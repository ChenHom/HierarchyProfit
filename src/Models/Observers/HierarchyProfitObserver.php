<?php

namespace Hierarchy\Models\Observers;

use Illuminate\Support\Arr;
use Hierarchy\Models\HierarchyProfit;
use Hierarchy\Supports\Traits\CurrentUserTrait;

class HierarchyProfitObserver
{
    use CurrentUserTrait;

    /**
     * @param HierarchyProfit $model
     * @return void
     */
    public function saved($model)
    {
        $diffAmount = Arr::get($model->getChanges(), 'amount');
        if (!is_null($diffAmount)) {
            $source = $model->pullUpdateSource();
            $originalAmount = $model->getOriginal('amount', 0);

            $model->record()->create([
                'source_id' => $source['id'],
                'type' => $source['type'],
                'original_amount' => $originalAmount,
                'diff_amount' => $diffAmount,
                'operator' => json_encode($this->currentLoginUser())
            ]);
        }
    }
}
