<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAqmsProcessOwnersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aqms_process_owners', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('aqms_schedule_id');
            $table->unsignedBigInteger('process_owner_id');
            $table->timestamps();

            // Foreign Key
            $table->foreign('aqms_schedule_id')->references('id')->on('aqms_schedules');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('aqms_process_owners');
    }
}
