<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStandardClauseContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('standard_clause_contents', function (Blueprint $table) {
            $table->bigIncrements('standard_clause_content_id');
            $table->string('standard_clause_content_name');
            $table->integer('is_header');
            $table->integer('standard_clause_content_stat');
            $table->unsignedBigInteger('standard_clause_id');
            $table->integer('created_by');
            $table->integer('last_updated_by');
            $table->integer('update_version');
            $table->timestamps();

            $table->foreign('standard_clause_id')->references('standard_clause_id')->on('standard_clauses');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('standard_clause_contents');
    }
}
