<?php

namespace Hierarchy\Models;

use Illuminate\Database\Eloquent\Model;
use Hierarchy\Models\Observers\StripProfitObserver;

/**
 * Class ProfitStrip.
 *
 * @package namespace Hierarchy\Models
 */
class ProfitStrip extends Model
{
    /**
     * 預扣利潤
     */
    const ACCRUED_PROFIT = 'A';

    /**
     * 利潤已入帳
     */
    const RECEIVED_PROFIT = 'R';

    /**
     * 申請失敗/取消利潤
     */
    const FAIL_PROFIT = 'F';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['hierarchy_profits_id', 'owner_id', 'amount', 'bank_name', 'bank_account', 'bank_account_name', 'bank_branch', 'status', 'operator', 'remark',];

    protected static function boot()
    {
        parent::boot();
        static::observe(new StripProfitObserver);
    }

    public function hierarchyProfit()
    {
        return $this->belongsTo(HierarchyProfit::class, 'hierarchy_profits_id', 'id');
    }
}
