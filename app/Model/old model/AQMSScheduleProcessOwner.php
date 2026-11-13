<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\AQMSScheduleYear;
use App\User;

class AQMSScheduleProcessOwner extends Model
{
    //
    protected $table = 'aqms_schedule_process_owners';
    protected $connection = 'mysql';

    public function aqms_schedule_year() {
    	return $this->belongsTo(AQMSScheduleYear::class);
    }

    public function process_owner_info() {
    	return $this->hasOne(User::class, 'id', 'process_owner_id');
    }
}
