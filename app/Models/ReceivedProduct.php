<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceivedProduct extends Model
{
    use HasFactory; // Đảm bảo rằng model có thể sử dụng factory nếu cần

    // Định nghĩa tên bảng
    protected $table = 'received_products';

    // Các trường có thể gán (mass assignable)
    protected $fillable = [
        'reception_id',
        'product_id',
        'quantity',
        'serial',
        'status',
        'note',
    ];

    // Nếu bảng có các cột timestamps (created_at, updated_at)
    public $timestamps = true;
    public function reception()
    {
        return $this->belongsTo(Receiving::class, 'reception_id');
    }

    /**
     * Relationship: ReceivedProduct belongs to Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}