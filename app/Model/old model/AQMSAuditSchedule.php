<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\AQMSSchedProcessOwners;
use App\Model\AQMSSchedInternalAuditors;

class AQMSAuditSchedule extends Model
{
    //
    protected $table = 'aqms_audit_schedules';
    protected $connection = 'mysql';

    public function aqms_sched_internal_auditors() {
    	return $this->hasMany(AQMSSchedInternalAuditors::class, 'aqms_audit_schedule_id', 'aqms_audit_schedule_id');
    }

    public function aqms_sched_process_owners() {
    	return $this->hasMany(AQMSSchedProcessOwners::class, 'aqms_audit_schedule_id', 'aqms_audit_schedule_id');
    }
}
