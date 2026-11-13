<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\Customer;
use App\Model\Department;

class CustomerDepartments extends Model
{
    //
    protected $table = 'customer_departments';
    
    public function customer() {
    	return $this->belongsTo(Customer::class);
    }

    public function departments() {
    	return $this->hasMany(Department::class, 'department_id', 'responsible_department_id');
    }
}
