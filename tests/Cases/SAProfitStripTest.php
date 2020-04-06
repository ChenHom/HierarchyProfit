<?php

namespace Hierarchy\Tests;

use Hierarchy\Tests\TestCase;
use Hierarchy\Supports\Facades\HProfit;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SAProfitStripTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setStrip();
    }

    /**
     * @test
     * @return void
     */
    public function test_申請利潤後_現金簿要出現預扣()
    {
        /** arrange */
        $this->profitAccrued_傳入申請利潤的資料_結果為True();
        $this->profitSuccess_利潤申請成功_結果為True();
        $cashFlow = $this->test_getProfitCashFlows_傳入代理商id及日期範圍_傳回每日現金簿();

        /** assert */
        $expected = ['date' => date('Y-m-d'), 'received' => -1 * $this->strip()->amount];

        $this->assertArrayHasKey($expected['date'], $cashFlow->first()->cashFlow);
        $this->assertEquals($expected['received'], $cashFlow->first()->cashFlow[$expected['date']]['received']);
    }
    /**
     * @test
     * @return void
     */
    public function test_取消利潤申請後_現金簿要出現沖正()
    {
        /** arrange */
        $this->profitAccrued_傳入申請利潤的資料_結果為True();
        $this->profitReverse_取消利潤申請_結果為True();
        $cashFlow = $this->test_getProfitCashFlows_傳入代理商id及日期範圍_傳回每日現金簿();

        /** assert */
        $expected = ['date' => date('Y-m-d'), 'failure' => $this->strip()->amount];

        $this->assertArrayHasKey($expected['date'], $cashFlow->first()->cashFlow);
        $this->assertEquals($expected['failure'], $cashFlow->first()->cashFlow[$expected['date']]['failure']);
    }

    /**
     * @test
     * @return void
     */
    public function test_profitSuccess_傳入錯誤資料去更改利潤成功_結果為False()
    {
        /** act */
        $fakerId = 99;
        $actual = HProfit::profitSuccess(static::$coId, static::$ownerId, static::$ownerRole, $fakerId);

        /** assert */
        $this->assertFalse($actual);
        $this->assertDatabaseMissing(
            'profit_strips',
            ['id' => $fakerId, 'status' => \Hierarchy\Models\ProfitStrip::RECEIVED_PROFIT]
        );
    }

    /**
     * @test
     * @return void
     */
    public function profitAccrued_傳入申請利潤的資料_結果為True()
    {
        /** arrange */
        $actual = $this->strip();

        /** assert */
        $this->assertNotEmpty($actual);
        $this->assertDatabaseHas(
            'profit_strips',
            ['id' => $actual->id, 'status' => \Hierarchy\Models\ProfitStrip::ACCRUED_PROFIT]
        );
        $this->assertDatabaseHas(
            'hierarchy_profit_records',
            ['source_id' => $actual->id, 'type' => \Hierarchy\Models\HierarchyProfitRecord::TYPE_I]
        );
    }

    /**
     * @test
     * @return void
     */
    public function profitReverse_取消利潤申請_結果為True()
    {
        /** act */
        $remark = 'test Reverse';
        $actual = HProfit::profitReverse(static::$coId, static::$ownerId, static::$ownerRole, $this->strip()->id, $remark);

        /** assert */
        $this->assertTrue($actual);
        $this->assertDatabaseHas(
            'profit_strips',
            [
                'id' => $this->strip()->id,
                'status' => \Hierarchy\Models\ProfitStrip::FAIL_PROFIT,
                'remark' => $this->strip()->remark . ' ' . $remark
            ]
        );
        $this->assertDatabaseHas(
            'hierarchy_profit_records',
            ['source_id' => $this->strip()->id, 'type' => \Hierarchy\Models\HierarchyProfitRecord::TYPE_R]
        );
    }

    /**
     * @test
     * @return void
     */
    public function profitSuccess_利潤申請成功_結果為True()
    {
        /** act */
        $actual = HProfit::profitSuccess(static::$coId, static::$ownerId, static::$ownerRole, $this->strip()->id);

        /** assert */
        $this->assertTrue($actual);
        $this->assertDatabaseHas(
            'profit_strips',
            ['id' => $this->strip()->id, 'status' => \Hierarchy\Models\ProfitStrip::RECEIVED_PROFIT]
        );
    }

    /**
     * @test
     * @return void
     */
    public function test_getProfits_傳入代理商id_傳回代理商底下總代及代理的利潤餘額總表()
    {
        /** arrange */
        /** act */
        $actual = HProfit::getProfits(static::$coId, static::$ownerId);
        /** assert */
        $expected = [
            "id" => '',
            "co_id" => static::$coId,
            "owner_id" => static::$ownerId,
            "owner_role" => static::$ownerRole,
            "amount" => '',
            "created_at" => '',
            "updated_at" => '',
        ];

        foreach ($actual as $profit) {
            foreach ($profit->getAttributes() as $key => $value) {
                $this->assertArrayHasKey($key, $expected);
            }
            $this->assertEquals($expected['co_id'], $profit->co_id);
            $this->assertEquals($expected['owner_id'], $profit->owner_id);
            $this->assertEquals($expected['owner_role'], $profit->owner_role);
        }
    }

    /**
     * @test
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function test_getProfitCashFlows_傳入代理商id及日期範圍_傳回每日現金簿()
    {
        /** arrange */
        $startTime = date('Y-m-d 00:00:00');
        $endTime = date('Y-m-d 23:59:59');
        $dateRange = dateRange($startTime, $endTime);

        /** act */
        $actual = HProfit::getHierarchyCashFlows(static::$coId, $startTime, $endTime);
        $expected = [
            'lastDayBalance',
            'dayAccumulative',
            'received',
            'failure',
            'balance'
        ];
        /** assert */
        $nextDayBalance = null;
        foreach ($dateRange as $dateObject) {
            $date = $dateObject->format('Y-m-d');
            $yesterdayBalance = $dateObject->modify('-1 day')->format('Y-m-d');
            foreach ($actual as $profit) {
                $this->assertArrayHasKey($date, $profit->cashFlow);
                foreach ($expected as $key) {
                    $this->assertArrayHasKey($key, $profit->cashFlow[$date]);
                }
                $this->assertEquals(
                    (float) $profit->cashFlow[$date]['balance'],
                    $profit->cashFlow[$date]['dayAccumulative'] +
                        $profit->cashFlow[$date]['received'] +
                        $profit->cashFlow[$date]['failure'],
                    0.00001
                );
                if (is_null($nextDayBalance)) {
                    $nextDayBalance[$date][$profit->id] = $profit->cashFlow[$date][$key];
                } else {
                    if (isset($nextDayBalance[$yesterdayBalance][$profit->id])) {
                        $this->assertEqualsWithDelta(
                            $nextDayBalance[$yesterdayBalance][$profit->id],
                            (float) $profit->cashFlow[$date]['lastDayBalance'],
                            0.00001
                        );
                    }
                }
            }
        }
        return $actual;
    }
}
