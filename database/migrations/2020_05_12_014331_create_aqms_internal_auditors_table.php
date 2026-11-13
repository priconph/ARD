<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAqmsInternalAuditorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aqms_internal_auditors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('aqms_schedule_id');
            $table->unsignedBigInteger('internal_auditor_id');
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
        Schema::dropIfExists('aqms_internal_auditors');
    }
}
