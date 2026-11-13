<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\AQMSAuditScheduleYear;
use App\User;

class AQMSSchedInternalAuditors extends Model
{
    //
    protected $table = 'aqms_schedule_internal_auditors';
    protected $connection = 'mysql';

    public function aqms_sched_internal_auditors() {
    	return $this->belongsTo(AQMSAuditScheduleYear::class);
    }

    public function internal_auditor_info() {
    	return $this->hasOne(User::class, 'id', 'internal_auditor_id');
    }
}
