<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\User;

class AQMSInternalAuditor extends Model
{
    //
    protected $table = 'aqms_internal_auditors';

    public function internal_auditor_info() {
    	return $this->hasOne(User::class, 'id', 'internal_auditor_id');
    }
}
