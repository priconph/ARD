<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTuvDepartmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tuv_departments', function (Blueprint $table) {
            $table->bigIncrements('tuv_department_id');
            $table->unsignedBigInteger('tuv_audit_id');
            $table->integer('responsible_department_id');
            $table->integer('tuv_department_stat');
            $table->integer('created_by');
            $table->integer('last_updated_by');
            $table->integer('update_version');
            $table->timestamps();

            // Foreign key
            $table->foreign('tuv_audit_id')->references('tuv_audit_id')->on('tuv_audits');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tuv_departments');
    }
}
