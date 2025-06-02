<?php

namespace App\Models;

use App\Traits\ActionTakenBy;
use Illuminate\Database\Eloquent\Model;

class SaleDueApplication extends Model
{
    use ActionTakenBy;

    protected $fillable = [
        'new_sale_id',
        'original_sale_id',
        'customer_id',
        'applied_amount',
        'note'
    ];

    protected $casts = [
        'applied_amount' => 'decimal:8'
    ];

    public function newSale()
    {
        return $this->belongsTo(Sale::class, 'new_sale_id');
    }

    public function originalSale()
    {
        return $this->belongsTo(Sale::class, 'original_sale_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
