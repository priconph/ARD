<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\Department;

class EMSArea extends Model
{
    //
    protected $table = 'ems_areas';
    protected $connection = 'mysql';

    public function area_info() {
    	return $this->hasOne(Department::class, 'department_id', 'area_id');
    }
}
