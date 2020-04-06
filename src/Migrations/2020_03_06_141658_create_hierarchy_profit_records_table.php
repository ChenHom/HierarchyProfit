<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateHierarchyProfitRecordsTable.
 */
class CreateHierarchyProfitRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hierarchy_profit_records', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('hierarchy_profits_id');
            $table->unsignedInteger('source_id')->comment('來源 id');
            $table->enum('type', ['TI', 'RI', 'TD', 'RD', 'I', 'R'])->default('TI')->comment('type: TI:代收利潤累加, RI:代付利潤累加, TD:代收利潤退回, RD:代付利潤退回, I:申請利潤, R:申請失敗/取消(沖正)');
            $table->decimal('original_amount', 12,4)->default(0)->comment('原先金額');
            $table->decimal('diff_amount', 12,4)->default(0)->comment('變更後金額');
            $table->text('operator')->comment('操作者');
            $table->timestamps();

            $table->index(['hierarchy_profits_id', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hierarchy_profit_records');
    }
}
