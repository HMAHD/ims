<?php

namespace App\Models;

use App\Traits\ActionTakenBy;
use App\Traits\UserNotify;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use ActionTakenBy, UserNotify;

    public function sale()
    {
        return $this->hasMany(Sale::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function saleReturns()
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function saleDetails()
    {
        return $this->hasMany(SaleDetails::class);
    }

    public function totalSaleReturnDueAmount()
    {
        return $this->sale->sum('due_amount');
    }

    public function totalSaleDueAmount()
    {
        return $this->saleReturns->sum('due_amount');
    }

    function  totalReceivableAmount()
    {
        $saleAmount   = $this->sale->where('due_amount', '>', 0)->sum('due_amount');
        $returnAmount = $this->saleReturns->where('due_amount', '<', 0)->sum('due_amount');
        return $saleAmount + abs($returnAmount);
    }

    function totalPayableAmount()
    {
        $saleAmount = $this->sale->where('due_amount', '<', 0)->sum('due_amount');

        $returnAmount   = $this->saleReturns->where('due_amount', '>', 0)->sum('due_amount');
        return abs($saleAmount) + $returnAmount;
    }

    /**
     * Get available returns that can be applied to new sales
     */
    public function getAvailableReturns()
    {
        return $this->saleReturns()
            ->where('due_amount', '>', 0)
            ->whereDoesntHave('appliedToSales')
            ->with(['sale', 'details.product'])
            ->get();
    }

    /**
     * Get available due amounts that can be applied to new sales
     */
    public function getAvailableDues()
    {
        return $this->sale()
            ->where('due_amount', '>', 0)
            ->whereDoesntHave('appliedToDues')
            ->get();
    }

    /**
     * Get total available return amount
     */
    public function getTotalAvailableReturnAmount()
    {
        return $this->getAvailableReturns()->sum('due_amount');
    }

    /**
     * Get total available due amount
     */
    public function getTotalAvailableDueAmount()
    {
        return $this->getAvailableDues()->sum('due_amount');
    }

    public function fullname(): Attribute
    {
        return new Attribute(
            get: fn() => $this->name,
        );
    }

    public function mobileNumber(): Attribute
    {
        return new Attribute(
            get: fn() =>  $this->mobile,
        );
    }
}
