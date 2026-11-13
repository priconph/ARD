<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\Internal;
use App\User;

class InternalSysPersonInCharges extends Model
{
    //
    protected $table = 'internal_sys_per_in_charges';
    protected $connection = 'mysql';

    public function internal() {
    	return $this->belongsTo(Internal::class);
    }

    public function internal_sys_per_in_charge_record() {
        return $this->hasOne(User::class, 'id', 'person_in_charge_id');
    }
}
