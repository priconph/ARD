<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\AQMSScheduleYear;
use App\User;

class AQMSScheduleInternalAuditor extends Model
{
    //
    protected $table = 'aqms_schedule_internal_auditors';
    protected $connection = 'mysql';

    public function aqms_sched_year() {
    	return $this->belongsTo(AQMSScheduleYear::class);
    }

    public function internal_auditor_info() {
    	return $this->hasOne(User::class, 'id', 'internal_auditor_id');
    }
}
