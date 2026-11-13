<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\User;

class AQMSProcessOwner extends Model
{
    //
    protected $table = 'aqms_process_owners';

    public function process_owner_info() {
    	return $this->hasOne(User::class, 'id', 'process_owner_id');
    }
}
