<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\Internal;
use App\Model\Department;
use App\Model\EmailRecipient;

class InternalDepartments extends Model
{
    //
    protected $table = 'internal_departments';
    // protected $connection = 'rapidx';

    public function internal() {
    	return $this->belongsTo(Internal::class);
    }

    public function departments() {
    	return $this->hasMany(Department::class, 'department_id', 'responsible_department_id');
    }

    public function department_info() {
    	return $this->hasOne(Department::class, 'department_id', 'responsible_department_id');
    }

    public function email_recipients_list() {
    	return $this->hasMany(EmailRecipient::class, 'department_id', 'responsible_department_id');
    }
}
