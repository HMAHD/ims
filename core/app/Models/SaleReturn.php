<?php

namespace App\Models;

use App\Traits\ActionTakenBy;
use Illuminate\Database\Eloquent\Model;

class SaleReturn extends Model
{
    use ActionTakenBy;

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function details()
    {
        return $this->hasMany(SaleReturnDetails::class);
    }

    public function appliedToSales()
    {
        return $this->hasMany(SaleReturnApplication::class, 'original_sale_return_id');
    }

    /**
     * Get remaining amount that can be applied to new sales
     */
    public function getRemainingApplicableAmount()
    {
        $appliedAmount = $this->appliedToSales()->sum('applied_amount');
        return max(0, $this->due_amount - $appliedAmount);
    }
}
