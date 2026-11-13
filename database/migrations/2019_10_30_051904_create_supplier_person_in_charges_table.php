<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSupplierPersonInChargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('supplier_person_in_charges', function (Blueprint $table) {
            $table->bigIncrements('supplier_person_in_charge_id');
            $table->unsignedBigInteger('supplier_audit_id');
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
        Schema::dropIfExists('supplier_person_in_charges');
    }
}
