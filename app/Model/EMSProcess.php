<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\EMSSchedule;

class EMSProcess extends Model
{
    //

    protected $table = 'ems_processes';

    public function ems_schedule_details() {
    	return $this->hasMany(EMSSchedule::class, 'process_id', 'id');
    }
}
