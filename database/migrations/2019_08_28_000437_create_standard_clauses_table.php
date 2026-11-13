<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStandardClausesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('standard_clauses', function (Blueprint $table) {
            $table->bigIncrements('standard_clause_id');
            $table->string('standard_clause_name');
            $table->integer('standard_clause_stat');
            $table->integer('created_by');
            $table->integer('last_updated_by');
            $table->integer('update_version');
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
        Schema::dropIfExists('standard_clauses');
    }
}
