<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\TUV;
use App\User;

class TUVConPersonInCharges extends Model
{
    //
    protected $table = 'tuv_con_per_in_charges';
    protected $connection = 'mysql';

    public function tuv() {
    	return $this->belongsTo(TUV::class);
    }

    public function tuv_con_per_in_charge_record() {
        return $this->hasOne(User::class, 'id', 'person_in_charge_id');
    }
}
