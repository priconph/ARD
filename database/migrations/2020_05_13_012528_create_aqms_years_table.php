<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAqmsYearsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aqms_years', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('aqms_schedule_id');
            $table->integer('year');
            $table->string('start_plan', 25)->nullable();
            $table->string('end_actual', 25)->nullable();
            $table->string('start_actual', 25)->nullable();
            $table->string('end_reschedule', 25)->nullable();
            $table->string('start_reschedule', 25)->nullable();
            $table->string('end_plan')->nullable();
            $table->integer('status');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('last_updated_by');
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
        Schema::dropIfExists('aqms_years');
    }
}
