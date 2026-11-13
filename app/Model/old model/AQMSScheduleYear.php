<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\AQMSSchedule;

class AQMSScheduleYear extends Model
{
    //
    protected $table = 'aqms_schedule_years';
    protected $connection = 'mysql';

    public function aqms_schedule_info() {
    	return $this->hasOne(AQMSSchedule::class, 'aqms_schedule_id', 'aqms_schedule_id');
    }
}
