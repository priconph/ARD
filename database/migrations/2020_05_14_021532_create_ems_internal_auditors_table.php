<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmsInternalAuditorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ems_internal_auditors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ems_schedule_id');
            $table->unsignedBigInteger('internal_auditor_id');
            $table->timestamps();

            // Foreign Key
            $table->foreign('ems_schedule_id')->references('id')->on('ems_schedules');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ems_internal_auditors');
    }
}
