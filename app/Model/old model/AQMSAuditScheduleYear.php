<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\AQMSAuditSchedule;

class AQMSAuditScheduleYear extends Model
{
    //
    protected $table = 'aqms_audit_schedule_years';
    protected $connection = 'mysql';

    public function aqms_audit_schedule() {
    	return $this->hasOne(AQMSAuditSchedule::class, 'aqms_audit_schedule_id', 'aqms_audit_schedule_id');
    }
}
