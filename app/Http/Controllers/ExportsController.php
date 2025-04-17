<?php

namespace App\Http\Controllers;

use App\Helpers\GlobalHelper;
use App\Models\Customers;
use App\Models\Exports;
use App\Models\Product;
use App\Models\ProductExport;
use App\Models\ProductWarranties;
use App\Models\SerialNumber;
use App\Models\User;
use App\Models\warrantyHistory;
use App\Models\warrantyLookup;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExportsController extends Controller
{
    private $exports;

    public function __construct()
    {
        $this->exports = new Exports();
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $title = "Phiếu xuất hàng";
        $warehouse_id = GlobalHelper::getWarehouseId();
        $exports = Exports::with(['user', 'customer'])->orderBy('id', 'desc');
        if ($warehouse_id) {
            $exports = $exports->where('warehouse_id', $warehouse_id);
        }
        $exports = $exports->get();
        $users = User::all();
        $customers = Customers::all();
        return view('expertise.export.index', compact('title', 'exports', 'users', 'customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $title = "Tạo phiếu xuất hàng";
        $export_code = $this->exports->generateExportCode();
        $users = User::all();
        $customers = Customers::all();
        $products = Product::all();
        $productWarranty = ProductWarranties::all();
        return view('expertise.export.create', compact('title', 'export_code', 'users', 'customers', 'products', 'productWarranty'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $export_id = $this->exports->addExport($request->all());
        $dataTest = $request->input('data-test');

        // Giải mã chuỗi JSON thành mảng
        $uniqueProductsArray = json_decode($dataTest, true);
        $warehouse_id = GlobalHelper::getWarehouseId();
        // dd($warehouse_id);
        // Duyệt qua từng sản phẩm trong mảng
        foreach ($uniqueProductsArray as $serial) {
            if (isset($serial['serial']) && !empty($serial['serial'])) {
                $trimmedSerial = str_replace(' ', '', $serial['serial']);
                $sn = SerialNumber::where("serial_code", $trimmedSerial)->first();
                if ($sn) {
                    $sn->update([
                        'status' => 2,
                    ]);
                    ProductExport::create([
                        'export_id' => $export_id,
                        'product_id' => $serial['product_id'],
                        'quantity' => 1,
                        'sn_id' => $sn->id,
                        'warranty' => json_encode($serial['warranty']),
                        'note' => $serial['note_seri'],
                    ]);
                    if ($warehouse_id != 2) {
                        // Tạo bản ghi bảo hành cho từng warranty
                        foreach ($serial['warranty'] as $warranty) {
                            $warrantyName = $warranty[0] ?? null; // Tên bảo hành
                            $warrantyMonth = $warranty[1] ?? 0;   // Số tháng bảo hành

                            // Tạo đối tượng DateTime từ ngày xuất kho
                            $date = new DateTime($request->date_create);
                            $day = (int)$date->format('d');

                            // Cộng số tháng bảo hành
                            $date->modify("+$warrantyMonth months");

                            // Nếu ngày gốc là 29, 30, 31 nhưng tháng mới không có ngày đó, đặt về ngày cuối tháng
                            if ((int)$date->format('d') !== $day) {
                                $date->modify('last day of last month');
                            }

                            warrantyLookup::create([
                                'product_id' => $serial['product_id'],
                                'sn_id' => $sn->id,
                                'customer_id' => $request->customer_id,
                                'export_return_date' => $request->date_create,
                                'warranty' => $warrantyMonth,
                                'name_warranty' => $warrantyName ?? "Trọn bộ",
                                'status' => 0,
                                'warranty_expire_date' => $date->format('Y-m-d'),
                            ]);
                        }
                    }
                }
            }
        }
        //Cập nhật trạng thái bảo hành
        $today = Carbon::now();
        $records = WarrantyLookup::all();
        foreach ($records as $record) {
            if ($today->greaterThanOrEqualTo($record->warranty_expire_date)) {
                $record->update(['status' => 1]); // Cập nhật trạng thái thành "hết bảo hành"
            } else {
                $record->update(['status' => 0]);
            }
            // Lọc ra các bản ghi có cùng sn_id
            $snIdRecords = WarrantyLookup::where('sn_id', $record->sn_id)->get();

            // Kiểm tra nếu có bất kỳ bản ghi nào hết hạn bảo hành
            $expired = false;
            $warranties = []; // Mảng để lưu các tên bảo hành hết hạn

            // Duyệt qua các bản ghi có cùng sn_id
            foreach ($snIdRecords as $snIdRecord) {
                if ($today->greaterThanOrEqualTo($snIdRecord->warranty_expire_date)) {
                    // Nếu bảo hành hết hạn, thêm tên bảo hành vào mảng và đánh dấu hết hạn
                    $expired = true;
                    $warranties[] = $snIdRecord->name_warranty;
                }
            }

            // Nối tên bảo hành hết hạn
            $status = implode(', ', $warranties) . ' hết bảo hành';

            // Nếu có bảo hành hết hạn, cập nhật trạng thái của tất cả bản ghi có cùng sn_id
            if ($expired) {
                WarrantyLookup::where('sn_id', $record->sn_id)
                    ->update(['name_status' => $status]);
            } else {
                // Nếu không có bảo hành hết hạn, thì cập nhật trạng thái là "Còn bảo hành"
                $record->update(['name_status' => "Còn bảo hành"]);
            }
        }
        return redirect()->route('exports.index')->with('msg', 'Tạo phiếu xuất hàng thành công!');
    }

    /**
     * Display the specified resource.
     */
    public function show(String $id)
    {
        $export = Exports::with(['user', 'customer'])->where("exports.id", $id)->first();
        $title = "Xem chi tiết phiếu xuất hàng";
        $productExports = ProductExport::with(['export', 'product', 'serialNumber'])
            ->where("export_id", $id)
            ->get();
        return view('expertise.export.show', compact('title', 'export', 'productExports'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(String $id)
    {
        $export = Exports::with(['user', 'customer'])->where("exports.id", $id)->first();
        if ($export) {
            $users = User::all();
            $customers = Customers::all();
            $title = "Sửa phiếu xuất hàng";
            $productExports = ProductExport::where("export_id", $id)
                ->get()->groupBy('product_id');
            $productAll = Product::all();
            $exports = Exports::with(['user', 'customer'])->orderBy('id', 'DESC')->get();
            $productWarranty = ProductWarranties::all();
            return view('expertise.export.edit', compact('title', 'export', 'users', 'customers', 'productExports', 'productAll', 'exports', 'productWarranty'));
        } else {
            abort(404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $warehouse_id = GlobalHelper::getWarehouseId() ?? 1;
        // Validate dữ liệu đầu vào
        $validatedData = $request->validate(
            [
                'export_code' => 'required|string|max:255|unique:exports,export_code,' . $id,
                'user_id' => 'required|integer|exists:users,id',
                'phone' => 'nullable|string|max:15',
                'date_create' => 'required|date',
                'customer_id' => 'required|integer|exists:customers,id',
                'address' => 'nullable|string|max:255',
                'contact_person' => 'nullable|string|max:255',
                'note' => 'nullable|string|max:500',
            ],
            [
                'export_code.required' => 'Mã phiếu là bắt buộc.',
                'user_id.required' => 'Người nhập là bắt buộc.',
                'date_create.required' => 'Ngày tạo là bắt buộc.',
            ]
        );

        // Gắn thêm giá trị warehouse_id vào dữ liệu validated
        $validatedData['warehouse_id'] = $warehouse_id;
        // Lấy bản ghi export cần cập nhật
        $export = Exports::findOrFail($id);
        // Cập nhật dữ liệu
        $export->update($validatedData);
        // Cập nhật export_return_date của warrantyLookup
        WarrantyLookup::whereIn('sn_id', ProductExport::where('export_id', $id)->pluck('sn_id'))
            ->update(['export_return_date' => $request->date_create, 'customer_id' => $request->customer_id]);

        // Lấy dữ liệu từ form gửi lên
        $dataTest = json_decode($request->input('data-test'), true);

        // Lấy danh sách serial từ form (chuẩn hóa)
        $formSerials = array_map(function ($serial) {
            return strtolower(str_replace(' ', '', $serial)); // Loại bỏ khoảng trắng và chuyển thành chữ thường
        }, array_column($dataTest, 'serial'));

        // Lấy serials từ database (chuẩn hóa)
        $existingSerials = SerialNumber::all()->mapWithKeys(function ($serial) {
            return [strtolower(str_replace(' ', '', $serial->serial_code)) => $serial->id];
        });

        // Lấy danh sách sn_id hiện tại trong ProductExport
        $currentSnIds = ProductExport::where('export_id', $id)->pluck('sn_id')->toArray();

        // 1. Xử lý thêm mới hoặc cập nhật các serial có sẵn
        foreach ($dataTest as $data) {
            if (isset($data['serial'], $data['product_id']) && !empty($data['serial'])) {
                // Chuẩn hóa serial
                $normalizedSerial = strtolower(str_replace(' ', '', $data['serial']));

                // Kiểm tra serial đã chuẩn hóa trong database
                if (isset($existingSerials[$normalizedSerial])) {
                    $snId = $existingSerials[$normalizedSerial];

                    // Thêm hoặc cập nhật ProductExport
                    $existingExport = ProductExport::where('export_id', $id)
                        ->where('product_id', $data['product_id'])
                        ->where('sn_id', $snId)
                        ->first();

                    if (!$existingExport) {
                        ProductExport::create([
                            'export_id' => $id,
                            'product_id' => $data['product_id'],
                            'sn_id' => $snId,
                            'note' => $data['note_seri'] ?? '',
                            'warranty' => json_encode($data['warranty']) ?? 12,
                        ]);
                    } else {
                        // Cập nhật ghi chú nếu cần
                        $existingExport->update([
                            'warranty' => json_encode($data['warranty']) ?? 12,
                            'note' => $data['note_seri'] ?? '',
                        ]);
                    }

                    // Cập nhật trạng thái serial thành 2 (exported)
                    SerialNumber::where('id', $snId)->update(['status' => 2]);
                    if ($warehouse_id != 2) {
                        // Duyệt qua các bảo hành trong mảng warranty
                        foreach ($data['warranty'] as $warrantyItem) {
                            $nameWarranty = $warrantyItem[0];
                            $warrantyPeriod = (int) $warrantyItem[1];

                            // Tính toán ngày hết hạn bảo hành chính xác
                            $startDate = Carbon::parse($request->date_create);
                            $warrantyExpireDate = $startDate->addMonthsNoOverflow($warrantyPeriod);

                            // Kiểm tra nếu ngày hết hạn nhỏ hơn ngày bắt đầu (do bị dồn về cuối tháng)
                            if ($warrantyExpireDate->day < $startDate->day) {
                                $warrantyExpireDate = $warrantyExpireDate->endOfMonth();
                            }

                            // Tìm kiếm WarrantyLookup dựa trên sn_id và name_warranty
                            $existingWarranty = WarrantyLookup::where('sn_id', $snId)
                                ->where('name_warranty', $nameWarranty)
                                ->first();

                            if (!$existingWarranty) {
                                // Nếu không tồn tại, tạo mới
                                WarrantyLookup::create([
                                    'product_id' => $data['product_id'],
                                    'sn_id' => $snId,
                                    'name_warranty' => $nameWarranty,
                                    'customer_id' => $request->customer_id,
                                    'export_return_date' => $request->date_create,
                                    'warranty' => $warrantyPeriod,
                                    'status' => 0,
                                    'warranty_expire_date' => $warrantyExpireDate->format('Y-m-d'),
                                ]);
                            } else {
                                // Nếu đã tồn tại, cập nhật thông tin
                                $existingWarranty->update([
                                    'warranty' => $warrantyPeriod,
                                    'name_warranty' => $nameWarranty,
                                    'warranty_expire_date' => $warrantyExpireDate->format('Y-m-d'),
                                ]);
                            }
                        }
                    }
                }
            }

            // 2. Lấy danh sách sn_id từ form (chỉ những serial tồn tại)
            $formSnIds = array_filter(array_map(function ($data) use ($existingSerials) {
                $normalizedSerial = strtolower(str_replace(' ', '', $data['serial'] ?? ''));
                return $existingSerials[$normalizedSerial] ?? null;
            }, $dataTest));

            // 3. Xử lý các serial bị xóa khỏi phiếu xuất
            $removedSnIds = array_diff($currentSnIds, $formSnIds);

            // Cập nhật trạng thái serial bị xóa về 1 (active)
            if (!empty($removedSnIds)) {
                SerialNumber::whereIn('id', $removedSnIds)->update(['status' => 1]);

                // Xóa khỏi WarrantyLookup nếu tồn tại
                WarrantyLookup::whereIn('sn_id', $removedSnIds)->delete();

                // Xóa serials bị xóa khỏi ProductExport
                ProductExport::where('export_id', $id)
                    ->whereIn('sn_id', $removedSnIds)
                    ->delete();
            }

            // 4. Kiểm tra và xóa các WarrantyLookup không còn hợp lệ
            foreach ($formSnIds as $snId) {
                // Lấy danh sách name_warranty từ form ứng với sn_id này
                $formWarranties = [];
                foreach ($dataTest as $data) {
                    $normalizedSerial = strtolower(str_replace(' ', '', $data['serial'] ?? ''));
                    if (($existingSerials[$normalizedSerial] ?? null) === $snId) {
                        $formWarranties = array_merge($formWarranties, array_column($data['warranty'] ?? [], 0));
                    }
                }

                // Lấy các bản ghi WarrantyLookup hiện tại từ database
                $warrantyLookups = WarrantyLookup::where('sn_id', $snId)->get();

                foreach ($warrantyLookups as $warranty) {
                    // Kiểm tra nếu name_warranty trong database không nằm trong danh sách từ form
                    if (!in_array($warranty->name_warranty, $formWarranties)) {
                        // Xóa bản ghi WarrantyLookup
                        $warranty->delete();
                    }
                }
            }
        }
        // Cập nhật trạng thái bảo hành
        $today = Carbon::now();
        $records = WarrantyLookup::all();
        foreach ($records as $record) {
            if ($today->greaterThanOrEqualTo($record->warranty_expire_date)) {
                $record->update(['status' => 1]); // Cập nhật trạng thái thành "hết bảo hành"
            } else {
                $record->update(['status' => 0]);
            }
            // Lọc ra các bản ghi có cùng sn_id
            $snIdRecords = WarrantyLookup::where('sn_id', $record->sn_id)->get();

            // Kiểm tra nếu có bất kỳ bản ghi nào hết hạn bảo hành
            $expired = false;
            $warranties = []; // Mảng để lưu các tên bảo hành hết hạn

            // Duyệt qua các bản ghi có cùng sn_id
            foreach ($snIdRecords as $snIdRecord) {
                if ($today->greaterThanOrEqualTo($snIdRecord->warranty_expire_date)) {
                    // Nếu bảo hành hết hạn, thêm tên bảo hành vào mảng và đánh dấu hết hạn
                    $expired = true;
                    $warranties[] = $snIdRecord->name_warranty;
                }
            }

            // Nối tên bảo hành hết hạn
            $status = implode(', ', $warranties) . ' hết bảo hành';

            // Nếu có bảo hành hết hạn, cập nhật trạng thái của tất cả bản ghi có cùng sn_id
            if ($expired) {
                WarrantyLookup::where('sn_id', $record->sn_id)
                    ->update(['name_status' => $status]);
            } else {
                // Nếu không có bảo hành hết hạn, thì cập nhật trạng thái là "Còn bảo hành"
                $record->update(['name_status' => "Còn bảo hành"]);
            }
        }

        return redirect()->route('exports.index')->with('msg', 'Cập nhật thành công phiếu xuất hàng!');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(String $id)
    {
        $export = Exports::findOrFail($id);
        $productExports = ProductExport::where('export_id', $id)->get();

        foreach ($productExports as $productExport) {
            $exists = SerialNumber::where('id', $productExport->sn_id)
                ->where('status', 2)
                ->exists();
            if (!$exists) {
                return redirect()->route('exports.index')->with('warning', 'Xóa thất bại: Có SerialNumber không hợp lệ.');
            }
        }

        // Nếu tất cả SerialNumber hợp lệ, thực hiện xóa
        foreach ($productExports as $productExport) {
            $warrantyLookups = warrantyLookup::where('sn_id', $productExport->sn_id)->get();
            foreach ($warrantyLookups as $warrantyLookup) {
                warrantyHistory::where('warranty_lookup_id', $warrantyLookup->id)->delete();
            }
            warrantyLookup::where('sn_id', $productExport->sn_id)->delete();
            SerialNumber::where('id', $productExport->sn_id)->update(['status' => 1]);
            $productExport->delete();
        }

        if (!ProductExport::where('export_id', $id)->exists()) {
            $export->delete();
        }

        return redirect()->route('exports.index')->with('msg', 'Xóa thành công phiếu xuất hàng!');
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
        if (isset($data['customer']) && $data['customer'] !== null) {
            $filters[] = ['value' => 'Khách hàng: ' . count($data['customer']) . ' đã chọn', 'name' => 'khách hàng', 'icon' => 'user'];
        }
        if (isset($data['date']) && $data['date'][1] !== null) {
            $date_start = date("d/m/Y", strtotime($data['date'][0]));
            $date_end = date("d/m/Y", strtotime($data['date'][1]));
            $filters[] = ['value' => 'Ngày lập phiếu: từ ' . $date_start . ' đến ' . $date_end, 'name' => 'ngay-lap-phieu', 'icon' => 'date'];
        }
        if ($request->ajax()) {
            $exports = $this->exports->getExportAjax($data);
            return response()->json([
                'data' => $exports,
                'filters' => $filters,
            ]);
        }
        return false;
    }
}
