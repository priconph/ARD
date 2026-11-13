<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInternalSysPerInChargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('internal_sys_per_in_charges', function (Blueprint $table) {
            $table->bigIncrements('internal_sys_per_in_charge_id');
            $table->unsignedBigInteger('internal_audit_id');
            $table->integer('person_in_charge_id');
            $table->integer('created_by');
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
        Schema::dropIfExists('internal_sys_per_in_charges');
    }
}
