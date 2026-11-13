<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuditScheduleDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('audit_schedule_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('year');
            $table->string('title');
            $table->string('scope');
            $table->string('reference_stds');
            $table->string('method');
            $table->integer('schedule_type');
            $table->integer('status');
            $table->integer('update_version');
            $table->integer('created_by');
            $table->integer('last_updated_by');
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
        Schema::dropIfExists('audit_schedule_details');
    }
}
