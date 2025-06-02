<?php

namespace App\Models;

use App\Traits\ActionTakenBy;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{

    use ActionTakenBy;

    protected $casts = [
        'applied_returns' => 'array',
        'applied_dues' => 'array',
        'applied_return_amount' => 'decimal:8',
        'applied_due_amount' => 'decimal:8'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function saleDetails()
    {
        return $this->hasMany(SaleDetails::class);
    }

    public function saleReturn()
    {
        return $this->hasOne(SaleReturn::class);
    }

    public function returnApplications()
    {
        return $this->hasMany(SaleReturnApplication::class, 'new_sale_id');
    }

    public function dueApplications()
    {
        return $this->hasMany(SaleDueApplication::class, 'new_sale_id');
    }

    public function appliedFromReturns()
    {
        return $this->hasMany(SaleReturnApplication::class, 'original_sale_return_id');
    }

    public function appliedFromDues()
    {
        return $this->hasMany(SaleDueApplication::class, 'original_sale_id');
    }

    public function appliedToDues()
    {
        return $this->hasMany(SaleDueApplication::class, 'original_sale_id');
    }

    /**
     * Get the final receivable amount after applying returns and dues
     */
    public function getFinalReceivableAmount()
    {
        return $this->receivable_amount + $this->applied_due_amount - $this->applied_return_amount;
    }

    /**
     * Get the total amount with all adjustments
     */
    public function getTotalWithAdjustments()
    {
        return $this->total_price - $this->discount_amount + $this->applied_due_amount - $this->applied_return_amount;
    }
}
