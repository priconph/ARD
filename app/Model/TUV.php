<?php

namespace App\Model;

use App\User;
// use App\Model\TUVDepartments;
use App\Model\Department;
use App\Model\EvaluationItem;
use App\Model\TuvAuditAttachment;
use App\Model\TUVConPersonInCharges;
use App\Model\TUVSysPersonInCharges;
use App\Model\TUVCorrPersonInCharges;
use Illuminate\Database\Eloquent\Model;

class TUV extends Model
{
    //
    protected $table = 'tuv_audits';

    public function tuv_departments() {
    	return $this->hasMany(TUVDepartments::class, 'tuv_audit_id', 'tuv_audit_id');
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

    public function tuv_corr_per_in_charges() {
        return $this->hasMany(TUVCorrPersonInCharges::class, 'tuv_audit_id', 'tuv_audit_id');
    }

    public function tuv_con_per_in_charges() {
        return $this->hasMany(TUVConPersonInCharges::class, 'tuv_audit_id', 'tuv_audit_id');
    }

    public function tuv_sys_per_in_charges() {
        return $this->hasMany(TUVSysPersonInCharges::class, 'tuv_audit_id', 'tuv_audit_id');
    }

    public function evaluation_item_info() {
        return $this->hasOne(EvaluationItem::class, 'evaluation_item_id', 'evaluation_item_id');
    }  

    public function tuv_attachments(){
        return $this->hasMany(TuvAuditAttachment::class, 'tuv_audit_id', 'tuv_audit_id');
    }

     public function qad_approver() {
    	return $this->hasOne(User::class, 'id', 'qad_approval');
    }
}