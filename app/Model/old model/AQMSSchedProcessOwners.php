<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\AQMSAuditScheduleYear;
use App\User;

class AQMSSchedProcessOwners extends Model
{
    //
    protected $table = 'aqms_schedule_process_owners';
    protected $connection = 'mysql';

    public function aqms_audit_schedule_year() {
    	return $this->belongsTo(AQMSAuditScheduleYear::class);
    }

    public function process_owner_info() {
    	return $this->hasOne(User::class, 'id', 'process_owner_id');
    }
}
