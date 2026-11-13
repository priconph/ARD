<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAqmsScheduleYears extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aqms_schedule_years', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('aqms_schedule_id');
            $table->string('year');
            $table->date('plan_from')->nullable();
            $table->date('plan_to')->nullable();
            $table->date('actual_from')->nullable();
            $table->date('actual_to')->nullable();
            $table->date('reschedule_from')->nullable();
            $table->date('reschedule_to')->nullable();
            $table->integer('status');
            $table->integer('created_by');
            $table->integer('last_updated_by');
            $table->integer('update_version');
            $table->timestamps();
            
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
        Schema::dropIfExists('aqms_schedule_years');
    }
}
