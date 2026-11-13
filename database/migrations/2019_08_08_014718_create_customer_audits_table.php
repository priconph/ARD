<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomerAuditsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_audits', function (Blueprint $table) {
            $table->bigIncrements('customer_audit_id');
            $table->string('customer_name');
            $table->date('audit_date_from');
            $table->date('audit_date_to');
            $table->string('auditors');
            $table->string('item_no')->nullable();
            $table->date('deadline_of_submission');
            $table->integer('deadline_of_submission_days');
            $table->date('actual_date_of_submission')->nullable();
            $table->integer('sending_stat');
            $table->integer('email_stat');
            $table->integer('in_batch');
            $table->text('process');
            $table->text('evaluation_item');
            $table->text('classification');
            $table->text('statement_of_findings')->nullable();
            $table->text('illustration')->nullable();
            $table->string('img_illustration')->nullable();
            $table->string('pdf_illustration')->nullable();
            $table->text('root_cause')->nullable();
            $table->text('improvement_plan')->nullable();
            $table->date('commitment_date')->nullable();
            $table->string('responsible_group');
            $table->text('corrective_action')->nullable();
            $table->string('img_corrective_action')->nullable();
            $table->string('pdf_corrective_action')->nullable();
            $table->integer('qad_approval');
            $table->string('qad_approval_at')->nullable();
            $table->integer('o_s_approval');
            $table->integer('qad_approved_by');
            $table->integer('o_s_approved_by');
            $table->integer('audit_stat')->comment = "1-open, 2-for QAD Approval, 3 - Close"; //Task
            $table->integer('customer_audit_stat')->comment = "1-ongoing, 2-for approval, 3-Done Approval, 4 - disapprove, 5-archived"; //default
            $table->text('qad_dis_remarks')->nullable();

            $table->integer('number_OFI')->nullable();
            $table->integer('number_major_nc')->nullable();
            $table->integer('number_minor_nc')->nullable();
            $table->integer('number_possitive_feedback')->nullable();
            $table->String('audit_title')->nullable();

            $table->String('audit_title')->nullable();
            $table->String('section')->nullable();
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
        Schema::dropIfExists('customer_audits');
    }
}
