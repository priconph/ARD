<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTuvAuditsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tuv_audits', function (Blueprint $table) {
            $table->bigIncrements('tuv_audit_id');
            $table->string('ctrl_no')->nullable();
            $table->string('audit_category');
            $table->string('purpose_of_audit');
            $table->date('audit_date_from');
            $table->date('audit_date_to');
            $table->string('auditors');
            $table->string('item_no')->nullable();
            $table->text('evaluation_item');
            $table->string('classification');
            $table->date('deadline_for_submission'); //Deadline for submission of CA Report
            $table->integer('deadline_for_submission_days');
            $table->date('actual_date_of_submission')->nullable();
            $table->integer('sending_stat')->nullable();
            $table->integer('email_stat')->nullable();
            $table->integer('in_batch')->nullable();
            $table->string('standard_clause')->nullable();
            $table->text('statement_of_nc')->nullable();
            $table->text('objective_evidence')->nullable();
            $table->text('root_cause_analysis')->nullable(); //Root cause analysis (including method used and results)
            $table->text('correction')->nullable(); //Correction, containment & systematic corrective action to the nonconformity
            $table->text('containment')->nullable(); //Correction, containment & systematic corrective action to the nonconformity
            $table->text('systematic')->nullable(); //Correction, containment & systematic corrective action to the nonconformity
            $table->date('correction_commitment_date')->nullable();
            // $table->integer('correction_person_in_charge')->nullable();
            $table->date('containment_commitment_date')->nullable();
            // $table->integer('containment_person_in_charge')->nullable();
            $table->date('systematic_commitment_date')->nullable();
            // $table->integer('systematic_person_in_charge')->nullable();
            $table->text('corrective_action')->nullable(); //Corrective Action Effectiveness Verification
            $table->string('img_corrective_action')->nullable();
            $table->string('pdf_corrective_action')->nullable();
            $table->integer('qad_approval')->nullable();
            $table->string('qad_approval_at')->nullable();
            $table->integer('o_s_approval')->nullable();
            $table->integer('qad_approved_by')->nullable();
            $table->integer('o_s_approved_by')->nullable();
            $table->integer('audit_stat')->nullable()->comment = "1-open, 2-for QAD Approval, 3 - Close";
            $table->integer('tuv_audit_stat')->nullable()->comment = "1-ongoing, 2-for approval, 3-Done Approval, 4 - disapprove, 5-archived";
            $table->text('qad_dis_remarks')->nullable();

            $table->integer('number_OFI')->nullable();
            $table->integer('number_major_nc')->nullable();
            $table->integer('number_minor_nc')->nullable();
            $table->integer('number_possitive_feedback')->nullable();
            $table->String('audit_title')->nullable();

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
        Schema::dropIfExists('tuv_audits');
    }
}
