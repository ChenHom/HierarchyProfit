<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateProfitStripsTable.
 */
class CreateProfitStripsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('profit_strips', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('hierarchy_profits_id');
            $table->unsignedInteger('owner_id');
            $table->decimal('amount', 10, 4)->comment('申請金額');
            $table->string('bank_name')->comment('銀行名稱');
            $table->string('bank_account')->comment('銀行帳號');
            $table->string('bank_account_name')->comment('銀行戶名');
            $table->string('bank_branch')->default('')->comment('銀行支行');
            $table->enum('status', ['A', 'R', 'F'])->default('A')->comment('狀態 A:預扣利潤 R:利潤已入帳 F:申請失敗/取消(利潤沖正)');
            $table->text('operator')->comment('操作者');
            $table->string('remark')->nullable()->comment('備註');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('profit_strips');
    }
}
