<?php

namespace Hierarchy\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class HierarchyProfitRecord.
 *
 * @package namespace Hierarchy\Models
 */
class HierarchyProfitRecord extends Model
{
    /**
     * TI:代收利潤累加
     */
    const TYPE_TI = 'TI';

    /**
     * RI:代付利潤累加
     */
    const TYPE_RI = 'RI';

    /**
     * TD:代收利潤退回
     */
    const TYPE_TD = 'TD';

    /**
     * RD:代付利潤退回
     */
    const TYPE_RD = 'RD';

    /**
     * 利潤扣款
     */
    const TYPE_I = 'I';

    /**
     * 利潤取消沖正
     */
    const TYPE_R = 'R';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['hierarchy_profits_id', 'source_id', 'type', 'original_amount', 'diff_amount', 'operator',];

    public function hierarchyProfit()
    {
        return $this->belongsTo(HierarchyProfit::class, 'hierarchy_profits_id');
    }
}
