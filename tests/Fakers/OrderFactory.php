<?php

use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Model;

$factory->define(
    $dummyClassForOrder = get_class(
        new class extends Model {}
    ),
    function (Faker $faker) {
        $paymentAmount = $faker->numberBetween(0, 5000);
        $baseFee = $faker->randomFloat(2, 0.1, 2.0);
        $saFee = $baseFee + $faker->randomFloat(2, 0.1, 2.0);
        $aFee = $saFee + $faker->randomFloat(2, 0.1, 2.0);
        $calc = [
            'sa_profit' => round($paymentAmount * ($aFee - $saFee) / 100, 4),
        ];
        return $calc + ['id' => mt_rand(100, 59999)];
    },
    'Order.SA'
);

config(compact('dummyClassForOrder'));
