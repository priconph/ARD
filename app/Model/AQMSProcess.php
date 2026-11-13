<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\AQMSSchedule;

class AQMSProcess extends Model
{
    //

    protected $table = 'aqms_processes';

    public function aqms_schedule_details() {
    	return $this->hasMany(AQMSSchedule::class, 'process_id', 'id');
    }
}
