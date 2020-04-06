<?php

namespace Hierarchy\Supports\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Database\Eloquent\Collection getProfits($coId, $ownerId = '')
 * @method static \Illuminate\Database\Eloquent\Collection getStrips($ownerId, $type = '', $startTime = '', $endTime = '')
 * @method static \Illuminate\Pagination\Paginator getStripsForPaginate($ownerId, $type = '', $startTime = '', $endTime = '')
 * @method static \Illuminate\Pagination\Paginator getHierarchyCashFlowsForPaginate($coId, $startTime, $endTime, $ownerId = '')
 * @method static \Illuminate\Database\Eloquent\Collection getHierarchyCashFlows($coId, $startTime, $endTime, $ownerId = '')
 * @method static bool profitAccrued($coId, $ownerId, $ownerRole, $order)
 * @method static bool profitSuccess($coId, $ownerId, $ownerRole, $orderId)
 * @method static bool profitReverse($coId, $ownerId, $ownerRole, $orderId, $remark)
 * @method static bool profitDeduction($coId, $ownerId, $role, $profit, $orderId, $type = \Hierarchy\Models\HierarchyProfitRecord::TYPE_RI)
 * @method static bool profitAccumulate($coId, $ownerId, $role, $profit, $orderId, $type = \Hierarchy\Models\HierarchyProfitRecord::TYPE_TI)
 * @method static \Illuminate\Pagination\Paginator toPaginator($items, $perPage = 15)
 */
class HProfit extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'hp';
    }
}
