<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInternalAuditsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('internal_audits', function (Blueprint $table) {
            $table->bigIncrements('internal_audit_id');
            $table->string('audit_type');
            $table->date('audit_date_from');
            $table->date('audit_date_to');
            $table->string('business_process');
            $table->string('auditors');
            $table->string('auditees');
            $table->string('audit_report_control_no');
            $table->string('classification');
            $table->string('nc_control_no')->nullable();
            $table->string('item_no')->nullable();
            $table->string('category_of_findings');
            $table->date('audit_findings_issued_date');
            $table->date('deadline_of_submission'); //Deadline for submission of CA Report
            $table->integer('deadline_of_submission_days');
            $table->date('actual_date_of_submission')->nullable();
            $table->integer('sending_stat')->nullable();
            $table->integer('email_stat')->nullable();
            $table->integer('in_batch');
            $table->text('iso_iatf_clause'); // ISO / IATF Clause
            $table->text('statement_of_findings')->nullable();
            $table->text('illustration')->nullable();
            $table->string('img_illustration')->nullable();
            $table->string('pdf_illustration')->nullable();
            $table->text('root_cause')->nullable();
            $table->text('correction')->nullable(); //Correction, containment & systematic corrective action to the nonconformity
            $table->text('containment')->nullable(); //Correction, containment & systematic corrective action to the nonconformity
            $table->text('systematic')->nullable(); //Correction, containment & systematic corrective action to the nonconformity
            $table->date('correction_commitment_date')->nullable();
            // $table->integer('correction_person_in_charge')->nullable();
            $table->date('containment_commitment_date')->nullable();
            // $table->integer('containment_person_in_charge')->nullable();
            $table->date('systematic_commitment_date')->nullable();
            // $table->integer('systematic_person_in_charge')->nullable();
            $table->text('improvement_action')->nullable();
            $table->date('commitment_date')->nullable();
            // $table->integer('responsible_department');
            // $table->integer('person_in_charge')->nullable();
            $table->text('corrective_action')->nullable(); //Corrective Action Effectiveness Verification
            $table->string('img_corrective_action')->nullable();
            $table->string('pdf_corrective_action')->nullable();
            $table->integer('qad_approval');
            $table->string('qad_approval_at')->nullable();
            $table->integer('o_s_approval');
            $table->integer('qad_approved_by');
            $table->integer('o_s_approved_by');
            $table->integer('audit_stat')->nullable()->comment="1-open, 2-for QAD Approval, 3 - Close";
            $table->integer('internal_audit_stat')->comment= "1-ongoing, 2-for approval, 3-Done Approval, 4 - disapprove, 5-archived";
            $table->text('qad_dis_remarks')->nullable();

            $table->integer('number_OFI')->nullable();
            $table->integer('number_major_nc')->nullable();
            $table->integer('number_minor_nc')->nullable();
            $table->integer('number_possitive_feedback')->nullable();
            $table->string('audit_title')->nullable();
            
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
        Schema::dropIfExists('internal_audits');
    }
}
