<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAqmsProcessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aqms_processes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('audit_schedule_id');
            $table->text('process')->nullable();
            $table->integer('no');
            $table->tinyInteger('status')->default(1)->comment = '1=Active; 2=Archived';
            $table->integer('created_by');
            $table->integer('last_updated_by');
            $table->timestamps();

            // Foreign Key
            $table->foreign('audit_schedule_id')->references('id')->on('audit_schedule_details');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('aqms_processes');
    }
}
