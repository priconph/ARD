<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Model\Supplier;
use App\User;

class SupplierPersonInCharges extends Model
{
    //
    protected $table = 'supplier_person_in_charges';

    public function supplier() {
    	return $this->belongsTo(Supplier::class);
    }

    public function person_in_charge_record() {
        return $this->hasOne(User::class, 'id', 'person_in_charge_id');
    }
}
