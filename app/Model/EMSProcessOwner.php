<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\User;

class EMSProcessOwner extends Model
{
    //
    protected $table = 'ems_process_owners';

    public function process_owner_info() {
    	return $this->hasOne(User::class, 'id', 'process_owner_id');
    }
}
