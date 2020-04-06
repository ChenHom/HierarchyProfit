<?php

namespace Hierarchy;

use Illuminate\Support\Facades\DB;
use Hierarchy\Models\ProfitStrip;
use Hierarchy\Models\HierarchyProfit;
use Illuminate\Database\Eloquent\Collection;
use Hierarchy\Models\HierarchyProfitRecord;

class HierarchyProfitService
{
    /**
     * @var HierarchyProfit
     */
    private $hierarchyProfit;

    /**
     * @var HierarchyProfitRecord
     */
    private $record;

    /**
     * @var ProfitStrip
     */
    private $profitStrip;

    /**
     * 累加筆數多已扣款筆數 1 筆時, 才能扣款
     */
    public const DEDUCTIBLE = 1;

    public function __construct(
        HierarchyProfit $hierarchyProfit,
        HierarchyProfitRecord $hierarchyProfitRecord,
        ProfitStrip $profitStrip
    ) {
        $this->hierarchyProfit = $hierarchyProfit;
        $this->record = $hierarchyProfitRecord;
        $this->profitStrip = $profitStrip;
    }

    /**
     * 取得代理商底下總代、代理的利潤餘額
     *
     * @param int|string $coId
     * @param int|string $ownerId
     * @return mixed
     */
    public function getProfits($coId, $ownerId = '', $method = 'get')
    {
        $where = [['co_id', '=', $coId]];
        if ($ownerId) {
            $where[] = ['owner_id', '=', $ownerId];
        }
        return $this->hierarchyProfit->where($where)->{$method}();
    }

    /**
     * 取得利潤申請資料
     *
     * @param int|string $ownerId
     * @param string $type
     * @param string $startTime
     * @param string $endTime
     * @return Collection
     */
    public function getStrips($ownerId, $type = '', $startTime = '', $endTime = '')
    {
        $where = [
            ['owner_id', '=', $ownerId]
        ];
        if ($type) {
            $where[] = ['status', '=', $type];
        }
        if ($startTime) {
            $where[] = ['created_at', '>=', $startTime];
        }
        if ($endTime) {
            $where[] = ['created_at', '<=', $endTime];
        }
        return $this->profitStrip->where($where)->orderBy('id', 'desc')->get();
    }

    /**
     * 取得利潤申請資料並轉成分頁
     *
     * @param int|string $ownerId
     * @param string $type
     * @param string $startTime
     * @param string $endTime
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getStripsForPaginate(
        $ownerId,
        $type = '',
        $startTime = '',
        $endTime = ''
    ) {
        return $this->strips($ownerId, $type, $startTime, $endTime, 'paginate');
    }

    /**
     * 取得代理商底下總代、代理的利潤現金簿總表並轉成分頁
     *
     * @param int|string $coId
     * @param string $startTime
     * @param string $endTime
     * @param int|string $ownerId
     * @return Illuminate\Pagination\Paginator
     */
    public function getHierarchyCashFlowsForPaginate(
        $coId,
        $startTime,
        $endTime,
        $ownerId = ''
    ) {
        $paginate = $this->getProfits($coId, $ownerId, 'paginate');
        $profitIds = $ownerId
            ? (array) $ownerId
            : $paginate->getCollection()->pluck('id')->all();
        $this->bindPerDayAmount($paginate->getCollection(), $profitIds, $startTime, $endTime);
        return $paginate;
    }

    /**
     * 取得代理商底下總代、代理的利潤現金簿總表
     *
     * @param int|string $coId
     * @param string $startTime
     * @param string $endTime
     * @param int|string $ownerId
     * @return Collection
     */
    public function getHierarchyCashFlows($coId, $startTime, $endTime, $ownerId = '')
    {
        $hierarchyProfits = $this->getProfits($coId);
        $profitIds = $ownerId
            ? (array) $ownerId
            : $hierarchyProfits->pluck('id')->all();
        return $this->bindPerDayAmount($hierarchyProfits, $profitIds, $startTime, $endTime);
    }

    /**
     * 申請利潤, 預扣金額
     *
     * @param int|string $coId
     * @param int|string $ownerId
     * @param string $ownerRole
     * @param array $order
     * @return ProfitStrip
     * @throws \Throwable|\Exception
     */
    public function profitAccrued($coId, $ownerId, $ownerRole, $order)
    {
        return DB::transaction(function () use ($coId, $ownerId, $ownerRole, $order) {
            return $this->getProfitForLock($coId, $ownerId, $ownerRole)
                ->strip()
                ->create(
                    [
                        'owner_id' => $ownerId,
                        'amount' => $order['amount'],
                        'bank_name' => $order['bank_name'],
                        'bank_account' => $order['bank_account'],
                        'bank_account_name' => $order['bank_account_name'],
                        'bank_branch' => $order['bank_branch'],
                        'remark' => $order['remark'],
                        'status' => $this->profitStrip::ACCRUED_PROFIT,
                    ]
                );
        });
    }

    /**
     * 申請成功, 更新狀態
     *
     * @param int|string $coId
     * @param int|string $ownerId
     * @param string $ownerRole
     * @param int|string $orderId
     * @return bool
     * @throws \Throwable|\Exception
     */
    public function profitSuccess($coId, $ownerId, $ownerRole, $orderId)
    {
        $hierarchy = $this->getProfitForLock($coId, $ownerId, $ownerRole);
        return DB::transaction(function () use ($hierarchy, $orderId) {
            return $this->profitStripModify(
                $hierarchy,
                $orderId,
                $this->profitStrip::RECEIVED_PROFIT
            );
        });
    }

    /**
     * 申請取消/失敗, 沖正利潤
     *
     * @param int|string $coId
     * @param int|string $ownerId
     * @param string $ownerRole
     * @param int|string $orderId
     * @param string $remark
     * @return bool
     * @throws \Throwable|\Exception
     */
    public function profitReverse($coId, $ownerId, $ownerRole, $orderId, $remark)
    {
        $hierarchy = $this->getProfitForLock($coId, $ownerId, $ownerRole);
        return DB::transaction(function () use ($hierarchy, $orderId, $remark) {
            return $this->profitStripModify(
                $hierarchy,
                $orderId,
                $this->profitStrip::FAIL_PROFIT,
                $remark
            );
        });
    }

    /**
     * 扣除利潤
     *
     * @param int|string $coId
     * @param int|string $ownerId
     * @param string $role
     * @param int|float $profit
     * @param int|string $orderId
     * @param string $type
     * @return bool
     */
    public function profitDeduction($coId, $ownerId, $role, $profit, $orderId, $type = HierarchyProfitRecord::TYPE_RI)
    {
        if ($profit == 0) {
            return false;
        }

        return DB::transaction(function () use ($coId, $ownerId, $profit, $orderId, $type, $role) {
            /**
             * @todo 需要檢查是否有加過
             */
            $hierarchyProfit = $this->getProfitForLock($coId, $ownerId, $role);
            if ($this->cancelable($hierarchyProfit, $orderId, $type, $profit)) {
                return $this->alterHierarchyOfProfit(
                    $hierarchyProfit,
                    $orderId,
                    -1 * $profit,
                    str_replace('I', 'D', $type)
                );
            }
            return false;
        });
    }

    /**
     * 累加利潤
     *
     * @param int|string $coId
     * @param int|string $ownerId
     * @param string $role
     * @param int|float $profit
     * @param int|string $orderId
     * @param string $type
     * @return bool
     */
    public function profitAccumulate($coId, $ownerId, $role, $profit, $orderId, $type = HierarchyProfitRecord::TYPE_TI)
    {
        if ($profit == 0) {
            return false;
        }

        /**
         * @todo 上鎖，更新
         */
        return DB::transaction(function () use ($coId, $ownerId, $role, $profit, $orderId, $type) {
            /**
             * @todo 需要檢查是否有加過
             */
            $hierarchyProfit = $this->getProfitForLock($coId, $ownerId, $role);
            if ($this->accumulable($hierarchyProfit, $orderId, $type)) {
                return $this->alterHierarchyOfProfit(
                    $hierarchyProfit,
                    $orderId,
                    $profit,
                    $type
                );
            }
            return false;
        });
    }

    /**
     * 補上每日的金額資料
     *
     * @param \Illuminate\Support\Collection $hierarchyProfits
     * @param array $profitIds
     * @param string $startTime
     * @param string $endTime
     * @return \Illuminate\Support\Collection
     */
    private function bindPerDayAmount($hierarchyProfits, $profitIds, $startTime, $endTime)
    {
        /**
         * @todo 取出累加金額
         */
        $dayAccumulative = $this->getAccumulativeRecord($profitIds, $startTime, $endTime);

        /**
         * @todo 取出已下發(已申請)
         */
        $received = $this->getStripReceivedRecord($profitIds, $startTime, $endTime);

        /**
         * @todo 取出下發沖正(取消申請)
         */
        $failure = $this->getStripFailureRecord($profitIds, $startTime, $endTime);

        /**
         * @todo 取出當日餘額
         */
        $balances = $this->getBalance($profitIds, $startTime, $endTime);

        foreach (dateRange($startTime, $endTime) as $date) {
            $day = $date->format('Y-m-d');
            foreach ($hierarchyProfits as $profit) {
                if (!isset($profit->cashFlow)) {
                    $profit->cashFlow = [];
                }
                /**
                 * @todo 取出前日餘額
                 */
                $cashFlow[$day]['lastDayBalance'] = $this->getLastDayBalance($profit->id, "{$day} 00:00:00");
                $cashFlow[$day]['dayAccumulative'] = $dayAccumulative[$profit->id][$day][0]['record'][0]['amount'] ?? 0;
                $cashFlow[$day]['received'] = $received[$profit->id][$day][0]['record'][0]['amount'] ?? 0;
                $cashFlow[$day]['failure'] = $failure[$profit->id][$day][0]['record'][0]['amount'] ?? 0;
                $cashFlow[$day]['balance'] = $cashFlow[$day]['lastDayBalance']
                    + ($balances[$profit->id][$day][0]['record'][0]['amount'] ?? 0);
                $profit->cashFlow += $cashFlow;
                $cashFlow = [];
            }
        }

        return $hierarchyProfits;
    }

    /**
     *
     * @param int|string $ownerId
     * @param string $type
     * @param string $startTime
     * @param string $endTime
     * @param string $method
     * @return mixed
     */
    private function strips($ownerId, $type = '', $startTime = '', $endTime = '', $method = 'get')
    {
        $where = [
            ['owner_id', '=', $ownerId],
            ['created_at', '>=', $startTime ?: date('Y-m-d 00:00:00')],
            ['created_at', '<=', $endTime ?: date('Y-m-d 23:59:59')]
        ];
        if ($type) {
            $where[] = ['status', '=', $type];
        }
        return $this->profitStrip->where($where)
            ->orderBy('id', 'desc')
            ->{$method}();
    }

    /**
     * 更新申請利潤資料的狀態
     *
     * @param HierarchyProfit $hierarchy
     * @param int|string $orderId
     * @param string $status
     * @param string $remark
     * @return bool
     */
    private function profitStripModify($hierarchy, $orderId, $status, $remark = null)
    {
        if ($this->profitStripValidator($hierarchy, $orderId)) {
            $strip = $hierarchy->strip()->find($orderId);
            $strip->status = $status;
            if ($remark) {
                $strip->remark = $strip->remark . ' ' . $remark;
            }

            return $strip->update();
        }
        return false;
    }

    /**
     * 驗證申請利潤的記錄
     *
     * @param HierarchyProfit $hierarchy
     * @param int|string $orderId
     * @return bool
     */
    private function profitStripValidator($hierarchy, $orderId)
    {
        if (!$hierarchy->strip()->find($orderId)) {
            return false;
        }

        return $this->hasInserted($hierarchy, $orderId, HierarchyProfitRecord::TYPE_I);
    }

    /**
     * 金額是否相等
     *
     * @param float|int $inputAmount
     * @param float|int $dbAmount
     * @return bool
     */
    private function amountValidator($inputAmount, $dbAmount)
    {
        return round($inputAmount, 4) === round($dbAmount, 4);
    }

    /**
     * 累加筆數與退款筆數差值
     *
     * @param int $accCount
     * @param int $dedCount
     * @return int
     */
    private function differenceAccumulatedCountAndDeductCount($accCount, $dedCount)
    {
        return $accCount - $dedCount;
    }

    /**
     * 檢查是否有記錄
     *
     * @param HierarchyProfit $hierarchyProfit
     * @param int $orderId
     * @param string $type
     * @param float|int $amount
     * @return bool
     */
    private function hasInserted($hierarchyProfit, $orderId, $type)
    {
        return $this->record($hierarchyProfit, $orderId, $type)
            ->isNotEmpty();
    }

    /**
     * 取得 record
     *
     * @param HierarchyProfit $profit
     * @param int|string $sourceId
     * @param string $type
     * @return null|\Illuminate\Database\Eloquent\Collection
     */
    private function record($hierarchyProfit, $sourceId, $type)
    {
        return $hierarchyProfit->record()
            ->where([['source_id', '=', $sourceId], ['type', '=', $type]])
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * 是否可累加
     *
     * @param HierarchyProfit $hierarchyProfit
     * @param int $orderId
     * @param string $type
     * @return bool
     */
    private function accumulable($hierarchyProfit, $orderId, $type)
    {
        return !$this->hasInserted($hierarchyProfit, $orderId, $type);
    }

    /**
     * 是否可取消
     *
     * @param HierarchyProfit $hierarchyProfit
     * @param int $orderId
     * @param string $type
     * @param float|int $amount
     * @return bool
     */
    private function cancelable($hierarchyProfit, $orderId, $type, $amount)
    {
        $accumulated = $this->record($hierarchyProfit, $orderId, $type);
        if (is_null($accumulated) || $accumulated->isEmpty()) {
            return false;
        }

        if (!$this->amountValidator(
            $amount,
            $accumulated->first()->diff_amount
                - $accumulated->first()->original_amount
        )) {
            return false;
        }
        $deduct = $this->record(
            $hierarchyProfit,
            $orderId,
            str_replace('I', 'D', $type)
        );
        $differ = $this->differenceAccumulatedCountAndDeductCount(
            $accumulated->count(),
            $deduct->count()
        );
        return $differ === self::DEDUCTIBLE;
    }

    /**
     * 更新 HierarchyProfit 及 新增一筆 Record
     *
     * @param HierarchyProfit $hierarchyProfit
     * @param int $orderId
     * @param int|float $profit
     * @param string $type
     * @return bool
     */
    private function alterHierarchyOfProfit($hierarchyProfit, $orderId, $profit, $type)
    {
        /**
         * @todo 更新 hierarchyProfit 及新增 record 資料
         */
        return $hierarchyProfit->pushUpdateSource([
            'id' => $orderId,
            'type' => $type,
        ])->update([
            'amount' => $hierarchyProfit->amount + $profit,
        ]);
    }

    /**
     * 取出階層的利潤資料
     *
     * @param string|int $coId
     * @param string|int $ownerId
     * @param string $ownerRole
     * @return HierarchyProfit
     */
    private function getProfitForLock($coId, $ownerId, $ownerRole = '')
    {
        $conditions = ['co_id' => $coId, 'owner_id' => $ownerId];
        if ($ownerRole) {
            $conditions['owner_role'] = $ownerRole;
        }
        return $this->hierarchyProfit->lockForUpdate()
            ->firstOrCreate($conditions);
    }

    /**
     * 取得申請失敗的記錄
     *
     * @param array $hierarchyProfitsId
     * @param string $startTime
     * @param string $endTime
     * @return \Illuminate\Support\Collection
     */
    private function getStripFailureRecord($hierarchyProfitsId, $startTime, $endTime)
    {
        return $this->getProfitRecord(
            $hierarchyProfitsId,
            $startTime,
            $endTime,
            $this->record::TYPE_R
        );
    }

    /**
     * 取得成功申請的記錄
     *
     * @param array $hierarchyProfitsId
     * @param string $startTime
     * @param string $endTime
     * @return \Illuminate\Support\Collection
     */
    private function getStripReceivedRecord($hierarchyProfitsId, $startTime, $endTime)
    {
        return $this->getProfitRecord(
            $hierarchyProfitsId,
            $startTime,
            $endTime,
            $this->record::TYPE_I
        );
    }

    /**
     * 取得申請的記錄
     *
     * @param int|array $hierarchyProfitsId
     * @param string $startTime
     * @param string $endTime
     * @param string|array $type
     * @return \Illuminate\Support\Collection
     */
    private function getProfitRecord($hierarchyProfitsId, $startTime, $endTime, $type)
    {
        return $this->hierarchyProfit->with(['record' => function ($query) use ($type, $startTime, $endTime) {
            if (is_string($type)) {
                $query->where('type', $type);
            } else if (is_array($type)) {
                $query->whereIn('type', $type);
            }

            $query->select([
                'hierarchy_profits_id',
                DB::raw('sum(diff_amount - original_amount) as amount'),
                DB::raw('date(created_at) as date')
            ])->where([
                ['created_at', '>=', $startTime],
                ['created_at', '<=', $endTime]
            ])->groupBy('hierarchy_profits_id', DB::raw('date(created_at)'));
        }])->find($hierarchyProfitsId, ['id'])
            ->groupBy(['id', 'record.*.date']);
    }

    /**
     * 取當日累加金額
     *
     * @param int|array $hierarchyProfitsId
     * @param string $startTime
     * @param string $endTime
     * @return \Illuminate\Support\Collection
     */
    private function getAccumulativeRecord($hierarchyProfitsId, $startTime, $endTime)
    {
        return $this->getProfitRecord($hierarchyProfitsId, $startTime, $endTime, [
            $this->record::TYPE_TI,
            $this->record::TYPE_RI,
            $this->record::TYPE_TD,
            $this->record::TYPE_RD
        ]);
    }

    /**
     * 當日餘額
     *
     * @param int|array $hierarchyProfitsId
     * @param string $startTime
     * @param string $endTime
     * @return \Illuminate\Support\Collection
     */
    private function getBalance($hierarchyProfitsId, $startTime, $endTime)
    {
        return $this->getProfitRecord(
            $hierarchyProfitsId,
            $startTime,
            $endTime,
            array_keys(__('hierarchy::hierarchy_profit.type.record'))
        );
    }

    /**
     * 取得傳入日期之前的最後的餘額
     *
     * @param int $hierarchyProfitsId
     * @param string $lastDay
     * @return float
     */
    private function getLastDayBalance($hierarchyProfitsId, $lastDay)
    {
        return $this->record->where([
            ['hierarchy_profits_id', '=', $hierarchyProfitsId],
            ['created_at', '<', $lastDay]
        ])->orderBy('id', 'desc')->value('diff_amount') ?? 0;
    }
}
