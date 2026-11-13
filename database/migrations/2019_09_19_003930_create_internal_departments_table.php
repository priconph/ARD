<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInternalDepartmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('internal_departments', function (Blueprint $table) {
            $table->bigIncrements('internal_department_id');
            $table->unsignedBigInteger('internal_audit_id');
            $table->integer('responsible_department_id');
            $table->integer('internal_department_stat');
            $table->integer('created_by');
            $table->integer('last_updated_by');
            $table->integer('update_version');
            $table->timestamps();

            // Foreign key
            $table->foreign('internal_audit_id')->references('internal_audit_id')->on('internal_audits');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('internal_departments');
    }
}
