<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomerDepartmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_departments', function (Blueprint $table) {
            $table->bigIncrements('customer_department_id');
            $table->unsignedBigInteger('customer_audit_id');
            $table->integer('responsible_department_id');
            $table->integer('customer_department_stat');
            $table->integer('created_by');
            $table->integer('last_updated_by');
            $table->integer('update_version');
            $table->timestamps();

            // Foreign key
            $table->foreign('customer_audit_id')->references('customer_audit_id')->on('customer_audits');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_departments');
    }
}
