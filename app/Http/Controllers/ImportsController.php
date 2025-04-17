<?php

namespace App\Http\Controllers;

use App\Helpers\GlobalHelper;
use App\Models\Exports;
use App\Models\Imports;
use App\Models\InventoryHistory;
use App\Models\InventoryLookup;
use App\Models\Notification;
use App\Models\Product;
use App\Models\ProductExport;
use App\Models\ProductImport;
use App\Models\Providers;
use App\Models\Quotation;
use App\Models\Receiving;
use App\Models\ReturnForm;
use App\Models\SerialNumber;
use App\Models\User;
use App\Notifications\InventoryLookupNotification;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ImportsController extends Controller
{
    private $imports;

    public function __construct()
    {
        $this->imports = new Imports();
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $title = "Phiếu nhập hàng";
        $imports = $this->imports->getAllImports();
        $users = User::get();
        $providers = Providers::get();
        return view('expertise.import.index', compact('title', 'imports', 'users', 'providers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $title = "Tạo phiếu nhập hàng";
        $providers = Providers::all();
        $import_code = $this->imports->generateImportCode();
        //Lấy data products
        $products = Product::all();
        //
        $users = User::all();
        return view('expertise.import.create', compact('title', 'providers', 'import_code', "products", "users"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // $validatedData = $request->validate(
        //     [
        //         'import_code' => 'required|string|max:255|unique:imports,import_code',
        //         'user_id'     => 'required|integer|exists:users,id',
        //         'phone'       => 'nullable|string|max:15',
        //         'date_create' => 'required|date',
        //         'provider_id' => 'required|integer|exists:providers,id',
        //         'address'     => 'nullable|string|max:255',
        //         'note'        => 'nullable|string|max:500',
        //     ],
        //     [
        //         'import_code.required' => 'Mã nhập kho là bắt buộc.',
        //         'import_code.unique'   => 'Mã nhập kho đã tồn tại.',
        //         'user_id.required'     => 'Người nhập là bắt buộc.',
        //         'date_create.required' => 'Ngày tạo là bắt buộc.',
        //         'provider_id.required'     => 'Nhà cung cấp là bắt buộc.',
        //     ]
        // );

        // $import = Imports::create($validatedData);
        // dd($request->all());

        // Phân biệt kho hàng mới và bảo hành qua role
        $roleUser = Auth::user()->roles()->first()->id;
        $warehouse_id = $roleUser == 2 ? 1 : ($roleUser == 3 ? 2 : null);

        $import_id = $this->imports->addImport($request->all());
        $dataTest = $request->input('data-test');

        // Giải mã chuỗi JSON thành mảng
        $uniqueProductsArray = json_decode($dataTest, true);

        // Duyệt qua từng sản phẩm trong mảng
        foreach ($uniqueProductsArray as $serial) {
            $sn_id = 0;
            // Nếu có serial thì tạo SerialNumber
            if (isset($serial['serial']) && !empty($serial['serial'])) {
                $newSerial = SerialNumber::create([
                    'serial_code' => str_replace(' ', '', $serial['serial']),
                    'product_id' => $serial['product_id'],
                    'note' => $serial['note_seri'],
                    'warehouse_id' => $warehouse_id ?? 1,
                ]);
                $sn_id = $newSerial->id;
            }
            ProductImport::create([
                'import_id' => $import_id,
                'product_id' => $serial['product_id'],
                'quantity' => $serial['qty'],
                'sn_id' => $sn_id,
                'note' => $serial['note_seri'],
            ]);
            //Tra cứu tồn kho
            InventoryLookup::create([
                'product_id' => $serial['product_id'],
                'sn_id' => $sn_id,
                'provider_id' => $request->provider_id,
                'import_date' => $request->date_create,
                'storage_duration' => 0,
                'status' => 0,
                'remaining_quantity' => $serial['qty'],
                'import_id' => $import_id,
                'warehouse_id' => $warehouse_id ?? 1,
            ]);
        }

        // Lấy tất cả các bản ghi trong InventoryLookup
        $records = InventoryLookup::all();

        foreach ($records as $record) {
            // Tính thời gian tồn kho
            $receivedDate = Carbon::parse($record->import_date); // Ngày nhập kho
            $storageDuration = $receivedDate->diffInDays(Carbon::now()); // Tính số ngày tồn kho

            // Cập nhật thời gian tồn kho
            $record->update(['storage_duration' => $storageDuration]);

            // Kiểm tra lần bảo trì đầu tiên hoặc các lần sau
            if ($storageDuration >= 90 && !$record->warranty_date) {
                // Lần bảo trì đầu tiên
                $record->status = 1;
                $record->save();
                // Gửi thông báo lần bảo trì đầu tiên
                $message = "tới hạn bảo trì";
                $this->notifyStatusChange($record, $message);
            } else if ($record->warranty_date) {
                // Các lần bảo trì tiếp theo
                $nextMaintenanceDate = Carbon::parse($record->warranty_date)->addDays(90);

                if (Carbon::now()->greaterThanOrEqualTo($nextMaintenanceDate)) {
                    // Nếu đã tới thời gian bảo trì tiếp theo
                    $record->status = 1;
                    $record->save();
                    // Gửi thông báo lần bảo trì tiếp theo
                    $message = "tới hạn bảo trì";
                    $this->notifyStatusChange($record, $message);
                }
            } else {
                $record->status = 0;
                $record->save();
            }
        }

        return redirect()->route('imports.index')->with('msg', 'Tạo phiếu nhập hàng thành công!');
    }

    private function notifyStatusChange($record, $message)
    {
        // Nếu không có serial thì không cần thông báo
        if (empty($record->serialNumber?->serial_code)) {
            return;
        }
        // Lấy tất cả người dùng không có quyền 'dichvu'
        $users = User::whereDoesntHave('permissions', function ($query) {
            $query->where('name', 'dichvu');
        })->get();

        foreach ($users as $user) {
            // Kiểm tra nếu người dùng không có quyền 'dichvu' và chưa nhận thông báo này
            if (!$user->hasPermissionTo('dichvu')) {
                // Gửi thông báo nếu chưa tồn tại thông báo tương tự cho user
                $existingNotification = $user->notifications()
                    ->where('type', InventoryLookupNotification::class)
                    ->where('data->inventoryLookup_id', $record->id)
                    ->where('data->message', $message)
                    ->where('data->warranty_date', $record->warranty_date)
                    ->exists();

                if (!$existingNotification) {
                    // Tạo thông báo mới
                    $user->notify(new InventoryLookupNotification($record, $message));
                }
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(String $id)
    {
        $import = Imports::leftJoin("providers", "providers.id", "imports.provider_id")
            ->leftJoin("users", "users.id", "imports.user_id")
            ->select("providers.provider_name", "users.name", "imports.*")
            ->where("imports.id", $id)
            ->first();
        $productImports = ProductImport::where("import_id", $id)
            ->get()->groupBy('product_id');
        $title = "Xem chi tiết phiếu nhập hàng";
        $providers = Providers::all();

        return view('expertise.import.see', compact('title', 'import', 'productImports', 'providers'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(String $id)
    {
        $import = Imports::leftJoin("providers", "providers.id", "imports.provider_id")
            ->leftJoin("users", "users.id", "imports.user_id")
            ->select("providers.provider_name", "users.name", "imports.*")
            ->where("imports.id", $id)
            ->first();
        if ($import) {
            $title = "Sửa phiếu nhập hàng";
            $users = User::all();
            $providers = Providers::all();

            $productImports = ProductImport::where("import_id", $id)
                ->get()->groupBy('product_id');
            $productAll = Product::all();
            $data = $this->imports->getAllImports();
            return view('expertise.import.edit', compact('title', 'import', 'users', 'providers', 'productAll', 'productImports', 'data'));
        } else {
            abort(404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, String $id)
    {
        $warehouse_id = GlobalHelper::getWarehouseId() ?? 1;
        // Validate dữ liệu đầu vào
        $validatedData = $request->validate([
            'import_code' => 'required|string|max:255|unique:imports,import_code,' . $id,
            'user_id'     => 'required|integer|exists:users,id',
            'phone'       => 'nullable|string|max:15',
            'date_create' => 'required|date',
            'provider_id' => 'required|integer|exists:providers,id',
            'address'     => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'note'        => 'nullable|string|max:500',
        ], [
            'import_code.required' => 'Mã phiếu là bắt buộc.',
            'user_id.required'     => 'Người nhập là bắt buộc.',
            'date_create.required' => 'Ngày tạo là bắt buộc.',
        ]);
        $validatedData['warehouse_id'] = $warehouse_id;
        // Tìm import theo ID
        $import = Imports::findOrFail($id);

        // Cập nhật dữ liệu
        $import->update($validatedData);

        // Cập nhật import_date của InventoryLookup
        InventoryLookup::whereIn('sn_id', ProductImport::where('import_id', $id)->pluck('sn_id'))
            ->update(['import_date' => $request->date_create, 'provider_id' => $request->provider_id]);

        // Lấy dữ liệu từ form gửi lên
        $dataTest = json_decode($request->input('data-test'), true);

        // Chuẩn hóa serial từ form (loại bỏ khoảng trắng và chuyển chữ thường)
        $formSerials = array_map(function ($serial) {
            return strtolower(trim(str_replace(' ', '', $serial)));
        }, array_column($dataTest, 'serial'));

        // Chuẩn hóa serial trong database
        $existingSerials = SerialNumber::whereIn('serial_code', $formSerials)->get();
        $existingSerialCodes = $existingSerials->pluck('serial_code')->map(function ($serial) {
            return strtolower(trim(str_replace(' ', '', $serial)));
        })->toArray();

        $serialIds = $existingSerials->pluck('id', 'serial_code')->mapWithKeys(function ($id, $serial) {
            return [strtolower(trim(str_replace(' ', '', $serial))) => $id];
        })->toArray();

        // Lọc các serial cần thêm mới
        $newSerials = array_filter($dataTest, function ($data) use ($existingSerialCodes) {
            $normalizedSerial = strtolower(trim(str_replace(' ', '', $data['serial'] ?? '')));
            return isset($data['serial']) && !empty($data['serial']) && !in_array($normalizedSerial, $existingSerialCodes);
        });

        // Thêm mới các serial
        foreach ($newSerials as $serialData) {
            if (isset($serialData['serial']) && !empty($serialData['serial'])) {
                $normalizedSerial = trim(str_replace(' ', '', $serialData['serial']));

                $newSerial = SerialNumber::create([
                    'serial_code' => $normalizedSerial,
                    'product_id' => $serialData['product_id'],
                ]);

                $normalizedSerial = strtolower(trim(str_replace(' ', '', $serialData['serial'])));
                $serialIds[$normalizedSerial] = $newSerial->id;

                // Thêm vào InventoryLookup
                InventoryLookup::create([
                    'product_id' => $serialData['product_id'],
                    'sn_id' => $newSerial->id,
                    'provider_id' => $request->provider_id,
                    'import_date' => $request->date_create,
                    'storage_duration' => 0,
                    'status' => 0,
                    'remaining_quantity' => $serialData['qty'],
                    'warehouse_id' => $warehouse_id ?? 1,
                ]);
            }
        }

        // Thêm mới hoặc cập nhật ProductImport
        foreach ($dataTest as $data) {
            if (isset($data['serial']) && !empty($data['serial'])) {
                $normalizedSerial = strtolower(trim(str_replace(' ', '', $data['serial'])));
                if (isset($serialIds[$normalizedSerial])) {
                    $snId = $serialIds[$normalizedSerial];

                    ProductImport::updateOrCreate(
                        [
                            'import_id' => $id,
                            'product_id' => $data['product_id'],
                            'sn_id' => $snId,
                        ],
                        [
                            'note' => $data['note_seri'] ?? '',
                            'quantity' => $data['qty'],
                        ]
                    );

                    InventoryLookup::updateOrCreate(
                        [
                            'sn_id' => $snId,
                            'product_id' => $data['product_id'],
                            'provider_id' => $request->provider_id,
                            'import_date' => $request->date_create,
                            'warehouse_id' => $warehouse_id ?? 1,
                        ],
                        [
                            'remaining_quantity' => $data['qty'],
                        ]
                    );
                }
            }
        }

        // Lấy danh sách sn_id từ form
        $formSerialIds = array_filter(array_map(function ($data) use ($serialIds) {
            $normalizedSerial = strtolower(trim(str_replace(' ', '', $data['serial'] ?? '')));
            return isset($data['serial']) && !empty($data['serial']) ? $serialIds[$normalizedSerial] ?? null : null;
        }, $dataTest));

        // Tìm các serial bị xóa khỏi form
        $removedSnIds = ProductImport::where('import_id', $id)
            ->whereNotIn('sn_id', $formSerialIds)
            ->pluck('sn_id');

        // Xóa các serial không còn trong form
        ProductImport::where('import_id', $id)
            ->whereNotIn('sn_id', $formSerialIds)
            ->delete();

        if ($removedSnIds->isNotEmpty()) {
            SerialNumber::whereIn('id', $removedSnIds)->delete();
            $deletedInventoryLookupIds = InventoryLookup::whereIn('sn_id', $removedSnIds)->pluck('id')->toArray();
            InventoryLookup::whereIn('sn_id', $removedSnIds)->delete();
            Notification::whereIn('data->inventoryLookup_id', $deletedInventoryLookupIds)->delete();
        }

        // Lấy tất cả các bản ghi trong InventoryLookup
        $records = InventoryLookup::all();

        foreach ($records as $record) {
            // Tính thời gian tồn kho
            $receivedDate = Carbon::parse($record->import_date); // Ngày nhập kho
            $storageDuration = $receivedDate->diffInDays(Carbon::now()); // Tính số ngày tồn kho

            // Cập nhật thời gian tồn kho
            $record->update(['storage_duration' => $storageDuration]);

            // Kiểm tra lần bảo trì đầu tiên hoặc các lần sau
            if ($storageDuration >= 90 && !$record->warranty_date) {
                // Lần bảo trì đầu tiên
                $record->status = 1;
                $record->save();
                // Gửi thông báo lần bảo trì đầu tiên
                $message = "tới hạn bảo trì";
                $this->notifyStatusChange($record, $message);
            } else if ($record->warranty_date) {
                // Các lần bảo trì tiếp theo
                $nextMaintenanceDate = Carbon::parse($record->warranty_date)->addDays(90);

                if (Carbon::now()->greaterThanOrEqualTo($nextMaintenanceDate)) {
                    // Nếu đã tới thời gian bảo trì tiếp theo
                    $record->status = 1;
                    $record->save();
                    // Gửi thông báo lần bảo trì tiếp theo
                    $message = "tới hạn bảo trì";
                    $this->notifyStatusChange($record, $message);
                }
            } else {
                $record->status = 0;
                $record->save();
            }
        }

        return redirect()->route('imports.index')->with('msg', 'Cập nhật thành công phiếu nhập hàng!');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(String $id)
    {
        $import = Imports::findOrFail($id);
        $productImports = ProductImport::where('import_id', $id)->get();

        // Kiểm tra sản phẩm đã được xuất chưa
        foreach ($productImports as $productImport) {
            $exists = ProductExport::where('product_id', $productImport->product_id)
                ->where(function ($query) use ($productImport) {
                    if ($productImport->sn_id != 0) {
                        $query->where('sn_id', $productImport->sn_id);
                    }
                })
                ->exists();

            if ($exists) {
                return redirect()->route('imports.index')
                    ->with('warning', 'Xóa thất bại: do có sản phẩm đã được xuất!');
            }
        }

        // Xóa các InventoryLookup theo import_id
        $inventoryLookups = InventoryLookup::where('import_id', $id)->get();

        foreach ($inventoryLookups as $inventoryLookup) {
            Notification::whereJsonContains('data->inventoryLookup_id', $inventoryLookup->id)->delete();
            InventoryHistory::where('inventory_lookup_id', $inventoryLookup->id)->delete();
            $inventoryLookup->delete();
        }

        // Xóa các serial (nếu có)
        SerialNumber::whereIn('id', $productImports->pluck('sn_id')->filter()->toArray())->delete();

        // Xóa chi tiết nhập và phiếu nhập
        ProductImport::where('import_id', $id)->delete();
        $import->delete();

        return redirect()->route('imports.index')->with('msg', 'Xóa thành công phiếu nhập hàng!');
    }
    public function searchMiniView(Request $request)
    {
        // Định dạng lại ngày truyền vào để chắc chắn không có phần giờ
        $fromDate = Carbon::parse($request->fromDate)->format('Y-m-d');
        $toDate = Carbon::parse($request->toDate)->format('Y-m-d');

        if ($request->page == "NH") {
            $imports = Imports::query()
                ->when($request->idGuest, function ($query, $idGuest) {
                    return $query->where('provider_id', $idGuest);
                })
                ->when($request->creator, function ($query, $creator) {
                    return $query->where('imports.user_id', $creator);
                })
                ->whereBetween(DB::raw("DATE(imports.date_create)"), [$fromDate, $toDate])
                ->with('user', 'provider')
                ->get();

            $data = $imports->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'import_code' => $detail->import_code,
                    'date_create' => Carbon::parse($detail->date_create)->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y'),
                    'provider_name' => $detail->provider->provider_name ?? "",
                ];
            });

            return response()->json($data);
        }
        if ($request->page == "XH") {
            $exports = Exports::query()
                ->when($request->idGuest, function ($query, $idGuest) {
                    return $query->where('customer_id', $idGuest);
                })
                ->when($request->creator, function ($query, $creator) {
                    return $query->where('exports.user_id', $creator);
                })
                ->whereBetween(DB::raw("DATE(exports.date_create)"), [$fromDate, $toDate])
                ->with('user', 'customer')
                ->get();

            $data = $exports->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'export_code' => $detail->export_code,
                    'date_create' => Carbon::parse($detail->date_create)->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y'),
                    'customer_name' => $detail->customer->customer_name,
                ];
            });

            return response()->json($data);
        }
        if ($request->page == "TN") {
            $receiving = Receiving::query()
                ->when($request->idGuest, function ($query, $idGuest) {
                    return $query->where('customer_id', $idGuest);
                })
                ->when($request->creator, function ($query, $creator) {
                    return $query->where('receiving.user_id', $creator);
                })
                ->whereBetween(DB::raw("DATE(receiving.date_created)"), [$fromDate, $toDate])
                ->with('user', 'customer')
                ->get();

            $data = $receiving->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'form_code_receiving' => $detail->form_code_receiving,
                    'status' => match ($detail->status) {
                        1 => 'Tiếp nhận',
                        2 => 'Xử lý',
                        3 => 'Hoàn thành',
                        4 => 'Khách không đồng ý',
                    },
                    'date_create' => Carbon::parse($detail->date_created)->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y'),
                    'customer_name' => $detail->customer->customer_name,
                ];
            });

            return response()->json($data);
        }
        if ($request->page == "BG") {
            $quotation = Quotation::query()
                ->when($request->idGuest, function ($query, $idGuest) {
                    return $query->where('customer_id', $idGuest);
                })
                ->when($request->creator, function ($query, $creator) {
                    return $query->where('quotations.user_id', $creator);
                })
                ->whereBetween(DB::raw("DATE(quotations.quotation_date)"), [$fromDate, $toDate])
                ->with('customer')
                ->get();

            $data = $quotation->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'quotation_code' => $detail->quotation_code,
                    'form_type' => match ($detail->reception->form_type) {
                        1 => 'Bảo hành',
                        2 => 'Dịch vụ',
                        3 => 'Bảo hành dịch vụ',
                    },
                    'date_create' => Carbon::parse($detail->quotation_date)->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y'),
                    'customer_name' => $detail->customer->customer_name,
                ];
            });

            return response()->json($data);
        }
        if ($request->page == "TH") {
            $returnForm = ReturnForm::query()
                ->when($request->idGuest, function ($query, $idGuest) {
                    return $query->where('customer_id', $idGuest);
                })
                ->when($request->creator, function ($query, $creator) {
                    return $query->where('return_form.user_id', $creator);
                })
                ->whereBetween(DB::raw("DATE(return_form.date_created)"), [$fromDate, $toDate])
                ->with('productReturns', 'reception', 'customer')
                ->get();

            $data = $returnForm->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'return_code' => $detail->return_code,
                    'status' => match ((int) $detail->status) {
                        1 => 'Hoàn thành',
                        2 => 'Khách không đồng ý',
                        default => 'Trạng thái không xác định',
                    },
                    'date_create' => Carbon::parse($detail->date_created)->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y'),
                    'customer_name' => $detail->customer->customer_name,
                ];
            });

            return response()->json($data);
        }
    }
    public function filterData(Request $request)
    {
        $data = $request->all();
        $filters = [];
        if (isset($data['ma']) && $data['ma'] !== null) {
            $filters[] = ['value' => 'Mã: ' . $data['ma'], 'name' => 'ma-phieu', 'icon' => 'po'];
        }
        if (isset($data['note']) && $data['note'] !== null) {
            $filters[] = ['value' => 'Ghi chú: ' . $data['note'], 'name' => 'ghi-chu', 'icon' => 'po'];
        }
        if (isset($data['serial']) && $data['serial'] !== null) {
            $filters[] = ['value' => 'S/N: ' . $data['serial'], 'name' => 'serial', 'icon' => 'po'];
        }
        if (isset($data['product_name']) && $data['product_name'] !== null) {
            $filters[] = ['value' => 'Tên sản phẩm: ' . $data['product_name'], 'name' => 'ten-san-pham', 'icon' => 'po'];
        }
        if (isset($data['product_code']) && $data['product_code'] !== null) {
            $filters[] = ['value' => 'Mã sản phẩm: ' . $data['product_code'], 'name' => 'ma-san-pham', 'icon' => 'po'];
        }
        if (isset($data['user']) && $data['user'] !== null) {
            $filters[] = ['value' => 'Người lập phiếu: ' . count($data['user']) . ' đã chọn', 'name' => 'nguoi-lap-phieu', 'icon' => 'user'];
        }
        if (isset($data['provider']) && $data['provider'] !== null) {
            $filters[] = ['value' => 'Nhà cung cấp: ' . count($data['provider']) . ' đã chọn', 'name' => 'nha-cung-cap', 'icon' => 'user'];
        }
        if (isset($data['date']) && $data['date'][1] !== null) {
            $date_start = date("d/m/Y", strtotime($data['date'][0]));
            $date_end = date("d/m/Y", strtotime($data['date'][1]));
            $filters[] = ['value' => 'Ngày lập phiếu: từ ' . $date_start . ' đến ' . $date_end, 'name' => 'ngay-lap-phieu', 'icon' => 'date'];
        }
        if ($request->ajax()) {
            $imports = $this->imports->getImportAjax($data);
            return response()->json([
                'data' => $imports,
                'filters' => $filters,
            ]);
        }
        return false;
    }
}
