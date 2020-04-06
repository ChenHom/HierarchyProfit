<?php

namespace Hierarchy\Models;

use Illuminate\Database\Eloquent\Model;
use Hierarchy\Models\Observers\HierarchyProfitObserver;

/**
 * Class HierarchyProfit.
 *
 * @package namespace Hierarchy\Models;
 */
class HierarchyProfit extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['co_id', 'owner_id', 'owner_role', 'amount',];

    /**
     * 更新或新增的來源資料
     *
     * @var array
     */
    private $updateSource = [];

    protected static function boot()
    {
        parent::boot();
        self::observe(new HierarchyProfitObserver);
    }

    public function record()
    {
        return $this->hasMany(HierarchyProfitRecord::class, 'hierarchy_profits_id');
    }

    public function strip()
    {
        return $this->hasMany(ProfitStrip::class, 'hierarchy_profits_id');
    }

    /**
     * 要傳至 record 的來源資料
     *
     * @param array $updateSource ['id' => 來源 id, 'type' => 來源類型]
     * @return void
     */
    public function pushUpdateSource($updateSource)
    {
        $this->updateSource = $updateSource;
        return $this;
    }

    /**
     * 取出來源資料
     *
     * @return array ['id' => 來源 id, 'type' => 來源類型]
     */
    public function pullUpdateSource()
    {
        return $this->updateSource;
    }
}
