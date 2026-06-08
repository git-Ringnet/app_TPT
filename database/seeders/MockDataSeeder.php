<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MockDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks to safely truncate tables
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        // Truncate tables to avoid duplicates when running seeds multiple times
        DB::table('groups')->truncate();
        DB::table('customers')->truncate();
        DB::table('providers')->truncate();
        DB::table('products')->truncate();
        DB::table('serial_numbers')->truncate();
        DB::table('imports')->truncate();
        DB::table('product_import')->truncate();
        DB::table('exports')->truncate();
        DB::table('product_export')->truncate();
        DB::table('inventory_lookup')->truncate();
        DB::table('warranty_lookup')->truncate();

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        // 1. Seed Groups
        DB::table('groups')->insert([
            // Customers
            ['id' => 1, 'group_type_id' => 1, 'group_code' => 'SILE', 'group_name' => 'Khách hàng Sỉ/Lẻ', 'description' => 'Khách mua sỉ hoặc lẻ trực tiếp'],
            ['id' => 2, 'group_type_id' => 1, 'group_code' => 'VIP', 'group_name' => 'Khách hàng VIP', 'description' => 'Khách hàng thân thiết có chiết khấu cao'],
            ['id' => 3, 'group_type_id' => 1, 'group_code' => 'DAILY', 'group_name' => 'Đại lý phân phối', 'description' => 'Cửa hàng liên kết đại lý'],
            
            // Providers
            ['id' => 4, 'group_type_id' => 2, 'group_code' => 'NPP_CHINH', 'group_name' => 'Nhà phân phối chính', 'description' => 'Nguồn hàng nhập chính hãng trực tiếp'],
            ['id' => 5, 'group_type_id' => 2, 'group_code' => 'NCC_PHU', 'group_name' => 'Nhà cung cấp phụ', 'description' => 'Đơn vị nhập linh kiện nhỏ lẻ'],
            
            // Products
            ['id' => 6, 'group_type_id' => 3, 'group_code' => 'RAM', 'group_name' => 'Bộ nhớ RAM', 'description' => 'Linh kiện bộ nhớ trong RAM'],
            ['id' => 7, 'group_type_id' => 3, 'group_code' => 'SSD', 'group_name' => 'Ổ cứng SSD', 'description' => 'Thiết bị lưu trữ thể rắn SSD'],
            ['id' => 8, 'group_type_id' => 3, 'group_code' => 'MAIN', 'group_name' => 'Bo mạch chủ (Mainboard)', 'description' => 'Các loại bo mạch chủ Intel/AMD'],
            ['id' => 9, 'group_type_id' => 3, 'group_code' => 'VGA', 'group_name' => 'Card màn hình (VGA)', 'description' => 'Card xử lý đồ họa NVIDIA/AMD'],
        ]);

        // 2. Seed Customers
        DB::table('customers')->insert([
            [
                'id' => 1,
                'group_id' => 3,
                'customer_code' => 'KH001',
                'customer_name' => 'Công ty Máy tính Phong Vũ',
                'address' => '264 Nguyễn Thị Minh Khai, Q.3, TP.HCM',
                'contact_person' => 'Nguyễn Văn An',
                'phone' => '02873016868',
                'email' => 'contact@phongvu.vn',
                'tax_code' => '0304674829',
                'note' => 'Khách hàng đại lý lớn khu vực miền Nam',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'group_id' => 2,
                'customer_code' => 'KH002',
                'customer_name' => 'Cửa hàng Tin học GearVN',
                'address' => '78-80 Hoàng Hoa Thám, P.12, Q.Tân Bình, TP.HCM',
                'contact_person' => 'Trần Minh Hoàng',
                'phone' => '18006975',
                'email' => 'sales@gearvn.com',
                'tax_code' => '0314982635',
                'note' => 'Khách hàng VIP, thanh toán nhanh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'group_id' => 1,
                'customer_code' => 'KH003',
                'customer_name' => 'Anh Nguyễn Văn Hùng',
                'address' => '12 Hùng Vương, Q.5, TP.HCM',
                'contact_person' => 'Nguyễn Văn Hùng',
                'phone' => '0912345678',
                'email' => 'hung.nguyen@gmail.com',
                'tax_code' => null,
                'note' => 'Khách lẻ mua máy tính lắp ráp',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'group_id' => 1,
                'customer_code' => 'KH004',
                'customer_name' => 'Chị Lê Thị Bình',
                'address' => '456 Lê Lợi, TP. Vũng Tàu',
                'contact_person' => 'Lê Thị Bình',
                'phone' => '0987654321',
                'email' => 'binh.le@yahoo.com',
                'tax_code' => null,
                'note' => 'Khách lẻ bảo hành từ xa',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 3. Seed Providers
        DB::table('providers')->insert([
            [
                'id' => 1,
                'group_id' => 4,
                'provider_code' => 'NCC001',
                'provider_name' => 'Công ty TNHH Viễn Sơn',
                'address' => '175 Nguyễn Thị Minh Khai, Q.1, TP.HCM',
                'contact_person' => 'Lê Minh Tuấn',
                'phone' => '02839250707',
                'email' => 'sales@microstar.com.vn',
                'tax_code' => '0301438902',
                'note' => 'Nhà phân phối ASUS, Gigabyte chính thức',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'group_id' => 4,
                'provider_code' => 'NCC002',
                'provider_name' => 'Công ty Cổ phần Dịch vụ Số FPT',
                'address' => 'Lô B1, Đường số 3, KCX Tân Thuận, Q.7, TP.HCM',
                'contact_person' => 'Nguyễn Thị Hương',
                'phone' => '02873006600',
                'email' => 'dist-sales@fpt.com.vn',
                'tax_code' => '0301127891',
                'note' => 'Nhà phân phối Kingston, Corsair chính hãng',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'group_id' => 5,
                'provider_code' => 'NCC003',
                'provider_name' => 'Nhà phân phối Mai Hoàng',
                'address' => '150 Bùi Thị Xuân, Q.1, TP.HCM',
                'contact_person' => 'Trần Văn Sơn',
                'phone' => '02839256150',
                'email' => 'sales@maihoang.com.vn',
                'tax_code' => '0101293845',
                'note' => 'Nhà cung cấp phụ cho nguồn hàng nhập thêm',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 4. Seed Products
        DB::table('products')->insert([
            [
                'id' => 1,
                'group_id' => 6,
                'product_code' => 'RAM-KST-8G',
                'product_name' => 'RAM Kingston ValueRAM 8GB DDR4 3200MHz',
                'brand' => 'Kingston',
                'warranty' => 36,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'group_id' => 6,
                'product_code' => 'RAM-COR-16G',
                'product_name' => 'RAM Corsair Vengeance LPX 16GB DDR4 3200MHz',
                'brand' => 'Corsair',
                'warranty' => 36,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'group_id' => 7,
                'product_code' => 'SSD-SAM-980-1T',
                'product_name' => 'SSD Samsung 980 Pro 1TB NVMe PCIe Gen4 M.2',
                'brand' => 'Samsung',
                'warranty' => 60,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'group_id' => 7,
                'product_code' => 'SSD-KST-NV2-500',
                'product_name' => 'SSD Kingston NV2 500GB NVMe PCIe Gen4',
                'brand' => 'Kingston',
                'warranty' => 36,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'group_id' => 8,
                'product_code' => 'MAIN-ASU-B760',
                'product_name' => 'Mainboard ASUS ROG Strix B760-F Gaming WiFi',
                'brand' => 'ASUS',
                'warranty' => 36,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'group_id' => 9,
                'product_code' => 'VGA-GIG-4060TI',
                'product_name' => 'Card màn hình Gigabyte RTX 4060 Ti EAGLE OC 8G',
                'brand' => 'Gigabyte',
                'warranty' => 36,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 5. Seed Serial Numbers
        DB::table('serial_numbers')->insert([
            // RAM Kingston (Product 1)
            ['id' => 1, 'serial_code' => 'SN-KST8G-001', 'product_id' => 1, 'status' => 1, 'note' => 'Nhập lô hàng mới tháng 6', 'warehouse_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'serial_code' => 'SN-KST8G-002', 'product_id' => 1, 'status' => 2, 'note' => 'Đã xuất cho Phong Vũ', 'warehouse_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'serial_code' => 'SN-KST8G-003', 'product_id' => 1, 'status' => 3, 'note' => 'Nhận bảo hành lỗi xanh màn', 'warehouse_id' => 2, 'created_at' => now(), 'updated_at' => now()],
            
            // RAM Corsair (Product 2)
            ['id' => 4, 'serial_code' => 'SN-COR16G-001', 'product_id' => 2, 'status' => 1, 'note' => 'Tồn kho hàng mới', 'warehouse_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'serial_code' => 'SN-COR16G-002', 'product_id' => 2, 'status' => 2, 'note' => 'Đã xuất cho GearVN', 'warehouse_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            
            // SSD Samsung (Product 3)
            ['id' => 6, 'serial_code' => 'SN-SAM980-001', 'product_id' => 3, 'status' => 1, 'note' => 'Tồn kho hàng mới', 'warehouse_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'serial_code' => 'SN-SAM980-002', 'product_id' => 3, 'status' => 2, 'note' => 'Đã xuất cho Phong Vũ', 'warehouse_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'serial_code' => 'SN-SAM980-003', 'product_id' => 3, 'status' => 4, 'note' => 'Khách lẻ trả hàng do không tương thích', 'warehouse_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            
            // SSD Kingston (Product 4)
            ['id' => 9, 'serial_code' => 'SN-KSTNV2-001', 'product_id' => 4, 'status' => 1, 'note' => 'Tồn kho hàng mới', 'warehouse_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'serial_code' => 'SN-KSTNV2-002', 'product_id' => 4, 'status' => 2, 'note' => 'Đã xuất cho GearVN', 'warehouse_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'serial_code' => 'SN-KSTNV2-003', 'product_id' => 4, 'status' => 3, 'note' => 'Tiếp nhận kiểm tra bad sector', 'warehouse_id' => 2, 'created_at' => now(), 'updated_at' => now()],
            
            // ASUS Mainboard (Product 5)
            ['id' => 12, 'serial_code' => 'SN-ASUB760-001', 'product_id' => 5, 'status' => 1, 'note' => 'Tồn kho hàng mới', 'warehouse_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 13, 'serial_code' => 'SN-ASUB760-002', 'product_id' => 5, 'status' => 2, 'note' => 'Đã xuất bán khách lẻ', 'warehouse_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            
            // Gigabyte VGA (Product 6)
            ['id' => 14, 'serial_code' => 'SN-GIG4060-001', 'product_id' => 6, 'status' => 1, 'note' => 'Tồn kho hàng mới', 'warehouse_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 15, 'serial_code' => 'SN-GIG4060-002', 'product_id' => 6, 'status' => 2, 'note' => 'Đã xuất cho GearVN', 'warehouse_id' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 6. Seed Imports
        DB::table('imports')->insert([
            [
                'id' => 1,
                'import_code' => 'IMP20260601',
                'user_id' => 1,
                'phone' => '02839250707',
                'date_create' => Carbon::parse('2026-06-01 10:00:00'),
                'provider_id' => 1, // Viễn Sơn
                'address' => '175 Nguyễn Thị Minh Khai, Q.1, TP.HCM',
                'contact_person' => 'Lê Minh Tuấn',
                'note' => 'Đơn nhập hàng linh kiện đầu tháng 6',
                'warehouse_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'import_code' => 'IMP20260602',
                'user_id' => 1,
                'phone' => '02873006600',
                'date_create' => Carbon::parse('2026-06-02 14:30:00'),
                'provider_id' => 2, // FPT
                'address' => 'Lô B1, Đường số 3, KCX Tân Thuận, Q.7, TP.HCM',
                'contact_person' => 'Nguyễn Thị Hương',
                'note' => 'Nhập hàng RAM và SSD số lượng lớn',
                'warehouse_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 7. Seed Product Import (Link products and serials to imports)
        DB::table('product_import')->insert([
            // Import 1 (ASUS Mainboard, Gigabyte VGA)
            ['id' => 1, 'import_id' => 1, 'product_id' => 5, 'quantity' => 1, 'sn_id' => 12, 'note' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'import_id' => 1, 'product_id' => 5, 'quantity' => 1, 'sn_id' => 13, 'note' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'import_id' => 1, 'product_id' => 6, 'quantity' => 1, 'sn_id' => 14, 'note' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'import_id' => 1, 'product_id' => 6, 'quantity' => 1, 'sn_id' => 15, 'note' => null, 'created_at' => now(), 'updated_at' => now()],
            
            // Import 2 (Kingston RAM, Corsair RAM, SSD Samsung, SSD Kingston)
            ['id' => 5, 'import_id' => 2, 'product_id' => 1, 'quantity' => 1, 'sn_id' => 1, 'note' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'import_id' => 2, 'product_id' => 1, 'quantity' => 1, 'sn_id' => 2, 'note' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'import_id' => 2, 'product_id' => 1, 'quantity' => 1, 'sn_id' => 3, 'note' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'import_id' => 2, 'product_id' => 2, 'quantity' => 1, 'sn_id' => 4, 'note' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'import_id' => 2, 'product_id' => 2, 'quantity' => 1, 'sn_id' => 5, 'note' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'import_id' => 2, 'product_id' => 3, 'quantity' => 1, 'sn_id' => 6, 'note' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'import_id' => 2, 'product_id' => 3, 'quantity' => 1, 'sn_id' => 7, 'note' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'import_id' => 2, 'product_id' => 3, 'quantity' => 1, 'sn_id' => 8, 'note' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 13, 'import_id' => 2, 'product_id' => 4, 'quantity' => 1, 'sn_id' => 9, 'note' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 14, 'import_id' => 2, 'product_id' => 4, 'quantity' => 1, 'sn_id' => 10, 'note' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 15, 'import_id' => 2, 'product_id' => 4, 'quantity' => 1, 'sn_id' => 11, 'note' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 8. Seed Exports
        DB::table('exports')->insert([
            [
                'id' => 1,
                'export_code' => 'EXP20260605',
                'user_id' => 1,
                'phone' => '02873016868',
                'date_create' => Carbon::parse('2026-06-05 09:30:00'),
                'customer_id' => 1, // Phong Vũ
                'contact_person' => 'Nguyễn Văn An',
                'address' => '264 Nguyễn Thị Minh Khai, Q.3, TP.HCM',
                'note' => 'Xuất sỉ đơn hàng linh kiện số 1',
                'warehouse_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'export_code' => 'EXP20260606',
                'user_id' => 1,
                'phone' => '18006975',
                'date_create' => Carbon::parse('2026-06-06 15:45:00'),
                'customer_id' => 2, // GearVN
                'contact_person' => 'Trần Minh Hoàng',
                'address' => '78-80 Hoàng Hoa Thám, P.12, Q.Tân Bình, TP.HCM',
                'note' => 'Xuất hàng đại lý GearVN tháng 6',
                'warehouse_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 9. Seed Product Export
        DB::table('product_export')->insert([
            // Export 1 to Phong Vũ (RAM Kingston SN 2, SSD Samsung SN 7, ASUS Mainboard SN 13)
            ['id' => 1, 'export_id' => 1, 'product_id' => 1, 'quantity' => 1, 'sn_id' => 2, 'warranty' => '36', 'note' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'export_id' => 1, 'product_id' => 3, 'quantity' => 1, 'sn_id' => 7, 'warranty' => '60', 'note' => null, 'created_at' => now(), 'updated_at' => now()],
            
            // Export 2 to GearVN (RAM Corsair SN 5, SSD Kingston SN 10, Gigabyte VGA SN 15)
            ['id' => 3, 'export_id' => 2, 'product_id' => 2, 'quantity' => 1, 'sn_id' => 5, 'warranty' => '36', 'note' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'export_id' => 2, 'product_id' => 4, 'quantity' => 1, 'sn_id' => 10, 'warranty' => '36', 'note' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'export_id' => 2, 'product_id' => 6, 'quantity' => 1, 'sn_id' => 15, 'warranty' => '36', 'note' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 10. Seed Inventory Lookup (Stock on hand)
        DB::table('inventory_lookup')->insert([
            // RAM Kingston SN 1 (in stock, remaining qty = 1, import_id = 2, warehouse = 1)
            [
                'id' => 1,
                'product_id' => 1,
                'sn_id' => 1,
                'provider_id' => 2,
                'import_date' => Carbon::parse('2026-06-02 14:30:00'),
                'storage_duration' => 6,
                'status' => 1,
                'warranty_date' => Carbon::parse('2029-06-02 14:30:00'),
                'note' => 'Lô hàng mới nguyên seal',
                'remaining_quantity' => 1,
                'import_id' => 2,
                'warehouse_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // RAM Corsair SN 4 (in stock)
            [
                'id' => 2,
                'product_id' => 2,
                'sn_id' => 4,
                'provider_id' => 2,
                'import_date' => Carbon::parse('2026-06-02 14:30:00'),
                'storage_duration' => 6,
                'status' => 1,
                'warranty_date' => Carbon::parse('2029-06-02 14:30:00'),
                'note' => 'RAM Kingston nguyên hộp',
                'remaining_quantity' => 1,
                'import_id' => 2,
                'warehouse_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // SSD Samsung SN 6 (in stock)
            [
                'id' => 3,
                'product_id' => 3,
                'sn_id' => 6,
                'provider_id' => 2,
                'import_date' => Carbon::parse('2026-06-02 14:30:00'),
                'storage_duration' => 6,
                'status' => 1,
                'warranty_date' => Carbon::parse('2031-06-02 14:30:00'),
                'note' => 'SSD tốc độ cao',
                'remaining_quantity' => 1,
                'import_id' => 2,
                'warehouse_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // SSD Kingston SN 9 (in stock)
            [
                'id' => 4,
                'product_id' => 4,
                'sn_id' => 9,
                'provider_id' => 2,
                'import_date' => Carbon::parse('2026-06-02 14:30:00'),
                'storage_duration' => 6,
                'status' => 1,
                'warranty_date' => Carbon::parse('2029-06-02 14:30:00'),
                'note' => 'Tồn kho bán lẻ',
                'remaining_quantity' => 1,
                'import_id' => 2,
                'warehouse_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // ASUS Mainboard SN 12 (in stock)
            [
                'id' => 5,
                'product_id' => 5,
                'sn_id' => 12,
                'provider_id' => 1,
                'import_date' => Carbon::parse('2026-06-01 10:00:00'),
                'storage_duration' => 7,
                'status' => 1,
                'warranty_date' => Carbon::parse('2029-06-01 10:00:00'),
                'note' => 'Mainboard ASUS mới',
                'remaining_quantity' => 1,
                'import_id' => 1,
                'warehouse_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Gigabyte VGA SN 14 (in stock)
            [
                'id' => 6,
                'product_id' => 6,
                'sn_id' => 14,
                'provider_id' => 1,
                'import_date' => Carbon::parse('2026-06-01 10:00:00'),
                'storage_duration' => 7,
                'status' => 1,
                'warranty_date' => Carbon::parse('2029-06-01 10:00:00'),
                'note' => 'Card VGA EAGLE OC',
                'remaining_quantity' => 1,
                'import_id' => 1,
                'warehouse_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 11. Seed Warranty Lookup (Warranty status of sold/exported items)
        DB::table('warranty_lookup')->insert([
            // RAM Kingston SN 2 sold to Phong Vũ (under warranty, warranty: 36 months, status = 1)
            [
                'id' => 1,
                'product_id' => 1,
                'sn_id' => 2,
                'customer_id' => 1,
                'name_warranty' => 'Bảo hành chính hãng',
                'name_status' => 'Bán mới',
                'export_return_date' => Carbon::parse('2026-06-05 09:30:00'),
                'warranty' => 36,
                'status' => 0, // 0: Bảo hành
                'name_expire_date' => '36 tháng kể từ ngày bán',
                'warranty_expire_date' => Carbon::parse('2029-06-05 09:30:00'),
                'warranty_extra' => null,
                'return_date' => null,
                'service_warranty_expired' => null,
                'export_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // SSD Samsung SN 7 sold to Phong Vũ (under warranty, warranty: 60 months)
            [
                'id' => 2,
                'product_id' => 3,
                'sn_id' => 7,
                'customer_id' => 1,
                'name_warranty' => 'Bảo hành VIP',
                'name_status' => 'Bán mới',
                'export_return_date' => Carbon::parse('2026-06-05 09:30:00'),
                'warranty' => 60,
                'status' => 0,
                'name_expire_date' => '60 tháng kể từ ngày bán',
                'warranty_expire_date' => Carbon::parse('2031-06-05 09:30:00'),
                'warranty_extra' => null,
                'return_date' => null,
                'service_warranty_expired' => null,
                'export_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // SSD Kingston SN 10 sold to GearVN
            [
                'id' => 3,
                'product_id' => 4,
                'sn_id' => 10,
                'customer_id' => 2,
                'name_warranty' => 'Bảo hành chính hãng',
                'name_status' => 'Bán mới',
                'export_return_date' => Carbon::parse('2026-06-06 15:45:00'),
                'warranty' => 36,
                'status' => 0,
                'name_expire_date' => '36 tháng kể từ ngày bán',
                'warranty_expire_date' => Carbon::parse('2029-06-06 15:45:00'),
                'warranty_extra' => null,
                'return_date' => null,
                'service_warranty_expired' => null,
                'export_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
