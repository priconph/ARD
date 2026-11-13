<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAqmsScheduleProcessOwners extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aqms_schedule_process_owners', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('aqms_schedule_id');
            $table->integer('process_owner_id');
            // Foreign key
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
        Schema::dropIfExists('aqms_schedule_process_owners');
    }
}
