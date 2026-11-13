<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\User;
use App\Model\Department;

class Approver extends Model
{
    protected $table = 'approvers';

    public function approver_info() {
    	return $this->hasOne(User::class, 'id', 'approver');
    }

    public function department() {
    	return $this->hasOne(Department::class, 'department_id', 'department_id');
    }
}