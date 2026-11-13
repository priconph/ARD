<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\TUV;
use App\Model\Department;

class TUVDepartments extends Model
{
    //
    protected $table = 'tuv_departments';
    // protected $connection = 'rapidx';

    public function tuv() {
    	return $this->belongsTo(TUV::class);
    }

    public function departments() {
    	return $this->hasMany(Department::class, 'department_id', 'responsible_department_id');
    }

    public function department_info() {
    	return $this->hasOne(Department::class, 'department_id', 'responsible_department_id');
    }

    public function tuv_audit_info() {
    	return $this->hasOne(TUV::class, 'tuv_audit_id', 'tuv_audit_id');
    }
}
