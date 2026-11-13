<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\AQMSScheduleYear;
use App\Model\AQMSScheduleInternalAuditor;
use App\Model\AQMSScheduleProcessOwner;

class AQMSSchedule extends Model
{
    //
    protected $table = 'aqms_schedules';
    protected $connection = 'mysql';

    public function aqms_sched_years() {
    	return $this->hasMany(AQMSScheduleYear::class, 'aqms_schedule_id', 'id');
    }

    public function aqms_sched_internal_auditors() {
    	return $this->hasMany(AQMSScheduleInternalAuditor::class, 'aqms_schedule_id', 'id');
    }

    public function aqms_sched_process_owners() {
    	return $this->hasMany(AQMSScheduleProcessOwner::class, 'aqms_schedule_id', 'id');
    }
}
