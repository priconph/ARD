<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\Internal;
use App\User;

class InternalPersonInCharges extends Model
{
    //
    protected $table = 'internal_person_in_charges';

    public function internal() {
    	return $this->belongsTo(Internal::class);
    }

    public function person_in_charge_record() {
        return $this->hasOne(User::class, 'id', 'person_in_charge_id');
    }
}
