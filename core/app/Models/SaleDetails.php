<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleDetails extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'price',
        'total',
        'is_return',
        'return_invoice',
        'return_note'
    ];

    protected $casts = [
        'date' => 'date',
        'is_return' => 'boolean',
        'quantity' => 'decimal:3',
        'price' => 'decimal:8',
        'total' => 'decimal:8'
    ];
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
