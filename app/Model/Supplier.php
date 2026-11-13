<?php

namespace App\Model;

use App\User;
use App\Model\Department;
use App\Model\EvaluationItem;
use App\Model\SupplierAuditAttachment;
use App\Model\SupplierPersonInCharges;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    //
    protected $table = 'supplier_audits';

    public function supplier_departments() {
    	return $this->hasMany(SupplierDepartments::class, 'supplier_audit_id', 'supplier_audit_id');
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

    // public function person_in_charge_record() {
    //     return $this->hasOne(User::class, 'id', 'person_in_charge');
    // }

    public function person_in_charges() {
        return $this->hasMany(SupplierPersonInCharges::class, 'supplier_audit_id', 'supplier_audit_id');
    }

    public function evaluation_item_info() {
        return $this->hasOne(EvaluationItem::class, 'evaluation_item_id', 'evaluation_item_id');
    } 

    public function supplier_attachments() {
        return $this->hasMany(SupplierAuditAttachment::class, 'supplier_audit_id', 'supplier_audit_id');
    }

    public function qad_approver() {
    	return $this->hasOne(User::class, 'id', 'qad_approval');
    }

}
