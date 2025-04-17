<?php

namespace App\Http\Controllers;

use App\Helpers\GlobalHelper;
use App\Models\Customers;
use App\Models\Exports;
use App\Models\InventoryLookup;
use App\Models\Product;
use App\Models\ProductExport;
use App\Models\ProductWarranties;
use App\Models\ReceivedProduct;
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
            $productId = (int)$serial['product_id'];
            $qty = (int)$serial['qty'];
            $note = $serial['note_seri'] ?? '';
            $warranties = $serial['warranty'] ?? [];
            $trimmedSerial = str_replace(' ', '', $serial['serial']);

            if (!empty($trimmedSerial)) {
                // Có serial
                $sn = SerialNumber::where('serial_code', $trimmedSerial)->first();

                if (!$sn) {
                    continue; // bỏ qua nếu serial không tồn tại
                }

                $snId = $sn->id;

                // Lấy thông tin tồn kho
                $lookup = InventoryLookup::where('product_id', $productId)
                    ->where('sn_id', $snId)
                    ->first();

                if (!$lookup || $lookup->remaining_quantity <= 0) {
                    continue; // Không còn hàng để xuất
                }

                // Kiểm tra số lượng xuất yêu cầu không vượt quá tồn kho
                $qtyToExport = $qty;  // Số lượng cần xuất
                $availableQuantity = $lookup->remaining_quantity;

                // Nếu tồn kho đủ
                $lookup->remaining_quantity -= $qtyToExport;
                $lookup->save();

                // Cập nhật trạng thái nếu hết tồn kho
                if ($lookup->remaining_quantity == 0) {
                    $sn->status = 2; // Đã xuất hết
                    $sn->save();
                }

                // Tạo bản ghi xuất
                ProductExport::create([
                    'export_id' => $export_id,
                    'product_id' => $productId,
                    'quantity' => $qtyToExport,  // Lưu số lượng đã xuất
                    'sn_id' => $snId,
                    'warranty' => json_encode($warranties),
                    'note' => $note,
                ]);

                // Tạo bản ghi bảo hành nếu có
                if ($warehouse_id != 2 && !empty($warranties)) {
                    foreach ($warranties as $warranty) {
                        $warrantyName = $warranty[0] ?? 'Trọn bộ';
                        $warrantyMonth = (int)($warranty[1] ?? 0);

                        $date = new DateTime($request->date_create);
                        $day = (int)$date->format('d');
                        $date->modify("+$warrantyMonth months");

                        if ((int)$date->format('d') !== $day) {
                            $date->modify('last day of last month');
                        }

                        WarrantyLookup::create([
                            'product_id' => $productId,
                            'sn_id' => $snId,
                            'customer_id' => $request->customer_id,
                            'export_return_date' => $request->date_create,
                            'warranty' => $warrantyMonth,
                            'name_warranty' => $warrantyName,
                            'status' => 0,
                            'warranty_expire_date' => $date->format('Y-m-d'),
                            'export_id' => $export_id,
                        ]);
                    }
                }
            } else {
                // Không có serial
                $snId = 0;
                $qtyToExport = $qty;
                $totalExported = 0;

                $lookupItems = InventoryLookup::where('product_id', $productId)
                    ->where('sn_id', 0)
                    ->where('remaining_quantity', '>', 0)
                    ->orderBy('created_at') // FIFO
                    ->get();

                foreach ($lookupItems as $item) {
                    if ($qtyToExport <= 0) break;

                    $available = $item->remaining_quantity;
                    $exportQty = min($qtyToExport, $available);

                    $item->remaining_quantity -= $exportQty;
                    $item->save();

                    $qtyToExport -= $exportQty;
                    $totalExported += $exportQty;
                }

                // Tạo 1 bản ghi xuất duy nhất
                ProductExport::create([
                    'export_id' => $export_id,
                    'product_id' => $productId,
                    'quantity' => $totalExported,
                    'sn_id' => 0,
                    'warranty' => json_encode($warranties),
                    'note' => $note,
                ]);

                // Tạo bảo hành nếu có
                if ($warehouse_id != 2 && !empty($warranties)) {
                    foreach ($warranties as $warranty) {
                        $warrantyName = $warranty[0] ?? 'Trọn bộ';
                        $warrantyMonth = (int)($warranty[1] ?? 0);

                        $date = new DateTime($request->date_create);
                        $day = (int)$date->format('d');
                        $date->modify("+$warrantyMonth months");

                        if ((int)$date->format('d') !== $day) {
                            $date->modify('last day of last month');
                        }

                        WarrantyLookup::create([
                            'product_id' => $productId,
                            'sn_id' => 0,
                            'customer_id' => $request->customer_id,
                            'export_return_date' => $request->date_create,
                            'warranty' => $warrantyMonth,
                            'name_warranty' => $warrantyName,
                            'status' => 0,
                            'warranty_expire_date' => $date->format('Y-m-d'),
                            'export_id' => $export_id,
                        ]);
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
        $validatedData = $request->validate([
            'export_code' => 'required|string|max:255|unique:exports,export_code,' . $id,
            'user_id' => 'required|integer|exists:users,id',
            'phone' => 'nullable|string|max:15',
            'date_create' => 'required|date',
            'customer_id' => 'required|integer|exists:customers,id',
            'address' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:500',
        ], [
            'export_code.required' => 'Mã phiếu là bắt buộc.',
            'user_id.required' => 'Người nhập là bắt buộc.',
            'date_create.required' => 'Ngày tạo là bắt buộc.',
        ]);

        $validatedData['warehouse_id'] = $warehouse_id;
        $export = Exports::findOrFail($id);
        $export->update($validatedData);
        // Cập nhật export_return_date của warrantyLookup
        WarrantyLookup::whereIn('sn_id', ProductExport::where('export_id', $id)->pluck('sn_id'))
            ->update(['export_return_date' => $request->date_create, 'customer_id' => $request->customer_id]);

        $dataTest = json_decode($request->input('data-test'), true);
        $formSerials = array_map(fn($s) => strtolower(str_replace(' ', '', $s)), array_column($dataTest, 'serial'));
        $existingSerials = SerialNumber::all()->mapWithKeys(fn($serial) => [
            strtolower(str_replace(' ', '', $serial->serial_code)) => $serial->id
        ]);

        $currentExports = ProductExport::where('export_id', $id)->get();
        $currentSnIds = $currentExports->pluck('sn_id')->filter()->toArray(); // bỏ sn_id = 0

        // Tạo danh sách sn_id từ form: cả serial thật và sn_id = 0
        $formSnIds = collect($dataTest)->map(function ($data) use ($existingSerials) {
            if (!empty($data['serial'])) {
                $normalized = strtolower(str_replace(' ', '', $data['serial']));
                return $existingSerials[$normalized] ?? null;
            }
            return 0;
        })->filter(fn($id) => is_numeric($id))->unique()->values()->toArray();

        // Riêng sn_id > 0 để xử lý phần 3
        $formSnIdsOnly = array_filter($formSnIds, fn($id) => $id > 0);

        // PHẦN 1: Reset tồn kho các dòng cũ
        foreach ($currentExports as $exported) {
            $lookup = InventoryLookup::where('product_id', $exported->product_id)
                ->when($exported->sn_id, fn($q) => $q->where('sn_id', $exported->sn_id))
                ->first();

            if ($lookup) {
                $lookup->increment('remaining_quantity', $exported->quantity);
            }
        }

        ProductExport::where('export_id', $id)->delete();

        // PHẦN 2: Ghi lại dữ liệu mới
        foreach ($dataTest as $data) {
            $productId = $data['product_id'];
            $qty = $data['qty'] ?? 1;
            $note = $data['note_seri'] ?? '';
            $warranties = $data['warranty'] ?? [];

            if (!empty($data['serial'])) {
                $normalizedSerial = strtolower(str_replace(' ', '', $data['serial']));
                if (!isset($existingSerials[$normalizedSerial])) continue;

                $snId = $existingSerials[$normalizedSerial];

                ProductExport::create([
                    'export_id' => $id,
                    'product_id' => $productId,
                    'sn_id' => $snId,
                    'note' => $note,
                    'warranty' => json_encode($warranties),
                    'quantity' => $qty,
                ]);

                SerialNumber::where('id', $snId)->update(['status' => 2]);

                $lookup = InventoryLookup::where('product_id', $productId)
                    ->where('sn_id', $snId)
                    ->first();

                if ($lookup && $lookup->remaining_quantity >= $qty) {
                    $lookup->decrement('remaining_quantity', $qty);
                }

                // Bảo hành
                if ($warehouse_id != 2) {
                    $formWarrantyNames = [];

                    foreach ($warranties as $item) {
                        $nameWarranty = $item[0];
                        $months = (int) $item[1];
                        $formWarrantyNames[] = $nameWarranty;

                        $startDate = Carbon::parse($request->date_create);
                        $expire = $startDate->copy()->addMonthsNoOverflow($months);
                        if ($expire->day < $startDate->day) $expire = $expire->endOfMonth();

                        WarrantyLookup::updateOrCreate([
                            'sn_id' => $snId,
                            'product_id' => $productId,
                            'name_warranty' => $nameWarranty,
                            'customer_id' => $request->customer_id,
                            'export_id' => $id,
                        ], [
                            'product_id' => $productId,
                            'customer_id' => $request->customer_id,
                            'export_return_date' => $request->date_create,
                            'warranty' => $months,
                            'status' => 0,
                            'warranty_expire_date' => $expire->format('Y-m-d'),
                            'export_id' => $id,
                        ]);
                    }

                    //Xoá các warranty đã bị bỏ khỏi form
                    if (!empty($formWarrantyNames)) {
                        WarrantyLookup::where('sn_id', $snId)
                            ->where('export_id', $id)
                            ->where('product_id', $productId)
                            ->whereNotIn('name_warranty', $formWarrantyNames)
                            ->delete();
                    }
                }
            } else {
                $formWarrantyNames = [];
                // Không có serial
                ProductExport::create([
                    'export_id' => $id,
                    'product_id' => $productId,
                    'sn_id' => 0,
                    'quantity' => $qty,
                    'note' => $note,
                    'warranty' => json_encode($warranties),
                ]);

                $lookup = InventoryLookup::where('product_id', $productId)
                    ->where('sn_id', 0)
                    ->first();

                if ($lookup && $lookup->remaining_quantity >= $qty) {
                    $lookup->decrement('remaining_quantity', $qty);
                }

                if ($warehouse_id != 2) {
                    foreach ($warranties as $item) {
                        $nameWarranty = $item[0];
                        $months = (int) $item[1];
                        $formWarrantyNames[] = $nameWarranty;
                        $startDate = Carbon::parse($request->date_create);
                        $expire = $startDate->copy()->addMonthsNoOverflow($months);
                        if ($expire->day < $startDate->day) $expire = $expire->endOfMonth();

                        WarrantyLookup::updateOrCreate([
                            'sn_id' => 0,
                            'product_id' => $productId,
                            'name_warranty' => $nameWarranty,
                            'customer_id' => $request->customer_id,
                            'export_id' => $id,
                        ], [
                            'product_id' => $productId,
                            'customer_id' => $request->customer_id,
                            'export_return_date' => $request->date_create,
                            'warranty' => $months,
                            'status' => 0,
                            'warranty_expire_date' => $expire->format('Y-m-d'),
                            'export_id' => $id,
                        ]);
                    }
                }

                // Xoá các warranty đã bị bỏ khỏi form
                if (!empty($formWarrantyNames)) {
                    WarrantyLookup::where('sn_id', 0)
                        ->where('export_id', $id)
                        ->where('product_id', $productId)
                        ->whereNotIn('name_warranty', $formWarrantyNames)
                        ->delete();
                }
            }
        }

        // PHẦN 3: Xử lý serial bị xóa
        $removedSnIds = array_diff($currentSnIds, $formSnIdsOnly);

        if (!empty($removedSnIds)) {
            SerialNumber::whereIn('id', $removedSnIds)->update(['status' => 1]);
            WarrantyLookup::whereIn('sn_id', $removedSnIds)->where('export_id', $id)->delete();
        }

        // PHẦN 4: Cập nhật trạng thái bảo hành
        $today = Carbon::now();
        $records = WarrantyLookup::all();

        foreach ($records as $record) {
            $isExpired = $today->greaterThanOrEqualTo($record->warranty_expire_date);
            $record->update(['status' => $isExpired ? 1 : 0]);

            $snIdRecords = WarrantyLookup::where('sn_id', $record->sn_id)->get();
            $expiredNames = $snIdRecords->filter(fn($r) => $today->greaterThanOrEqualTo($r->warranty_expire_date))
                ->pluck('name_warranty')->toArray();

            $record->update([
                'name_status' => empty($expiredNames) ? 'Còn bảo hành' : implode(', ', $expiredNames) . ' hết bảo hành'
            ]);
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

        // Kiểm tra sản phẩm đã được tiếp nhận bảo hành chưa
        foreach ($productExports as $productExport) {
            $exists = ReceivedProduct::where('product_id', $productExport->product_id)
                ->exists();

            if ($exists) {
                return redirect()->route('exports.index')
                    ->with('warning', 'Xóa thất bại: do có sản phẩm đã được tiếp nhận bảo hành!');
            }
        }

        // Xóa toàn bộ warranty_lookup liên quan đến export_id
        $warrantyLookups = WarrantyLookup::where('export_id', $id)->get();

        foreach ($warrantyLookups as $warrantyLookup) {
            WarrantyHistory::where('warranty_lookup_id', $warrantyLookup->id)->delete();
            $warrantyLookup->delete();
        }

        // Cập nhật lại tồn kho
        foreach ($productExports as $productExport) {
            $lookup = InventoryLookup::where('product_id', $productExport->product_id)
                ->where('sn_id', $productExport->sn_id);

            // Nếu là hàng không có serial (sn_id = 0) thì phải chọn theo thứ tự nhập (FIFO)
            if ($productExport->sn_id == 0) {
                $lookup = $lookup->orderBy('created_at', 'asc')->first();
            } else {
                $lookup = $lookup->first();
            }

            if ($lookup) {
                $lookup->remaining_quantity += $productExport->quantity;
                $lookup->save();
            }
        }

        // Cập nhật lại serial (nếu có)
        SerialNumber::whereIn('id', $productExports->pluck('sn_id')->filter()->toArray())
            ->update(['status' => 1]);

        // Xóa ProductExport
        ProductExport::where('export_id', $id)->delete();

        // Xóa phiếu xuất
        $export->delete();

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
