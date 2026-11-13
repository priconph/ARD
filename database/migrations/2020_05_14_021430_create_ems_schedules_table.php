<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmsSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ems_schedules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('process_id');
            $table->tinyInteger('status')->default(1)->comment = '1=Active; 2=Archived';
            $table->integer('created_by');
            $table->integer('last_updated_by');
            $table->timestamps();

            // Foreign Key
            $table->foreign('process_id')->references('id')->on('ems_processes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ems_schedules');
    }
}
