<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\Customer;
use App\User;

class CustomerPersonInCharges extends Model
{
    //
    protected $table = 'customer_person_in_charges';

    public function customer() {
    	return $this->belongsTo(Customer::class);
    }

    public function person_in_charge_record() {
        return $this->hasOne(User::class, 'id', 'person_in_charge_id');
    }
}