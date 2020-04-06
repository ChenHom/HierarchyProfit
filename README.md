# hierarchy_profit

總代、代理的利潤處理

## setting composer.json
add the setting below to composer.json
```
"repositories": [
    {
        "type": "vcs",
        "url": "git@gitlab.exigodev.com:payment_group/repositories/hierarchy_profit.git"
    }
]
```
## install
```
composer require homchen/hierarchy-profit
```
if required to notify agents when submit the profit strip

add the line below in AppServiceProvider.php
and implement the `ProfitNoticeContract`
```
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(\Hierarchy\Events\ProfitNoticeContract::class,
            {YOUR_NAMESPACE}\{YOUR_IMPLEMENT_CLASS}::class);
    }
```
## publish
```
php artisan vendor:publish --provider "Hierarchy\HierarchyServiceProvider"
```
## methods
* getProfits($coId, $ownerId = '')
* getHierarchyCashFlowsForPaginate($coId, $startTime, $endTime, $ownerId = '')
* getHierarchyCashFlows($coId, $startTime, $endTime, $ownerId = '')
* getStrips($ownerId, $type = '', $startTime = '', $endTime = '')
* getStripsForPaginate($ownerId, $type = '', $startTime = '', $endTime = '')
* profitAccrued($coId, $ownerId, $ownerRole, $order)
* profitSuccess($coId, $ownerId, $ownerRole, $orderId)
* profitReverse($coId, $ownerId, $ownerRole, $orderId, $remark)
* profitDeduction($coId, $ownerId, $role, $profit, $orderId, $type = \Hierarchy\Models\HierarchyProfitRecord::TYPE_RI)
* profitAccumulate($coId, $ownerId, $role, $profit, $orderId, $type = \Hierarchy\Models\HierarchyProfitRecord::TYPE_TI)
* toPaginator($items, $perPage = 15)

## usage
```
$coId = 6;
$startTime = date('Y-m-d 00:00:00');
$endTime = date('Y-m-d 23:59:59');
$ownerId = 1;
$orderId = 1;
$role = 'SA';
$remark = '實質力影響';

dump(HProfit::getProfits($coId)));

dump(HProfit::getStrips($coId));

dump(HProfit::getStripsForPaginate($coId));

dump(HProfit::getHierarchyCashFlowsForPaginator($coId, $startTime, $endTime));

dump(HProfit::getHierarchyCashFlows($coId, $startTime, $endTime));

dump(HProfit::profitAccrued($coId, $ownerId, $role, ['amount' => 200000000, 'bank_name' => '小元銀行', 'bank_account' => '201212201016482', 'bank_account_name' => '陳木寬', 'bank_branch' => '復華分行', 'remark' => '放水果禮盒'])));

dump(HProfit::profitSuccess($coId, $ownerId, $role, $orderId)));

dump(HProfit::profitReverse($coId, $ownerId, $role, $orderId, $remark)));
```