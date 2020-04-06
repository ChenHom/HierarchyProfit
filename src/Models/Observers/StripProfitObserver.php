<?php

namespace Hierarchy\Models\Observers;

use Hierarchy\Events\ProfitNoticeContract;
use Illuminate\Support\Arr;
use Hierarchy\Models\ProfitStrip;
use Hierarchy\Models\HierarchyProfitRecord;
use Hierarchy\Supports\Traits\CurrentUserTrait;

class StripProfitObserver
{
    use CurrentUserTrait;

    protected $recordType = [
        ProfitStrip::ACCRUED_PROFIT => HierarchyProfitRecord::TYPE_I,
        ProfitStrip::FAIL_PROFIT => HierarchyProfitRecord::TYPE_R
    ];

    /**
     * @param ProfitStrip $model
     * @return void
     */
    public function creating($model)
    {
        $model->operator = json_encode($this->currentLoginUser());
    }

    /**
     * @param ProfitStrip $model
     * @return void
     */
    public function saved($model)
    {
        if ($type = Arr::get($this->recordType, $model->status)) {
            $amount = $model->amount;
            if ($type === HierarchyProfitRecord::TYPE_I) {
                $amount *= -1;
                $this->sendNotification($model);
            }

            $model->hierarchyProfit->pushUpdateSource([
                'id' => $model->id,
                'type' => $type
            ])->update([
                'amount' => $model->hierarchyProfit->amount + $amount
            ]);
        }
    }

    /**
     * 發送利潤申請通知
     *
     * @param ProfitStrip $model
     * @return void
     */
    private function sendNotification($model)
    {
        if ($this->ifNotificationNeeded()) {
            broadcast(app(ProfitNoticeContract::class, [
                'coId' => $model->hierarchyProfit->co_id,
                'ownerId' => $model->hierarchyProfit->owner_id,
                'orderId' => $model->id
            ]));
        }
    }

    /**
     * 檢查是否有綁定 ProfitNoticeContract 的實作類別
     *
     * @return bool
     */
    private function ifNotificationNeeded()
    {
        return isset(app()->getBindings()[ProfitNoticeContract::class]);
    }
}
