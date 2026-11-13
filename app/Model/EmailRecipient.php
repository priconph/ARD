<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\User;
use App\Model\Department;

class EmailRecipient extends Model
{
    protected $table = 'email_recipients';

    public function receiver_info() {
    	return $this->hasOne(User::class, 'id', 'receiver');
    }

    public function department() {
    	return $this->hasOne(Department::class, 'department_id', 'department_id');
    }
}
