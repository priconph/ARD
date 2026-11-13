<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
Use \Maatwebsite\Excel\Sheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Sheets\TUVSheet;
use App\Exports\Sheets\CustomerSheet;
use App\Exports\Sheets\InternalSheet;
use App\Exports\Sheets\SupplierSheet;
use App\Model\TUV;
use App\Model\Customer;
use App\Model\Internal;
use App\Model\Supplier;

class AuditResultExport implements ShouldAutoSize, WithMultipleSheets
{
    /**
    * @return \Illuminate\Support\Collection
    */
    // public function collection()
    // {
    //     //
    // }

    use Exportable;

    protected $tuvs;
    protected $customers;
    protected $internals;
    protected $suppliers;
    protected $audit_result_type;

    function __construct($audit_result_type, $tuvs, $customers, $internals, $suppliers)
    {
        $this->tuvs = $tuvs;
        $this->customers = $customers;
        $this->internals = $internals;
        $this->suppliers = $suppliers;
        $this->audit_result_type = $audit_result_type;
    }

    //for multiple sheets
    public function sheets(): array
    {
        $sheets = [];

        if($this->audit_result_type == 1){
        	$sheets[] = new TUVSheet(1, $this->tuvs);
        }
        else if($this->audit_result_type == 2){
        	$sheets[] = new CustomerSheet(1, $this->customers);
        }
        else if($this->audit_result_type == 3){
        	$sheets[] = new InternalSheet(1, $this->internals);
        }
        else if($this->audit_result_type == 4){
        	$sheets[] = new SupplierSheet(1, $this->suppliers);
        }

        return $sheets;
    }
}
