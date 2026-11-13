<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\Supplier;
use App\Model\Department;

class SupplierDepartments extends Model
{
    //
    protected $table = 'supplier_departments';
    // protected $connection = 'rapidx';

    public function supplier() {
    	return $this->belongsTo(Supplier::class);
    }

    public function departments() {
    	return $this->hasMany(Department::class, 'department_id', 'responsible_department_id');
    }
}
