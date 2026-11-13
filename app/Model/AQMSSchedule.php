<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\AQMSProcessOwner;
use App\Model\AQMSInternalAuditor;
use App\Model\AQMSProcess;
use App\Model\AQMSYear;

class AQMSSchedule extends Model
{
    //
    protected $table = 'aqms_schedules';

    public function process_owner_details() {
    	return $this->hasMany(AQMSProcessOwner::class, 'aqms_schedule_id', 'id');
    }

    public function internal_auditor_details() {
    	return $this->hasMany(AQMSInternalAuditor::class, 'aqms_schedule_id', 'id');
    }

    public function aqms_process_info() {
    	return $this->hasOne(AQMSProcess::class, 'id', 'process_id');
    }

    public function aqms_year_details() {
        return $this->hasMany(AQMSYear::class, 'aqms_schedule_id', 'id');
    }
}
