<?php

namespace App\Http\Controllers;

use App\Helpers\GlobalHelper;
use App\Models\InventoryHistory;
use App\Models\InventoryLookup;
use App\Models\Product;
use App\Models\Providers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryLookupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private $inventoryLookup;
    public function __construct(InventoryLookup $inventoryLookup)
    {
        $this->inventoryLookup = $inventoryLookup;
    }
    public function index()
    {
        $title = "Tra cứu tồn kho";
        $warehouse_id = GlobalHelper::getWarehouseId();
        $inventory = InventoryLookup::with(['product', 'serialNumber', 'provider'])
            ->whereHas('serialNumber', function ($query) {
                $query->whereIn('status', [1, 5]);
            });
        if (Auth::user()->roles()->first()->id != 1 && !Auth::user()->hasAnyRole(['Quản lý kho'])) {
            if ($warehouse_id) {
                $inventory = $inventory->whereHas('serialNumber', function ($query) use ($warehouse_id) {
                    $query->where('warehouse_id', $warehouse_id);
                });
            }
        }
        $inventory = $inventory->orderby('id', 'DESC')->get();
        $providers = Providers::all();
        return view('expertise.inventoryLookup.index', compact('title', 'inventory', 'providers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(InventoryLookup $inventoryLookup)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(String $id)
    {
        $title = "Tra cứu tồn kho";
        $inventoryLookup = InventoryLookup::with(['product', 'serialNumber'])
            ->where("id", $id)
            ->first();
        if ($inventoryLookup) {
            $histories = InventoryHistory::with('inventoryLookup')
                ->where("inventory_lookup_id", $id)
                ->orderBy('created_at', 'desc')
                ->get();
            return view('expertise.inventoryLookup.edit', compact('title', 'inventoryLookup', 'histories'));
        } else {
            abort(404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(String $id, Request $request)
    {
        $inventoryLookup = InventoryLookup::findOrFail($id);
        $inventoryLookup->warranty_date = $request->warranty_date;
        $inventoryLookup->note = $request->note;
        $inventoryLookup->status = 0;
        $inventoryLookup->save();
        //Lưu lịch sử 
        if (!empty($request->warranty_date)) {
            // Thêm mới vào inventory_history
            InventoryHistory::create([
                'inventory_lookup_id' => $id,
                'import_date' => $inventoryLookup->import_date,
                'storage_duration' => $inventoryLookup->storage_duration,
                'warranty_date' => $request->warranty_date,
                'note' => $request->note,
            ]);
        }
        return redirect()->route('inventoryLookup.index')->with('msg', 'Cập nhật thành công bảo trì định kỳ!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InventoryLookup $inventoryLookup)
    {
        //
    }
    public function filterData(Request $request)
    {
        $data = $request->all();
        $filters = [];
        if (isset($data['ma']) && $data['ma'] !== null) {
            $filters[] = ['value' => 'Mã hàng: ' . $data['ma'], 'name' => 'ma-hang', 'icon' => 'po'];
        }
        if (isset($data['ten']) && $data['ten'] !== null) {
            $filters[] = ['value' => 'Tên hàng: ' . $data['ten'], 'name' => 'ten-hang', 'icon' => 'product'];
        }
        if (isset($data['brand']) && $data['brand'] !== null) {
            $filters[] = ['value' => 'Hãng: ' . $data['brand'], 'name' => 'hang', 'icon' => 'brand'];
        }
        if (isset($data['sn']) && $data['sn'] !== null) {
            $filters[] = ['value' => 'Serial: ' . $data['sn'], 'name' => 'serial', 'icon' => 'sn'];
        }
        if (isset($data['provider']) && $data['provider'] !== null) {
            $filters[] = ['value' => 'Nhà cung cấp: ' . count($data['provider']) . ' đã chọn', 'name' => 'nha-cung-cap', 'icon' => 'provider'];
        }
        if (isset($data['date']) && $data['date'][1] !== null) {
            $date_start = date("d/m/Y", strtotime($data['date'][0]));
            $date_end = date("d/m/Y", strtotime($data['date'][1]));
            $filters[] = ['value' => 'Ngày nhập hàng: từ ' . $date_start . ' đến ' . $date_end, 'name' => 'ngay-nhap-hang', 'icon' => 'date'];
        }
        if (isset($data['status']) && $data['status'] !== null) {
            $statusValues = [];
            if (in_array(1, $data['status'])) {
                $statusValues[] = '<span style="color: #858585;">Tới hạn bảo trì</span>';
            }
            if (in_array(0, $data['status'])) {
                $statusValues[] = '<span style="color: #08AA36BF;">Blank</span>';
            }
            $filters[] = ['value' => 'Tình trạng: ' . implode(', ', $statusValues), 'name' => 'trang-thai', 'icon' => 'status'];
        }
        if (isset($data['time_inven']) && $data['time_inven'][1] !== null) {
            $filters[] = ['value' => 'Thời gian tồn kho: ' . $data['time_inven'][0] . ' ' . $data['time_inven'][1], 'name' => 'thoi-gian-ton-kho', 'icon' => 'money'];
        }

        if ($request->ajax()) {
            $inventoryLookup = $this->inventoryLookup->getInvenAjax($data);
            return response()->json([
                'data' => $inventoryLookup,
                'filters' => $filters,
            ]);
        }
        return false;
    }
}
