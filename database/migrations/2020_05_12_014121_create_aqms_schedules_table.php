<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAqmsSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aqms_schedules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('process_id');
            $table->tinyInteger('status')->default(1)->comment = '1=Active; 2=Archived';
            $table->integer('created_by');
            $table->integer('last_updated_by');
            $table->timestamps();

            // Foreign Key
            $table->foreign('process_id')->references('id')->on('aqms_processes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('aqms_schedules');
    }
}
