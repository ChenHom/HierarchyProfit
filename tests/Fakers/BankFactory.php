<?php

use Faker\Generator as Faker;
use Hierarchy\Models\ProfitStrip;

$factory->define(ProfitStrip::class, function (Faker $faker) {
    return [
        'amount' => $faker->numberBetween(10, 50000),
        'bank_name' => $faker->randomElement(['中國銀行', '民生銀行', '建設銀行', '農業銀行']),
        'bank_account' => '201212201016482',
        'bank_account_name' => $faker->randomElement(['陳木寬', '尤恕叚', '雋希岸']),
        'bank_branch' => $faker->randomElement(['復華分行', '世華分行']),
        'remark' => $faker->randomElement(['水果禮盒', '花束', '茶葉罐'])
    ];
});
