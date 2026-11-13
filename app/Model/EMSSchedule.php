<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\EMSProcessOwner;
use App\Model\EMSInternalAuditor;
use App\Model\EMSArea;
use App\Model\EMSProcess;
use App\Model\EMSYear;

class EMSSchedule extends Model
{
    //
    protected $table = 'ems_schedules';

    public function process_owner_details() {
    	return $this->hasMany(EMSProcessOwner::class, 'ems_schedule_id', 'id');
    }

    public function internal_auditor_details() {
    	return $this->hasMany(EMSInternalAuditor::class, 'ems_schedule_id', 'id');
    }

    public function area_details() {
        return $this->hasMany(EMSArea::class, 'ems_schedule_id', 'id');
    }

    public function ems_process_info() {
    	return $this->hasOne(EMSProcess::class, 'id', 'process_id');
    }

    public function ems_year_details() {
        return $this->hasMany(EMSYear::class, 'ems_schedule_id', 'id');
    }
}
