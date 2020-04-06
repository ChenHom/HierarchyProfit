<?php

namespace Hierarchy\Tests\Cases;

use Hierarchy\Models\HierarchyProfitRecord;
use Hierarchy\Tests\TestCase;
use Hierarchy\Supports\Facades\HProfit;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SAOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakers = factory(
            config('dummyClassForOrder'),
            'Order.SA',
            mt_rand(1, 20)
        )->make();
    }

    /**
     * @test
     * @return void
     */
    public function test_profitAccumulate_傳入代收單_回傳True()
    {
        $actual = true;

        foreach ($this->fakers as $fake) {
            if ($fake->sa_profit == 0) {
                $isZero[] = $fake;
            } else {
                $notZero[] = $fake;
            }
            $actual = HProfit::profitAccumulate(
                static::$coId,
                static::$ownerId,
                static::$ownerRole,
                $fake->sa_profit,
                $fake->id
            ) && $actual;
        }

        $this->assertTrue($actual);
        foreach ($notZero as $fake) {
            $this->assertDatabaseHas('hierarchy_profit_records', [
                'source_id' => $fake->id,
                'type' => \Hierarchy\Models\HierarchyProfitRecord::TYPE_TI,
            ]);
        }
        foreach ($isZero ?? [] as $fake) {
            $this->assertDatabaseMissing('hierarchy_profit_records', [
                'source_id' => $fake->id,
                'type' => \Hierarchy\Models\HierarchyProfitRecord::TYPE_TI,
            ]);
        }
    }

    /**
     * @test
     * @return void
     */
    public function test_profitAccumulate_傳入0元代收單_回傳False()
    {
        $orderId = 1;
        $fakerAmount = 0;

        $actual = HProfit::profitAccumulate(
            static::$coId,
            static::$ownerId,
            static::$ownerRole,
            $fakerAmount,
            $orderId
        );

        $this->assertFalse($actual);
        $this->assertDatabaseMissing('hierarchy_profit_records', [
            'source_id' => $orderId,
            'type' => \Hierarchy\Models\HierarchyProfitRecord::TYPE_TI,
        ]);
    }

    /**
     * @test
     * @return void
     */
    public function test_profitAccumulate_傳入重複代收單_回傳False()
    {
        $orderId = 1;
        $amount = 1;

        $actual = HProfit::profitAccumulate(
            static::$coId,
            static::$ownerId,
            static::$ownerRole,
            $amount,
            $orderId
        );
        $this->assertTrue($actual);
        $actual = HProfit::profitAccumulate(
            static::$coId,
            static::$ownerId,
            static::$ownerRole,
            $amount,
            $orderId
        );
        $this->assertFalse($actual);
    }

    /**
     * @test 測試 profitDeduction_傳入失敗的代付單_傳回True
     *
     * @return void
     */
    public function test_profitDeduction_傳入代付單_回傳True()
    {
        /** arrange */
        foreach ($this->fakers as $fake) {
            if (HProfit::profitAccumulate(
                static::$coId,
                static::$ownerId,
                static::$ownerRole,
                $fake->sa_profit,
                $fake->id,
                \Hierarchy\Models\HierarchyProfitRecord::TYPE_RI
            )) {
                $isInserted[] = $fake;
            } else {
                $notInsert[] = $fake;
            };
        }

        /** act */
        foreach ($this->fakers as $fake) {
            if (HProfit::profitDeduction(
                static::$coId,
                static::$ownerId,
                static::$ownerRole,
                $fake->sa_profit,
                $fake->id
            )) {
                $hasDeducted[] = $fake;
            } else {
                $notDeduct[] = $fake;
            }
        }

        /** assert */
        $this->assertEquals(count($hasDeducted), count($isInserted));
        $this->assertEquals(count($notDeducted ?? []), count($notInsert ?? []));
        foreach ($isInserted as $fake) {
            $this->assertDatabaseHas('hierarchy_profit_records', [
                'source_id' => $fake->id,
                'type' => \Hierarchy\Models\HierarchyProfitRecord::TYPE_RI,
            ]);
            $this->assertDatabaseHas('hierarchy_profit_records', [
                'source_id' => $fake->id,
                'type' => \Hierarchy\Models\HierarchyProfitRecord::TYPE_RD,
            ]);
        }

        foreach ($notInsert ?? [] as $fake) {
            $this->assertDatabaseMissing('hierarchy_profit_records', [
                'source_id' => $fake->id,
                'type' => \Hierarchy\Models\HierarchyProfitRecord::TYPE_RI,
            ]);
            $this->assertDatabaseMissing('hierarchy_profit_records', [
                'source_id' => $fake->id,
                'type' => \Hierarchy\Models\HierarchyProfitRecord::TYPE_RD,
            ]);
        }
    }

    /**
     * @test 測試 profitDeduction_傳入失敗的代付單_傳回True
     *
     * @return void
     */
    public function test_profitDeduction_傳入錯誤代付單_回傳False()
    {
        $orderId = 1;
        $fakerId = -1;
        $amount = 10;
        HProfit::profitAccumulate(
            static::$coId,
            static::$ownerId,
            static::$ownerRole,
            $amount,
            $orderId,
            HierarchyProfitRecord::TYPE_RI
        );

        $actual = HProfit::profitDeduction(
            static::$coId,
            static::$ownerId,
            static::$ownerRole,
            $amount,
            $fakerId
        );

        /** assert */
        $this->assertFalse($actual);
        $this->assertDatabaseMissing('hierarchy_profit_records', [
            'source_id' => $fakerId,
            'type' => \Hierarchy\Models\HierarchyProfitRecord::TYPE_RD,
        ]);
    }

    /**
     * @test 測試 profitDeduction_傳入失敗的代付單_傳回True
     *
     * @return void
     */
    public function test_profitDeduction_傳入0元代付單_回傳False()
    {
        $orderId = 1;
        $amount = 10;
        $fakerAmount = 0;
        HProfit::profitAccumulate(
            static::$coId,
            static::$ownerId,
            static::$ownerRole,
            $amount,
            $orderId,
            HierarchyProfitRecord::TYPE_RI
        );

        $actual = HProfit::profitDeduction(
            static::$coId,
            static::$ownerId,
            static::$ownerRole,
            $fakerAmount,
            $orderId
        );

        /** assert */
        $this->assertFalse($actual);
        $this->assertDatabaseMissing('hierarchy_profit_records', [
            'source_id' => $orderId,
            'type' => \Hierarchy\Models\HierarchyProfitRecord::TYPE_RD,
        ]);
    }

    /**
     * @test 測試 profitDeduction_傳入失敗的代付單_傳回True
     *
     * @return void
     */
    public function test_profitDeduction_傳入錯誤金額代付單_回傳False()
    {
        $orderId = 1;
        $amount = 10;
        $fakerAmount = 20;
        HProfit::profitAccumulate(
            static::$coId,
            static::$ownerId,
            static::$ownerRole,
            $amount,
            $orderId,
            HierarchyProfitRecord::TYPE_RI
        );

        $actual = HProfit::profitDeduction(
            static::$coId,
            static::$ownerId,
            static::$ownerRole,
            $fakerAmount,
            $orderId
        );

        /** assert */
        $this->assertFalse($actual);
        $this->assertDatabaseMissing('hierarchy_profit_records', [
            'source_id' => $orderId,
            'type' => \Hierarchy\Models\HierarchyProfitRecord::TYPE_RD,
        ]);
    }
}
