<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSupplierDepartmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('supplier_departments', function (Blueprint $table) {
            $table->bigIncrements('supplier_department_id');
            $table->unsignedBigInteger('supplier_audit_id');
            $table->integer('responsible_department_id');
            $table->integer('supplier_department_stat');
            $table->integer('created_by');
            $table->integer('last_updated_by');
            $table->integer('update_version');
            $table->timestamps();

            // Foreign key
            $table->foreign('supplier_audit_id')->references('supplier_audit_id')->on('supplier_audits');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('supplier_departments');
    }
}
