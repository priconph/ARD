<?php

namespace App\Model;

use App\User;
use App\Model\Department;
use App\Model\EvaluationItem;
use App\Model\InternalAuditAttachment;
// use App\Model\ResultInInternalBatch;
use App\Model\InternalPersonInCharges;
use Illuminate\Database\Eloquent\Model;
use App\Model\InternalConPersonInCharges;
use App\Model\InternalSysPersonInCharges;
use App\Model\InternalCorrPersonInCharges;

class Internal extends Model
{
    //
    protected $table = 'internal_audits';

    public function internal_departments() {
    	return $this->hasMany(InternalDepartments::class, 'internal_audit_id', 'internal_audit_id');
    }

    public function user_created_by() {
    	return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function user_last_updated_by() {
    	return $this->hasOne(User::class, 'id', 'last_updated_by');
    }

    public function department() {
        return $this->hasMany(Department::class, 'department_id', 'responsible_department');
    }

    // public function internal_audit() {
    //     return $this->belongsTo(ResultInInternalBatch::class);
    // }

    // public function person_in_charge_record() {
    //     return $this->hasOne(User::class, 'id', 'person_in_charge');
    // }

    public function person_in_charges() {
        return $this->hasMany(InternalPersonInCharges::class, 'internal_audit_id', 'internal_audit_id');
    }    

    public function internal_corr_per_in_charges() {
        return $this->hasMany(InternalCorrPersonInCharges::class, 'internal_audit_id', 'internal_audit_id');
    }

    public function internal_con_per_in_charges() {
        return $this->hasMany(InternalConPersonInCharges::class, 'internal_audit_id', 'internal_audit_id');
    }

    public function internal_sys_per_in_charges() {
        return $this->hasMany(InternalSysPersonInCharges::class, 'internal_audit_id', 'internal_audit_id');
    }

    public function evaluation_item_info() {
        return $this->hasOne(EvaluationItem::class, 'evaluation_item_id', 'evaluation_item_id');
    }  

    public function internal_attachments() {
        return $this->hasMany(InternalAuditAttachment::class, 'internal_audit_id', 'internal_audit_id');
    }

    public function qad_approver() {
    	return $this->hasOne(User::class, 'id', 'qad_approval');
    }
}
