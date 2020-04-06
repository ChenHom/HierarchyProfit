<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateHierarchyProfitsTable.
 */
class CreateHierarchyProfitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hierarchy_profits', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('co_id')->comment('股東 id');
            $table->unsignedInteger('owner_id')->comment('該層的 user_id');
            $table->enum('owner_role', ['SA', 'A'])->comment('該層的 user_role');
            $table->decimal('amount', 12,4)->default(0)->comment('總金額');
            $table->timestamps();

            $table->index(['co_id', 'owner_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hierarchy_profits');
    }
}
