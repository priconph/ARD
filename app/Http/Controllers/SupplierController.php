<?php

namespace App\Http\Controllers;

use App\User;
use DataTables;
use Carbon\Carbon;
use App\Model\Approver;
use App\Model\Supplier;
use App\Model\Department;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Exports\AuditResultExport;
use App\Model\SupplierDepartments;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendNewSupplierMailJob;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use App\Jobs\SendAuditResultEmailJob;
use App\Jobs\SendSupplierBatchMailJob;
use App\Model\SupplierAuditAttachment;
use App\Model\SupplierPersonInCharges;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    //
    public function supplier_export(Request $request)
    {
        date_default_timezone_set('Asia/Manila');
        session_start();
        
        $responsible_department = 0;

        if($request->responsible_department != '0' && $request->has('responsible_department')) {
           $responsible_department = $request->responsible_department;
           $responsible_department = explode(",", $responsible_department);
        }

        $suppliers = Supplier::on('mysql')
                    ->with(
                        ['supplier_departments' => function($query) use ($responsible_department){
                            $query->where('supplier_department_stat', 1);
                            if($responsible_department != 0) {
                                $query->whereIn('responsible_department_id', $responsible_department);
                            }
                        }, 'supplier_departments.departments', 'person_in_charges', 'person_in_charges.person_in_charge_record'])
                    ->withCount(['supplier_departments' => function($query) use ($responsible_department){
                            $query->where('supplier_department_stat', 1);
                            if($responsible_department != 0) {
                                $query->whereIn('responsible_department_id', $responsible_department);
                            }
                        }])
                    ->having('supplier_departments_count', '>', 0);

        if($request->date_from != '0'){
            $suppliers = $suppliers->where('audit_date_from', '>=', $request->date_from);
        }

        if($request->date_to != '0'){
            $suppliers = $suppliers->where('audit_date_to', '<=', $request->date_to);
        }

        if($request->audit_category != '0' && $request->has('audit_category')) {
            $suppliers = $suppliers->where('audit_category', $request->audit_category);
        }

        if($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
            $suppliers = $suppliers->where('purpose_of_audit', $request->purpose_of_audit);
        }

        if($request->classification != '0' && $request->has('classification')) {
            $suppliers = $suppliers->where('classification', $request->classification);
        }

        if($request->audit_stat != '0' && $request->has('audit_stat')) {
            $suppliers = $suppliers->where('audit_stat', $request->audit_stat);
        }

        $suppliers = $suppliers->get();

        return Excel::download(new AuditResultExport(4, '', '', '', $suppliers), 'SUP' . date('Y-m-d H:i:s') . '.xlsx');
    }

    public function view_supplier_audit_by_stat(Request $request){
        session_start();
        $responsible_department = $request->responsible_department;

        $suppliers = Supplier::on('mysql')
                    ->with(
                        ['supplier_departments' => function($query) use ($responsible_department){
                            $query->where('supplier_department_stat', 1);
                            if($responsible_department != 0) {
                                $query->whereIn('responsible_department_id', $responsible_department);
                            }
                        }, 'supplier_departments.departments', 'person_in_charges', 'person_in_charges.person_in_charge_record'])
                    ->withCount(['supplier_departments' => function($query) use ($responsible_department){
                            $query->where('supplier_department_stat', 1);
                            if($responsible_department != 0) {
                                $query->whereIn('responsible_department_id', $responsible_department);
                            }
                        }])
                    ->having('supplier_departments_count', '>', 0);
                    // ->where('supplier_audit_stat', 1);

        if($request->audit_id != '0'){
            $suppliers = $suppliers->where('supplier_audit_id', $request->audit_id);
        }

        if($request->date_from != '0'){
            $suppliers = $suppliers->where('audit_date_from', '>=', $request->date_from);
        }

        if($request->date_to != '0'){
            $suppliers = $suppliers->where('audit_date_to', '<=', $request->date_to);
        }

        if($request->audit_category != '0' && $request->has('audit_category')) {
            $suppliers = $suppliers->where('audit_category', $request->audit_category);
        }

        if($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
            $suppliers = $suppliers->where('purpose_of_audit', $request->purpose_of_audit);
        }

        if($request->classification != '0' && $request->has('classification')) {
            $suppliers = $suppliers->where('classification', $request->classification);
        }

        if($request->audit_stat != '0' && $request->has('audit_stat')) {
            $suppliers = $suppliers->where('audit_stat', $request->audit_stat);
        }

        if($request->item_no != '0' && $request->has('item_no')) {
            $suppliers = $suppliers->where('item_no', $request->item_no);
        }

        if($request->approval_stat != '0' && $request->has('approval_stat')) {
            if($request->approval_stat == 1){
                $suppliers = $suppliers->where(function($query){
                    $query->where('audit_stat', 1);
                    $query->where('qad_approval', 0);
                });
            }
            else if($request->approval_stat == 2){
                $suppliers = $suppliers->where(function($query){
                    $query->where('audit_stat', 2);
                    $query->where('o_s_approval', 0);
                });
            }
        }

        if($request->evaluation_item != null && $request->has('evaluation_item')) {
            if($request->evaluation_item == 'Blank'){
                $suppliers = $suppliers->where('evaluation_item_id', null);
            }
            else{
                $suppliers = $suppliers->where('evaluation_item_id', $request->evaluation_item);
            }
        }

        $suppliers = $suppliers->where('supplier_audit_stat', $request->supplier_audit_stat);

        $suppliers = $suppliers->get();

        return DataTables::of($suppliers)
            ->addColumn('label1', function($supplier){
                $result = "";

                if($supplier->sending_stat == 1){
                    $result .= '<span class="tag tag-warning">Pending</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function($supplier){
                $result = "";

                if($supplier->audit_stat == 1){
                    $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
                }
                else if($supplier->audit_stat == 2){
                    $result .= '<span class="tag tag-success">For Closed-Out</span>';
                }
                else{
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            // ->addColumn('formatted_audit_date', function($supplier){
            //     return Carbon::parse($supplier->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($supplier->audit_date_to)->toFormattedDateString();
            // })
            ->addColumn('formatted_audit_date', function($supplier){
                $result = '';
                if($supplier->audit_date_from != $supplier->audit_date_to){
                    $result = Carbon::parse($supplier->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($supplier->audit_date_to)->toFormattedDateString();
                }
                else{
                    $result = Carbon::parse($supplier->audit_date_from)->toFormattedDateString();
                }
                return $result;
            })
            ->addColumn('action1', function($supplier){
                $result = '<center><div class="btn-group">
                          <button type="button" class="btn btn-primary dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-gear"></i>
                          </button>
                          <div class="dropdown-menu dropdown-menu-right">
                            <button class="dropdown-item aViewSuppAudit" supplier-id="' . $supplier->supplier_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalViewSuppAudit" data-keyboard="false">View</button>';

                // if($supplier->sending_stat != 2){
                    $result .= '<button class="dropdown-item aEditSupp" supplier-id="' . $supplier->supplier_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditSuppAudit" data-keyboard="false">Edit</button>';
                // }            

                if($supplier->audit_stat == 1 || $supplier->audit_stat == 2){
                    $result .= '<button class="dropdown-item aCloseSuppAudit" type="button" supplier-id="' . $supplier->supplier_audit_id . '" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalCloseSuppAudit" data-keyboard="false">Close</button>';
                }
                else if($supplier->audit_stat == 3 && $supplier->sending_stat == 1){
                    $result .= '<button class="dropdown-item aOpenSuppAudit" type="button" style="padding: 1px 1px; text-align: center;" supplier-id="' . $supplier->supplier_audit_id . '" data-toggle="modal" data-target="#modalOpenSuppAudit" data-keyboard="false">Open</button>';
                }

                if($supplier->sending_stat == 1){
                    $result .= '<button class="dropdown-item aDoneSuppAudit" type="button" supplier-id="' . $supplier->supplier_audit_id . '" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalDoneSuppAudit" data-keyboard="false">Done</button>';
                }

                if($supplier->sending_stat == 2) {
                    $result .= '<button class="dropdown-item aPendSuppAudit" type="button" supplier-id="' . $supplier->supplier_audit_id . '" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalPendSuppAudit" data-keyboard="false">Pend</button>';
                }

                if($supplier->supplier_audit_stat == 1) {
                    $result .= '<button class="dropdown-item aChangeSuppAuditStat" type="button" supplier-id="' . $supplier->supplier_audit_id . '" supplier-audit-stat="2" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalChangeSuppAuditStat" data-keyboard="false">Archive</button>';
                }
                else {
                    $result .= '<button class="dropdown-item aChangeSuppAuditStat" type="button" supplier-id="' . $supplier->supplier_audit_id . '" supplier-audit-stat="1" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalChangeSuppAuditStat" data-keyboard="false">Restore</button>';
                }
                            
                $result .= '</div>
                        </div></center>';

                // return $result;


                $test = '';
                $test = '<button class="btn-actions btn btn-sm btn-info aViewSuppAudit" supplier-id="' . $supplier->supplier_audit_id . '" type="button" data-toggle="modal" data-target="#modalViewSuppAudit" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="View" title="View"><i class="icon-file-text-o"></i></button> ';


                if($supplier->supplier_audit_stat == 1) {
                    $test .= '<button class="btn-actions btn btn-sm btn-success aEditSupp" supplier-id="' . $supplier->supplier_audit_id . '" type="button" data-toggle="modal" data-target="#modalEditSuppAudit" data-keyboard="false" title="Edit"><i class="icon-edit2"></i></button> ';

                    $test .= '<button class="btn-actions btn btn-sm btn-danger aChangeSuppAuditStat" type="button" supplier-id="' . $supplier->supplier_audit_id . '" supplier-audit-stat="2" data-toggle="modal" data-target="#modalChangeSuppAuditStat" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="Archive" title="Archive"><i class="icon-trash2"></i></button> ';
                }
                else {
                    $test .= '<button class="btn-actions btn btn-sm btn-warning aChangeSuppAuditStat" type="button" supplier-id="' . $supplier->supplier_audit_id . '" supplier-audit-stat="1" data-toggle="modal" data-target="#modalChangeSuppAuditStat" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="Restore" title="Restore"><i class="icon-history2"></i></button> ';               
                }
                return $test;
            })
            ->addColumn('check_box_send', function($supplier){
                $result = "";

                if($supplier->qad_approval == '0'){
                    $result = "disabled";
                }

                return '<center><input type="checkbox" class="chkSendSuppAudit" supplier-id="' . $supplier->supplier_audit_id . '" ' . $result . '></center>';
            })
            ->addColumn('approval_stat', function($supplier){
                $result = "";

                if($supplier->audit_stat == 1 && $supplier->qad_approval == 1){
                    $result .= '<span class="tag tag-success">Approved By QAD</span>';
                }
                else if($supplier->audit_stat == 1 && $supplier->qad_approval == 0){
                    $result .= '<span class="tag tag-warning">For QAD Approval</span>';
                }
                else if($supplier->audit_stat == 2 && $supplier->o_s_approval == 1){
                    $result .= '<span class="tag tag-success">Approved By Section Head</span>';
                }
                else if($supplier->audit_stat == 2 && $supplier->o_s_approval == 0){
                    $result .= '<span class="tag tag-warning">For Section Head Approval</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Approved By QAD</span>';
                }

                return $result;
            })
            ->rawColumns(['action1', 'label1', 'label2', 'formatted_audit_date', 'check_box_send', 'approval_stat'])
            ->make(true);
    }
    public function view_supplier_audit_by_stat_v2(Request $request){
        session_start();
        
        $responsible_department = $request->responsible_department;
        
        $suppliers = Supplier::on('mysql')
                    ->with(
                        [
                            'supplier_departments' => function($query) use ($responsible_department){
                                $query->where('supplier_department_stat', 1);
                                // if($responsible_department != 0) {
                                //     $query->whereIn('responsible_department_id', $responsible_department);
                                // }
                            }, 'supplier_departments.departments',
                            'person_in_charges',
                            'person_in_charges.person_in_charge_record',
                            'qad_approver',
                            'user_last_updated_by'
                        ])
                        ->when($request->isArchived == 1, function ($query) {
                            $query->where('supplier_audit_stat', 5);
                        }, function ($query) {
                            $query->where('supplier_audit_stat', '<>', 5);
                        });
                        // ->where('supplier_audit_stat', '<>', 5);
                    // ->withCount(['supplier_departments' => function($query) use ($responsible_department){
                    //         $query->where('supplier_department_stat', 1);
                    //         if($responsible_department != 0) {
                    //             $query->whereIn('responsible_department_id', $responsible_department);
                    //         }
                    //     }])
                    // ->having('supplier_departments_count', '>', 0);

        if($request->filterYear != '' || $request->filterYear != null) {
            $suppliers = $suppliers->whereYear('audit_date_from', $request->filterYear);
        }

        // $suppliers = $suppliers->where('supplier_audit_stat', $request->supplier_audit_stat);

        $suppliers = $suppliers->get();

        return DataTables::of($suppliers)
          
            ->addColumn('raw_audit_status', function($supplier){
                $result = "";

                if($supplier->audit_stat == 1){
                    $result .= '<span class="tag tag-warning">Open</span>';
                }
                else if($supplier->audit_stat == 2){
                    $result .= '<span class="tag tag-success">For QAD Approval</span>';
                }
                else{
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function($supplier){
                $result = '';
                if($supplier->audit_date_from != $supplier->audit_date_to){
                    $result = Carbon::parse($supplier->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($supplier->audit_date_to)->toFormattedDateString();
                }
                else{
                    $result = Carbon::parse($supplier->audit_date_from)->toFormattedDateString();
                }
                return $result;
            })
            ->addColumn('action1', function($supplier) use ($request){
                $result = '';
                $result = '<button class="btn-actions btn btn-sm btn-info aViewSuppAudit" supplier-id="' . $supplier->supplier_audit_id . '" type="button" data-toggle="modal" data-target="#modalViewSuppAudit" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="View" title="View"><i class="icon-file-text-o"></i></button> ';


                if($supplier->supplier_audit_stat == 1 || $supplier->supplier_audit_stat == 4) { 
                    $result .= '<button class="btn-actions btn btn-sm btn-warning aEditSupp" supplier-id="' . $supplier->supplier_audit_id . '" type="button" data-toggle="modal" data-target="#modalAddSuppAudit" data-keyboard="false" title="Edit"><i class="icon-edit2"></i></button> ';

                    $result .= '<button class="btn-actions btn btn-sm btn-danger aChangeSuppAuditStat" type="button" supplier-id="' . $supplier->supplier_audit_id . '" supplier-audit-stat="5" data-toggle="modal" data-target="#modalChangeSuppAuditStat" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="Archive" title="Archive"><i class="icon-trash2"></i></button> ';
                }
               
                if($supplier->audit_stat == 1 && $request->isArchived != 1) {
                    $result .= "<button class='btn btn-success btn-sm btnProceedApproval' type='button' data-aType='supplier' data-id='{$supplier->supplier_audit_id}'><i class='icon-paper-plane'></i></button>";
                }

                if($request->isArchived == 1){
                    $result .= '<button class="btn-actions btn btn-sm btn-success aChangeSuppAuditStat" type="button" supplier-id="' . $supplier->supplier_audit_id . '" supplier-audit-stat="1" data-toggle="modal" data-target="#modalChangeSuppAuditStat" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="Restore" title="Restore"><i class="icon-history2"></i></button> ';
                }
                return $result;
            })
            ->addColumn('check_box_send', function($supplier){
                $result = "";

                if($supplier->qad_approval == '0'){
                    $result = "disabled";
                }

                return '<center><input type="checkbox" class="chkSendSuppAudit" supplier-id="' . $supplier->supplier_audit_id . '" ' . $result . '></center>';
            })
            ->addColumn('approval_stat', function($supplier){
                $result = "----";

                if($supplier->supplier_audit_stat == 2){
                    $result = '<span class="tag tag-warning">Pending</span>';

                }
                else if($supplier->supplier_audit_stat == 3){
                    $result = '<span class="tag tag-success">approved</span>';
                    $result .= "<br><em>Approved by: {$supplier->qad_approver->name}</em>";
                }
                else if($supplier->supplier_audit_stat == 4){
                    $result = '<span class="tag tag-danger">disapproved</span>';
                    $result .= "<br><em>Disapproved by: {$supplier->user_last_updated_by->name}</em>";
                    $result .= "<br><em><strong>Remarks</strong>: {$supplier->qad_dis_remarks}</em>";
                }

                return $result;
            })
            ->addColumn('raw_responsible_department', function($supplier){
                $result = "";

                foreach($supplier->supplier_departments as $supplier_department) {
                    $result .= $supplier_department->departments[0]->department_name . ", ";
                }
                return $result;
            })
            ->rawColumns(['action1', 'raw_audit_status', 'formatted_audit_date', 'check_box_send', 'approval_stat', 'raw_responsible_department'])
            ->make(true);
    }

    public function view_supplier_audit_for_approval(Request $request){
        session_start();
        $responsible_department = $request->responsible_department;
        $user_access = $request->user_access;

        $is_approver = false;

        // if($_SESSION["rapidx_user_level_id"] == '3'){
        //     if(count($_SESSION['rapidx_user_accesses']) > 0){
        //         for($index = 0; $index < count($_SESSION['rapidx_user_accesses']); $index++){
        //             if($_SESSION['rapidx_user_accesses'][$index]['user_level_id'] == '4'){
        //                 $user_access = 4;
        //                 break;
        //             }
        //         }
        //     }
        // }

        $approvers = Approver::where('approver', $_SESSION["rapidx_user_id"])
                    ->where('approver_stat', '1')
                    ->get();

        if(count($approvers) > 0) {
            $is_approver = true;
        }

        if($user_access == '4'){
            $suppliers = Supplier::on('mysql')
                        ->with(
                            ['supplier_departments' => function($query) use ($responsible_department){
                                $query->where('supplier_department_stat', 1);
                                if($responsible_department != 0) {
                                    $query->whereIn('responsible_department_id', $responsible_department);
                                }
                            }, 'supplier_departments.departments', 'person_in_charges', 'person_in_charges.person_in_charge_record'])
                        ->withCount(['supplier_departments' => function($query) use ($responsible_department){
                                $query->where('supplier_department_stat', 1);
                                if($responsible_department != 0) {
                                    $query->whereIn('responsible_department_id', $responsible_department);
                                }
                            }])
                        ->where('qad_approval', 0)
                        ->having('supplier_departments_count', '>', 0);
        }
        else{
            $responsible_department = [];

            foreach ($approvers as $approver) {
                $responsible_department[] = $approver->department_id;
            }

            if($is_approver == false){
                $responsible_department[] = $_SESSION['rapidx_department_id'];
            }

            $suppliers = Supplier::on('mysql')
                        ->with(
                            ['supplier_departments' => function($query) use ($responsible_department){
                                $query->where('supplier_department_stat', 1);
                                if($responsible_department != 0) {
                                    $query->whereIn('responsible_department_id', $responsible_department);
                                }
                            }, 'supplier_departments.departments', 'person_in_charges', 'person_in_charges.person_in_charge_record'])
                        ->withCount(['supplier_departments' => function($query) use ($responsible_department){
                                $query->where('supplier_department_stat', 1);
                                if($responsible_department != 0) {
                                    $query->whereIn('responsible_department_id', $responsible_department);
                                }
                            }])
                        ->where('o_s_approval', 0)
                        ->where('audit_stat', 2)
                        ->having('supplier_departments_count', '>', 0);
        }


        if($request->date_from != '0'){
            $suppliers = $suppliers->where('audit_date_from', '>=', $request->date_from);
        }

        if($request->date_to != '0'){
            $suppliers = $suppliers->where('audit_date_to', '<=', $request->date_to);
        }

        if($request->audit_category != '0' && $request->has('audit_category')) {
            $suppliers = $suppliers->where('audit_category', $request->audit_category);
        }

        if($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
            $suppliers = $suppliers->where('purpose_of_audit', $request->purpose_of_audit);
        }

        if($request->classification != '0' && $request->has('classification')) {
            $suppliers = $suppliers->where('classification', $request->classification);
        }

        if($request->audit_stat != '0' && $request->has('audit_stat')) {
            $suppliers = $suppliers->where('audit_stat', $request->audit_stat);
        }

        $suppliers = $suppliers->get();

        return DataTables::of($suppliers)
            ->addColumn('label1', function($supplier){
                $result = "";

                if($supplier->sending_stat == 1){
                    $result .= '<span class="tag tag-warning">Pending</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function($supplier){
                $result = "";

                if($supplier->audit_stat == 1){
                    $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
                }
                else if($supplier->audit_stat == 2){
                    $result .= '<span class="tag tag-success">For Closed-Out</span>';
                }
                else{
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function($supplier){
                return Carbon::parse($supplier->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($supplier->audit_date_to)->toFormattedDateString();
            })
            ->addColumn('action1', function($supplier) use($user_access, $is_approver){
                $result = '<div class="btn-group">
                          <button type="button" class="btn btn-primary dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-gear"></i>
                          </button>
                          <div class="dropdown-menu dropdown-menu-right">
                            <button class="dropdown-item aViewSuppAudit" supplier-id="' . $supplier->supplier_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalViewSuppAudit" data-keyboard="false">View</button>';

                if($user_access == 4){
                    if($supplier->sending_stat != 2){
                        $result .= '<button class="dropdown-item aEditSupp" supplier-id="' . $supplier->supplier_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditSuppAudit" data-keyboard="false">Edit</button>';
                    }
                }
                else{
                    if($supplier->sending_stat != 2){
                        if(($supplier->audit_stat == 2 && $supplier->qad_approval == 1) && $is_approver == true){
                            if($supplier->audit_stat == 2 && $supplier->o_s_approval == 0 && $is_approver == true){
                                $result .= '<button class="dropdown-item aEditSupp" supplier-id="' . $supplier->supplier_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditSuppAudit" data-keyboard="false">Edit</button>';
                            }
                            else if($supplier->audit_stat == 2 && $supplier->o_s_approval == 0 && $is_approver == false){
                                $result .= '<button class="dropdown-item aEditSupp" supplier-id="' . $supplier->supplier_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditSuppAudit" data-keyboard="false">Edit</button>';
                            }
                        }
                        else if($supplier->audit_stat == 1 && $supplier->qad_approval == 1){
                            $result .= '<button class="dropdown-item aEditSupp" supplier-id="' . $supplier->supplier_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditSuppAudit" data-keyboard="false">Edit</button>';
                        }
                    }
                }
                            
                $result .= '</div>
                        </div>';

                return $result;
            })
            ->addColumn('check_box_send', function($supplier) use ($is_approver, $user_access){
                $result = "disabled";

                if($user_access == 4){
                    if($supplier->qad_approval == '0'){
                        $result = "";
                    }
                }
                else{
                    if($supplier->o_s_approval == '0'){
                        $result = "";
                    }
                }

                if($is_approver == false){
                    $result = "disabled";
                }

                return '<center><input type="checkbox" class="chkSendSuppAudit" supplier-id="' . $supplier->supplier_audit_id . '" ' . $result . '></center>';
            })
            ->addColumn('approval_stat', function($supplier){
                $result = "";

                if($supplier->audit_stat == 1 && $supplier->qad_approval == 1){
                    $result .= '<span class="tag tag-success">Approved By QAD</span>';
                }
                else if($supplier->audit_stat == 1 && $supplier->qad_approval == 0){
                    $result .= '<span class="tag tag-warning">For QAD Approval</span>';
                }
                else if($supplier->audit_stat == 2 && $supplier->o_s_approval == 1){
                    $result .= '<span class="tag tag-success">Approved By Section Head</span>';
                }
                else if($supplier->audit_stat == 2 && $supplier->o_s_approval == 0){
                    $result .= '<span class="tag tag-warning">For Section Head Approval</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Approved By QAD</span>';
                }

                return $result;
            })
            ->rawColumns(['action1', 'label1', 'label2', 'formatted_audit_date', 'check_box_send', 'approval_stat'])
            ->make(true);
    }
    public function view_supplier_audit_for_approval_v2(Request $request){
        session_start();
        $supplier_audits = Supplier::with([
            'supplier_departments' => function($query){
                $query->where('supplier_department_stat', 1);
            },
            'supplier_departments.departments',
        ])
        ->whereYear('created_at', '>=', '2026')
        ->where('audit_stat', 2)
        ->where('supplier_audit_stat', 2)
        ->get();

        return DataTables::of($supplier_audits)
        ->addColumn('action', function($supplier_audit){
            $result = "";
            $result .= "<button class='btn btn-sm btn-info aViewSuppAudit' style='margin-right: 2px;' supplier-id='{$supplier_audit->supplier_audit_id}' type='button' data-toggle='modal' data-target='#modalViewSuppAudit' data-keyboard='false' title='View Audit Result'><i class='icon-eye'></i></button>";
            $result .= "<button class='btn btn-sm btn-success btnApprove' style='margin-right: 2px;' data-id='{$supplier_audit->supplier_audit_id}' data-classification='supplier' title='Approve Audit Result'><i class='icon-circle-check'></i></button>";
            $result .= "<button class='btn btn-sm btn-danger btnDisapprove' data-id='{$supplier_audit->supplier_audit_id}' data-classification='supplier' title='Disapprove Audit Result'><i class='icon-times'></i></button>";
            return $result;
        })
        ->addColumn('formatted_audit_date', function($supplier){
            return Carbon::parse($supplier->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($supplier->audit_date_to)->toFormattedDateString();
        })
        ->rawColumns(['action', 'formatted_audit_date'])
        ->make(true);
    }

    public function view_batch_supplier_audit_by_stat(Request $request){
        session_start();
        $supplier_audit_id = [];
        if(isset($request->supplier_audit_id) || $request->supplier_audit_id != null){
            $supplier_audit_id = $request->supplier_audit_id;
        }
        $suppliers = Supplier::with([
                            'supplier_departments',
                            'supplier_departments.departments'
                        ])
                        ->whereIn('supplier_audit_id', $supplier_audit_id)
                        ->get();

        return DataTables::of($suppliers)
            ->addColumn('label1', function($supplier){
                $result = "";

                if($supplier->sending_stat == 1){
                    $result .= '<span class="tag tag-warning">Pending</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function($supplier){
                $result = "";

                if($supplier->audit_stat == 1){
                    $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
                }
                else if($supplier->audit_stat == 2){
                    $result .= '<span class="tag tag-success">For Closed-Out</span>';
                }
                else{
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function($supplier){
                return Carbon::parse($supplier->audit_date_from)->toFormattedDateString() . " - " . Carbon::parse($supplier->audit_date_to)->toFormattedDateString();
            })
            ->addColumn('action1', function($supplier){
                $result = '<div class="btn-group">
                          <button type="button" class="btn btn-primary dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-gear"></i>
                          </button>
                          <div class="dropdown-menu dropdown-menu-right">
                            <button class="dropdown-item aViewSuppAudit" supplier-id="' . $supplier->supplier_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalViewSuppAudit" data-keyboard="false">View</button>';
                
                if($supplier->sending_stat != 2){
                    $result .= '<button class="dropdown-item aEditSupp" supplier-id="' . $supplier->supplier_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditSuppAudit" data-keyboard="false">Edit</button>';
                }      

                $result .= '<button class="dropdown-item aRemoveSuppInBatch" supplier-id="' . $supplier->supplier_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalRemoveSuppInBatch" data-keyboard="false">Remove</button>';

                $result .= '</div>
                        </div>';

                return $result;
            })
            ->rawColumns(['action1', 'label1', 'label2', 'formatted_audit_date'])
            ->make(true);
    }

    public function get_supp_audit_by_rep_ctrl_no(Request $request){
        session_start();
        $supplier_audits = Supplier::where('audit_report_control_no', $request->audit_report_control_no)
                ->get();

        return response()->json(['supplier_audits' => $supplier_audits]);
    }

    public function view_single_supplier_audit(Request $request){
        session_start();
        $responsible_department = $request->responsible_department;

        $suppliers = Supplier::with(
                        ['supplier_departments' => function($query) use ($responsible_department){
                            $query->where('supplier_department_stat', 1);
                            if($responsible_department != 0) {
                                $query->where('responsible_department_id', $responsible_department);
                            }
                        }, 'supplier_departments.departments'])
                    ->withCount(['supplier_departments' => function($query) use ($responsible_department){
                            $query->where('supplier_department_stat', 1);
                            if($responsible_department != 0) {
                                $query->where('responsible_department_id', $responsible_department);
                            }
                        }])
                    ->where('in_batch', 0)
                    ->where('supplier_audit_stat', 1)
                    ->where('audit_stat', 1)
                    ->where('sending_stat', 1)
                    ->having('supplier_departments_count', '>', 0);

        if($request->date_from != '0' && $request->date_to != '0') {
            $suppliers = $suppliers->whereBetween('audit_date', [$request->date_from, $request->date_to]);
        }

        if($request->audit_category != '0' && $request->has('audit_category')) {
            $suppliers = $suppliers->where('audit_category', $request->audit_category);
        }

        if($request->audit_type != '0' && $request->has('audit_type')) {
            $suppliers = $suppliers->where('audit_type', $request->audit_type);
        }

        if($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
            $suppliers = $suppliers->where('purpose_of_audit', $request->purpose_of_audit);
        }

        if($request->classification != '0' && $request->has('classification')) {
            $suppliers = $suppliers->where('classification', $request->classification);
        }

        if($request->audit_stat != '0' && $request->has('audit_stat')) {
            $suppliers = $suppliers->where('audit_stat', $request->audit_stat);
        }

        if($request->audit_report_control_no != '0' && $request->has('audit_report_control_no')) {
            $suppliers = $suppliers->where('audit_report_control_no', $request->audit_report_control_no);
        }

        $suppliers = $suppliers->get();

        return DataTables::of($suppliers)
            ->addColumn('label1', function($supplier){
                $result = "";

                if($supplier->sending_stat == 1){
                    $result .= '<span class="tag tag-warning">Pending</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function($supplier){
                $result = "";

                if($supplier->audit_stat == 1){
                    $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
                }
                else if($supplier->audit_stat == 2){
                    $result .= '<span class="tag tag-success">For Closed-Out</span>';
                }
                else{
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function($supplier){
                return Carbon::parse($supplier->audit_date)->toFormattedDateString();
            })
            ->addColumn('action1', function($supplier){
                $result = '<div class="btn-group">
                          <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-cog"></i>
                          </button>
                          <div class="dropdown-menu dropdown-menu-right">
                            <button class="dropdown-item aViewSuppAudit" supplier-id="' . $supplier->supplier_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalViewSuppAudit" data-keyboard="false">View</button>';
                
                if(($supplier->sending_stat == 1 || $supplier->audit_stat == 1 || $supplier->audit_stat == 2) && $supplier->in_batch == 0) {
                    $result .= '<button class="dropdown-item aAddToBatchSuppAudit" type="button" supplier-id="' . $supplier->supplier_audit_id . '" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalAddToBatchSuppAudit" data-keyboard="false">Add to Batch</button>';
                }

                            
                $result .= '</div>
                        </div>';

                return $result;
            })
            ->rawColumns(['action1', 'label1', 'label2', 'formatted_audit_date'])
            ->make(true);
    }

    public function add_supplier_audit(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        $email_stat = 0;
        $request_approval = 1;

        if(isset($request->request_approval)){
            $request_approval = 0;
        }

        if(isset($request->send_email)){
            $email_stat = 1;
        }

        $data = $request->all();

        $validator = Validator::make($data, [
            'audit_type' => ['required', 'string', 'max:255'],
            'audit_date_from' => ['required', 'date', 'max:255'],
            'audit_date_to' => ['required', 'date', 'max:255'],
            'business_process' => ['required', 'string', 'max:255'],
            'auditors' => ['required', 'string', 'max:255'],
            'auditees' => ['required', 'string', 'max:255'],
            'audit_report_control_no' => ['required', 'string', 'max:255'],
            'classification' => ['required', 'string', 'max:255'],
            // 'category_of_findings' => ['required', 'string', 'max:255'],
            // 'evaluation_item_id' => ['required', 'string', 'max:255'],
            'audit_findings_issued_date' => ['required', 'string', 'max:255'],
            // 'deadline_of_submission' => ['required', 'string', 'max:255'], //Deadline for submission of CA Report
            // 'deadline_of_submission_days' => ['required', 'string', 'max:255'],
            // 'actual_date_of_submission' => ['required', 'string', 'max:255'],
            'iso_iatf_clause' => ['required', 'string', 'max:255'], // ISO / IATF Clause
            'statement_of_findings' => ['required', 'string'],
            // 'illustration' => ['required', 'string', 'max:255'],
            // 'img_illustration' => ['required'],
            'responsible_department' => ['required'],
            'audit_stat' => ['required'],
            'sending_stat' => ['required'],
            // 'corrective_action' => ['required', 'string', 'max:255'], //Corrective Action Effectiveness Verification
            // 'img_corrective_action' => ['required'],
        ]);

        if ($validator->passes()) {
            $ext_corr_act = "";
            if(isset($request->img_corrective_action)){
              $ext_corr_act = $request->file('img_corrective_action')->getClientOriginalExtension();
            }

            $ext_illustration = "";
            if(isset($request->img_illustration)){
              $ext_illustration = $request->file('img_illustration')->getClientOriginalExtension();
            }

            $ext_pdf_corr_act = "";
            if(isset($request->pdf_corrective_action)){
              $ext_pdf_corr_act = $request->file('pdf_corrective_action')->getClientOriginalExtension();
            }

            $ext_pdf_illustration = "";
            if(isset($request->pdf_illustration)){
              $ext_pdf_illustration = $request->file('pdf_illustration')->getClientOriginalExtension();
            }

            DB::beginTransaction();

            try{
                $img_corr_act = "";
                if(isset($request->img_corrective_action)){
                      $img_corr_act = date('YmdHis') . 'corract' . Str::random(5) . '.' . $ext_corr_act;
                }

                $img_illustration = "";
                if(isset($request->img_illustration)){
                     $img_illustration = date('YmdHis') . 'illu' . Str::random(5) . '.' . $ext_illustration;
                }

                $pdf_corr_act = "";
                if(isset($request->pdf_corrective_action)){
                      $pdf_corr_act = date('YmdHis') . 'corract' . Str::random(5) . '.' . $ext_pdf_corr_act;
                }

                $pdf_illustration = "";
                if(isset($request->pdf_illustration)){
                     $pdf_illustration = date('YmdHis') . 'illu' . Str::random(5) . '.' . $ext_pdf_illustration;
                }

                $supplier_audit_id = Supplier::on('mysql')->insertGetId([
                    'audit_type' => $request->audit_type,
                    'audit_date_from' => $request->audit_date_from,
                    'audit_date_to' => $request->audit_date_to,
                    'business_process' => $request->business_process,
                    'auditors' => $request->auditors,
                    'auditees' => $request->auditees,
                    'audit_report_control_no' => $request->audit_report_control_no,
                    'item_no' => $request->item_no,
                    'classification' => $request->classification,
                    'category_of_findings' => $request->category_of_findings,
                    'evaluation_item_id' => $request->evaluation_item_id,
                    'audit_findings_issued_date' => $request->audit_findings_issued_date,
                    'deadline_of_submission' => $request->deadline_of_submission, 
                    'deadline_of_submission_days' => $request->deadline_of_submission_days,
                    'actual_date_of_submission' => $request->actual_date_of_submission,
                    'sending_stat' => $request->sending_stat,
                    'email_stat' => $email_stat,
                    'in_batch' => 0,
                    'iso_iatf_clause' => $request->iso_iatf_clause, 
                    'statement_of_findings' => $request->statement_of_findings,
                    'illustration' => $request->illustration,
                    'img_illustration' => $img_illustration,
                    'pdf_illustration' => $pdf_illustration,
                    'root_cause' => $request->root_cause,
                    'improvement_action' => $request->improvement_plan,
                    'commitment_date' => $request->commitment_date,
                    // 'responsible_department' => $request->responsible_department,
                    'person_in_charge' => $request->txt_person_in_charge,
                    'corrective_action' => $request->corrective_action, 
                    'img_corrective_action' => $img_corr_act,
                    'pdf_corrective_action' => $pdf_corr_act,
                    'qad_approval' => $request_approval,
                    'o_s_approval' => 0,
                    'qad_approved_by' => 0,
                    'o_s_approved_by' => 0,
                    'audit_stat' => $request->audit_stat,
                    'supplier_audit_stat' => 1,
                    'created_by' => $_SESSION["rapidx_user_id"],
                    'last_updated_by' => $_SESSION["rapidx_user_id"],
                    'update_version' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                for($index = 0; $index < count($request->responsible_department); $index++) {
                    SupplierDepartments::insert([
                        'supplier_audit_id' => $supplier_audit_id,
                        'responsible_department_id' => $request->responsible_department[$index],
                        'supplier_department_stat' => 1,
                        'created_by' => $_SESSION['rapidx_user_id'],
                        'last_updated_by' => $_SESSION['rapidx_user_id'],
                        'update_version' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }

                if(isset($request->person_in_charge)){
                    for($index = 0; $index < count($request->person_in_charge); $index++) {
                        SupplierPersonInCharges::insert([
                            'supplier_audit_id' => $supplier_audit_id,
                            'person_in_charge_id' => $request->person_in_charge[$index],
                            'created_by' => $_SESSION['rapidx_user_id'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }

                DB::commit();

                if(isset($request->send_email)){
                     dispatch(new SendNewSupplierMailJob($supplier_audit_id));
                }
                
                if(isset($request->img_corrective_action)) {
                    if(!Storage::putFileAs('public/audit_result_images/supplier', $request->file('img_corrective_action'), $img_corr_act)){
                        return response()->json(['upload_result' => 0]);
                    }
                }
                if(isset($request->img_illustration)) {
                    if(!Storage::putFileAs('public/audit_result_images/supplier', $request->file('img_illustration'), $img_illustration)){
                        return response()->json(['upload_result' => 0]);
                    }
                }

                if(isset($request->pdf_corrective_action)) {
                    if(!Storage::putFileAs('public/audit_result_pdf/supplier', $request->file('pdf_corrective_action'), $pdf_corr_act)){
                        return response()->json(['upload_result' => 0]);
                    }
                }
                if(isset($request->pdf_illustration)) {
                    if(!Storage::putFileAs('public/audit_result_pdf/supplier', $request->file('pdf_illustration'), $pdf_illustration)){
                        return response()->json(['upload_result' => 0]);
                    }
                }
                return response()->json(['result' => 1]);
            }
            catch(\Exeption $e){
                DB::rollback();
                return response()->json(['result' => 0]);
            }
        }
        else{
            return response()->json(['result' => '0', 'error' => $validator->messages()]);
        }
    }

    public function add_supplier_audit_v2(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        $email_stat = 0;
        $request_approval = 1;

        $data = $request->all();

        DB::beginTransaction();

        try{
               
            if(isset($request->supplier_id)){ // update
                $extension = "";
                $pdf_extension = "";
                $image_name = "";
                $pdf_name = "";

                if(isset($request->img_corrective_action)) {
                    $extension = $request->file('img_corrective_action')->getClientOriginalExtension();
                    $image_name = date('YmdHis') . Str::random(5) . '.' . $extension;
                    if(!Storage::putFileAs('public/audit_result_images/supplier', $request->file('img_corrective_action'), $image_name)){
                        return response()->json(['upload_result' => 0]);
                    }
                }

                if(isset($request->pdf_corrective_action)) {
                    $pdf_extension = $request->file('pdf_corrective_action')->getClientOriginalExtension();
                    $pdf_name = date('YmdHis') . Str::random(5) . '.' . $pdf_extension;
                    if(!Storage::putFileAs('public/audit_result_pdf/supplier', $request->file('pdf_corrective_action'), $pdf_name)){
                        return response()->json(['upload_result' => 0]);
                    }
                }

                Supplier::on('mysql')->where('supplier_audit_id', $request->supplier_id)
                ->update([
                    'img_corrective_action' => $image_name,
                    'pdf_corrective_action' => $pdf_name,
                    'corrective_action' => $request->corrective_action,
                ]);

                if(isset($request->attachments)) {
                    for($index = 0; $index < count($request->attachments); $index++) {
                        $data = $request->attachments[$index];
                        $original_filename = $data['file']->getClientOriginalName();
                        $ext = $data['file']->getClientOriginalExtension();
                        $attachment_id = SupplierAuditAttachment::on('mysql')->insertGetId([
                            'supplier_audit_id' => $request->supplier_id,
                            'filename' => $original_filename,
                            'path' => 'audit_result_attachments/supplier/',
                            'remarks' => $data['remarks'],
                            // 'uploaded_by' => $_SESSION['rapidx_user_id'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        // Path where file will be saved
                        Storage::putFileAs('public/audit_result_attachments/supplier', $data['file'], "$attachment_id.$ext");
                    }
                }
            }
            else{
                $validator = Validator::make($data, [
                    'audit_type' => ['required', 'string', 'max:255'],
                    'audit_date_from' => ['required', 'date', 'max:255'],
                    'audit_date_to' => ['required', 'date', 'max:255'],
                    'business_process' => ['required', 'string', 'max:255'],
                    'auditors' => ['required', 'string', 'max:255'],
                    'auditees' => ['required', 'string', 'max:255'],
                    'audit_report_control_no' => ['required', 'string', 'max:255'],
                    'classification' => ['required', 'string', 'max:255'],
                    'audit_findings_issued_date' => ['required', 'string', 'max:255'],
                    // 'iso_iatf_clause' => ['required', 'string', 'max:255'], // ISO / IATF Clause
                    'responsible_department' => ['required'],
                    'audit_title' => ['required'],
                ]);

                if ($validator->passes()) {

                    $data = [
                        'audit_type' => $request->audit_type,   
                        'audit_date_from' => $request->audit_date_from,
                        'audit_date_to' => $request->audit_date_to,
                        'auditors' => $request->auditors,
                        'auditees' => $request->auditees,
                        'audit_report_control_no' => $request->audit_report_control_no,
                        'business_process' => $request->business_process,
                        'item_no' => $request->item_no,
                        'classification' => $request->classification,
                        'category_of_findings' => $request->category_of_findings,
                        'evaluation_item_id' => $request->evaluation_item_id,
                        'audit_findings_issued_date' => $request->audit_findings_issued_date,
                        'deadline_of_submission' => $request->deadline_of_submission, 
                        'deadline_of_submission_days' => $request->deadline_of_submission_days,
                        'actual_date_of_submission' => $request->actual_date_of_submission,
                        'in_batch' => 0,
                        'qad_approval' => 0,
                        'o_s_approval' => 0,
                        'qad_approved_by' => 0,
                        'o_s_approved_by' => 0,
                        'audit_stat' => 1,
                        'supplier_audit_stat' => 1,
                        'created_by' => $_SESSION["rapidx_user_id"],
                        'last_updated_by' => $_SESSION["rapidx_user_id"],
                        'update_version' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'audit_title' => $request->audit_title
                    ];

                    // if ($request->classification === 'CPAR') {
                        $data['number_OFI'] = $request->number_OFI;
                        $data['number_major_nc'] = $request->number_major_nc;
                        $data['number_minor_nc'] = $request->number_minor_nc;
                        $data['number_possitive_feedback'] = $request->number_possitive_feedback;
                    // }
                    
                    
                    $supplier_audit_id = Supplier::on('mysql')->insertGetId($data);

                    for($index = 0; $index < count($request->responsible_department); $index++) {
                        SupplierDepartments::insert([
                            'supplier_audit_id' => $supplier_audit_id,
                            'responsible_department_id' => $request->responsible_department[$index],
                            'supplier_department_stat' => 1,
                            'created_by' => $_SESSION['rapidx_user_id'],
                            'last_updated_by' => $_SESSION['rapidx_user_id'],
                            'update_version' => 1,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                    
                    if(isset($request->attachments)) {
                        for($index = 0; $index < count($request->attachments); $index++) {
                            $data = $request->attachments[$index];
                            $original_filename = $data['file']->getClientOriginalName();
                            $ext = $data['file']->getClientOriginalExtension();
                            $attachment_id = SupplierAuditAttachment::on('mysql')->insertGetId([
                                'supplier_audit_id' => $supplier_audit_id,
                                'filename' => $original_filename,
                                'path' => 'audit_result_attachments/supplier/',
                                'remarks' => $data['remarks'],
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                            // Path where file will be saved
                            Storage::putFileAs('public/audit_result_attachments/supplier', $data['file'], "$attachment_id.$ext");
                        }
                    }

                    dispatch(new SendAuditResultEmailJob($supplier_audit_id, 'supplier'));
                }
                else{
                    return response()->json(['result' => '0', 'error' => $validator->messages()]);
                }
            }
            DB::commit();
            return response()->json(['result' => 1]);
        }
        catch(Exeption $e){
            DB::rollback();
            return response()->json(['result' => 0]);
        }
        
    }

    public function send_supplier_batch(Request $request){
        session_start();
        try{
            dispatch(new SendSupplierBatchMailJob($request->supplier_audit_id));
            return response()->json(['result' => 1]);
        }
        catch(\Exception $e){
            return response()->json(['result' => $e]);
        }
    }

    public function approve_supplier_batch(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();
        DB::beginTransaction();

        try{
            if($request->user_access == 4){
                Supplier::on('mysql')->whereIn('supplier_audit_id', $request->supplier_audit_id)
                    ->increment('update_version', 1, 
                        [
                            'qad_approval' => 1,
                            'qad_approved_by' => $_SESSION["rapidx_user_id"],
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]
                    );
            }
            else{
                Supplier::on('mysql')->whereIn('supplier_audit_id', $request->supplier_audit_id)
                    ->increment('update_version', 1, 
                        [
                            'o_s_approval' => 1,
                            'o_s_approved_by' => $_SESSION["rapidx_user_id"],
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]
                    );
            }
            DB::commit();
            return response()->json(['result' => "1"]);
        }
        catch(\Exception $e) {
            DB::rollback();
            // throw $e;
            return response()->json(['result' => "0"]);
        }  
    }

    public function edit_supplier_audit(Request $request){
        session_start();

        $data = $request->all();

        $validator = Validator::make($data, [
            'audit_type' => ['required', 'string', 'max:255'],
            'audit_date_from' => ['required', 'date', 'max:255'],
            'audit_date_to' => ['required', 'date', 'max:255'],
            'business_process' => ['required', 'string', 'max:255'],
            'auditors' => ['required', 'string', 'max:255'],
            'auditees' => ['required', 'string', 'max:255'],
            'audit_report_control_no' => ['required', 'string', 'max:255'],
            'classification' => ['required', 'string', 'max:255'],
            // 'category_of_findings' => ['required', 'string', 'max:255'],
            // 'evaluation_item_id' => ['required', 'string', 'max:255'],
            'audit_findings_issued_date' => ['required', 'string', 'max:255'],
            // 'deadline_of_submission' => ['required', 'string', 'max:255'], //Deadline for submission of CA Report
            // 'deadline_of_submission_days' => ['required', 'string', 'max:255'],
            'iso_iatf_clause' => ['required', 'string', 'max:255'], // ISO / IATF Clause
            'statement_of_findings' => ['required', 'string'],
            'responsible_department' => ['required'],
            'audit_stat' => ['required'],
            'sending_stat' => ['required'],
            // 'illustration' => ['required', 'string'],
        ]);

        if ($validator->passes()) {

            DB::beginTransaction();

            try{
                $ext_illustration = "";
                $img_illustration = "";
                if(!isset($request->remove_img_illustration)){

                    $ext_illustration = "";
                    $img_illustration = "";

                    if(isset($request->img_illustration)){
                        $ext_illustration = $request->file('img_illustration')->getClientOriginalExtension();
                        $img_illustration = date('YmdHis') . 'illu' . Str::random(5) . '.' . $ext_illustration;
                    }
                    else{
                        if($request->current_img_illustration != "" && isset($request->current_img_illustration)){
                            $img_illustration = $request->current_img_illustration;
                        }
                        else{
                            $img_illustration = "";
                        }
                    }
                }
                else{
                    $img_illustration = "";
                }

                $ext_corr_act = "";
                $img_corr_act = "";
                if(!isset($request->remove_img_corrective_action)){
                    $ext_corr_act = "";
                    if(isset($request->img_corrective_action)){
                        $ext_corr_act = $request->file('img_corrective_action')->getClientOriginalExtension();
                    }
                    $img_corr_act = "";
                    if(isset($request->img_corrective_action)){
                        $img_corr_act = date('YmdHis') . 'corract' . Str::random(5) . '.' . $ext_corr_act;
                    }
                    else{
                        if($request->current_img_corrective_action != "" && isset($request->current_img_corrective_action)){
                            $img_corr_act = $request->current_img_corrective_action;
                        }
                        else{
                            $img_corr_act = "";
                        }
                    }
                }
                else{
                    $img_corr_act = "";
                }

                // PDF Files
                $ext_pdf_illustration = "";
                $pdf_illustration = "";
                if(!isset($request->remove_pdf_illustration)){

                    $ext_pdf_illustration = "";
                    $pdf_illustration = "";

                    if(isset($request->pdf_illustration)){
                        $ext_pdf_illustration = $request->file('pdf_illustration')->getClientOriginalExtension();
                        $pdf_illustration = date('YmdHis') . 'illu' . Str::random(5) . '.' . $ext_pdf_illustration;
                    }
                    else{
                        if($request->current_pdf_illustration != "" && isset($request->current_pdf_illustration)){
                            $pdf_illustration = $request->current_pdf_illustration;
                        }
                        else{
                            $pdf_illustration = "";
                        }
                    }
                }
                else{
                    $pdf_illustration = "";
                }

                $ext_pdf_corr_act = "";
                $pdf_corr_act = "";
                if(!isset($request->remove_pdf_corrective_action)){
                    $ext_pdf_corr_act = "";
                    if(isset($request->pdf_corrective_action)){
                        $ext_pdf_corr_act = $request->file('pdf_corrective_action')->getClientOriginalExtension();
                    }
                    $pdf_corr_act = "";
                    if(isset($request->pdf_corrective_action)){
                        $pdf_corr_act = date('YmdHis') . 'corract' . Str::random(5) . '.' . $ext_pdf_corr_act;
                    }
                    else{
                        if($request->current_pdf_corrective_action != "" && isset($request->current_pdf_corrective_action)){
                            $pdf_corr_act = $request->current_pdf_corrective_action;
                        }
                        else{
                            $pdf_corr_act = "";
                        }
                    }
                }
                else{
                    $pdf_corr_act = "";
                }

                Supplier::on('mysql')->where('supplier_audit_id', $request->supplier_audit_id)
                    ->increment('update_version', 1,
                    [
                        'audit_type' => $request->audit_type,
                        'audit_date_from' => $request->audit_date_from,
                        'audit_date_to' => $request->audit_date_to,
                        'business_process' => $request->business_process,
                        'auditors' => $request->auditors,
                        'auditees' => $request->auditees,
                        'audit_report_control_no' => $request->audit_report_control_no,
                        'item_no' => $request->item_no,
                        'classification' => $request->classification,
                        'category_of_findings' => $request->category_of_findings,
                        'audit_findings_issued_date' => $request->audit_findings_issued_date,
                        'deadline_of_submission' => $request->deadline_of_submission,
                        'deadline_of_submission_days' => $request->deadline_of_submission_days,
                        'actual_date_of_submission' => $request->actual_date_of_submission,
                        'iso_iatf_clause' => $request->iso_iatf_clause, 
                        'statement_of_findings' => $request->statement_of_findings,
                        'illustration' => $request->illustration,
                        'img_illustration' => $img_illustration,
                        'pdf_illustration' => $pdf_illustration,
                        'root_cause' => $request->root_cause,
                        'improvement_action' => $request->improvement_plan,
                        'commitment_date' => $request->commitment_date,
                        'audit_stat' => $request->audit_stat,
                        'sending_stat' => $request->sending_stat,
                        // 'responsible_department' => $request->responsible_department,
                        'person_in_charge' => $request->txt_person_in_charge,
                        'corrective_action' => $request->corrective_action, 
                        'img_corrective_action' => $img_corr_act,
                        'pdf_corrective_action' => $pdf_corr_act,
                        'last_updated_by' => $_SESSION["rapidx_user_id"],
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );

                $suppliers = Supplier::on('mysql')
                    ->with(['supplier_departments'])
                    ->get();

                SupplierDepartments::on('mysql')->where('supplier_audit_id', $request->supplier_audit_id)
                    ->whereNotIn('responsible_department_id', $request->responsible_department)
                    ->increment('update_version', 1, 
                        [
                            'supplier_department_stat' => 2,
                            'updated_at' => date('Y-m-d H:i:s'),
                            'last_updated_by' => $_SESSION['rapidx_user_id'],
                        ]
                    );

                SupplierDepartments::on('mysql')->where('supplier_audit_id', $request->supplier_audit_id)
                    ->whereIn('responsible_department_id', $request->responsible_department)
                    ->increment('update_version', 1, 
                        [
                            'supplier_department_stat' => 1,
                            'updated_at' => date('Y-m-d H:i:s'),
                            'last_updated_by' => $_SESSION['rapidx_user_id'],
                        ]
                    );

                for($index = 0; $index < count($request->responsible_department); $index++) {
                    $supplier_depts = SupplierDepartments::on('mysql')
                        ->where('supplier_audit_id', $request->supplier_audit_id)
                        ->where('responsible_department_id', $request->responsible_department[$index])
                        ->get();

                    if(count($supplier_depts) <= 0) {
                        SupplierDepartments::insert([
                            'supplier_audit_id' => $request->supplier_audit_id,
                            'responsible_department_id' => $request->responsible_department[$index],
                            'supplier_department_stat' => 1,
                            'created_by' => $_SESSION['rapidx_user_id'],
                            'last_updated_by' => $_SESSION['rapidx_user_id'],
                            'update_version' => 1,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }

                if(isset($request->person_in_charge)){
                    SupplierPersonInCharges::whereNotIn('person_in_charge_id', $request->person_in_charge)
                        ->where('supplier_audit_id', $request->supplier_audit_id)
                        ->delete();

                    for($index = 0; $index < count($request->person_in_charge); $index++) {
                        $supplier_per_in_charges = SupplierPersonInCharges::where('supplier_audit_id', $request->supplier_audit_id)
                            ->where('person_in_charge_id', $request->person_in_charge[$index])
                            ->get();

                        if(count($supplier_per_in_charges) <= 0) {
                            SupplierPersonInCharges::insert([
                                'supplier_audit_id' => $request->supplier_audit_id,
                                'person_in_charge_id' => $request->person_in_charge[$index],
                                'created_by' => $_SESSION['rapidx_user_id'],
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                        }

                    }
                }
                else{
                    SupplierPersonInCharges::where('supplier_audit_id', $request->supplier_audit_id)
                        ->delete();
                }

                DB::commit();

                if(!isset($request->remove_img_corrective_action)){
                    if(isset($request->img_corrective_action)){
                        if(!Storage::putFileAs('public/audit_result_images/supplier', $request->file('img_corrective_action'), $img_corr_act)){
                            return response()->json(['upload_result' => 0]);
                        }
                    }
                }
                if(!isset($request->remove_img_illustration)){
                    if(isset($request->img_illustration)){
                        if(!Storage::putFileAs('public/audit_result_images/supplier', $request->file('img_illustration'), $img_illustration)){
                            return response()->json(['upload_result' => 0]);
                        }
                    }
                }

                // PDF File
                if(!isset($request->remove_pdf_corrective_action)){
                    if(isset($request->pdf_corrective_action)){
                        if(!Storage::putFileAs('public/audit_result_pdf/supplier', $request->file('pdf_corrective_action'), $pdf_corr_act)){
                            return response()->json(['upload_result' => 0]);
                        }
                    }
                }
                if(!isset($request->remove_pdf_illustration)){
                    if(isset($request->pdf_illustration)){
                        if(!Storage::putFileAs('public/audit_result_pdf/supplier', $request->file('pdf_illustration'), $pdf_illustration)){
                            return response()->json(['upload_result' => 0]);
                        }
                    }
                }
                return response()->json(['result' => 1]);
            }
            catch(\Exeption $e){
                DB::rollback();
                return response()->json(['result' => 0]);
            }
        }
        else{
            return response()->json(['result' => '0', 'error' => $validator->messages()]);
        }
    }

    public function close_supplier_audit(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();
        DB::beginTransaction();

        try{
            Supplier::on('mysql')->where('supplier_audit_id', $request->supplier_audit_id)
                ->increment('update_version', 1, 
                    [
                        'audit_stat' => 3,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );
            DB::commit();
            return response()->json(['result' => "1"]);
        }
        catch(\Exception $e) {
            DB::rollback();
            // throw $e;
            return response()->json(['result' => "0"]);
        }  
    }

    public function open_supplier_audit(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();
        DB::beginTransaction();

        try{
            Supplier::on('mysql')->where('supplier_audit_id', $request->supplier_audit_id)
                ->increment('update_version', 1, 
                    [
                        'audit_stat' => 1,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );
            DB::commit();
            return response()->json(['result' => "1"]);
        }
        catch(\Exception $e) {
            DB::rollback();
            // throw $e;
            return response()->json(['result' => "0"]);
        }  
    }

    public function done_supplier_audit(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();
        DB::beginTransaction();

        try{
            Supplier::on('mysql')->where('supplier_audit_id', $request->supplier_audit_id)
                ->increment('update_version', 1, 
                    [
                        'sending_stat' => 2,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );
            // sending mail codes here...
            DB::commit();
            return response()->json(['result' => "1"]);
        }
        catch(\Exception $e) {
            DB::rollback();
            // throw $e;
            return response()->json(['result' => "0"]);
        }  
    }

    public function pend_supplier_audit(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();
        DB::beginTransaction();

        try{
            Supplier::on('mysql')->where('supplier_audit_id', $request->supplier_audit_id)
                ->increment('update_version', 1, 
                    [
                        'sending_stat' => 1,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );
            // sending mail codes here...
            DB::commit();
            return response()->json(['result' => "1"]);
        }
        catch(\Exception $e) {
            DB::rollback();
            // throw $e;
            return response()->json(['result' => "0"]);
        }  
    }

    public function get_supplier_audit_by_id(Request $request){
        session_start();
        $supplier_audits = Supplier::on('mysql')
                    ->with(
                        ['supplier_departments' => function($query){
                            $query->where('supplier_department_stat', 1);
                        }, 'supplier_departments.departments', 'user_created_by', 'user_last_updated_by', 'person_in_charges', 'person_in_charges.person_in_charge_record'])
                ->where('supplier_audit_id', $request->supplier_audit_id)
                ->get();

        return response()->json(['supplier_audits' => $supplier_audits]);
    }

    // public function view_open_supplier_audit_by_stat(Request $request){
    //     session_start();
    //     $is_approver = false;
    //     $responsible_department[] = $_SESSION['rapidx_department_id'];
    //     $user_id = $_SESSION['rapidx_user_id'];

    //     $approvers = Approver::where('approver', $user_id)
    //                             ->where('approver_stat', 1)
    //                             ->get();

    //     // return $approvers;

    //     if(count($approvers) > 0){
    //         $is_approver = true;
    //         // $responsible_department = [];
    //         for($index = 0; $index < count($approvers); $index++){
    //             $responsible_department[] = $approvers[$index]->department_id;
    //         }
    //     }

    //     // $responsible_department = $_SESSION['rapidx_department_id'];

    //     $suppliers = Supplier::on('mysql')
    //                 ->with(
    //                     ['supplier_departments' => function($query) use ($responsible_department){
    //                         $query->where('supplier_department_stat', 1);
    //                         // if($responsible_department != 0) {
    //                             $query->whereIn('responsible_department_id', $responsible_department);
    //                         // }
    //                     }, 'supplier_departments.departments'])
    //                 ->withCount(['supplier_departments' => function($query) use ($responsible_department){
    //                         $query->where('supplier_department_stat', 1);
    //                         // if($responsible_department != 0) {
    //                             $query->whereIn('responsible_department_id', $responsible_department);
    //                         // }
    //                     }])
    //                 ->where('qad_approval', 1)
    //                 ->where('supplier_audit_stat', 1)
    //                 ->having('supplier_departments_count', '>', 0);

    //     if($request->date_from != '0'){
    //         $suppliers = $suppliers->where('audit_date_from', '>=', $request->date_from);
    //     }

    //     if($request->date_to != '0'){
    //         $suppliers = $suppliers->where('audit_date_to', '<=', $request->date_to);
    //     }

    //     if($request->audit_category != '0' && $request->has('audit_category')) {
    //         $suppliers = $suppliers->where('audit_category', $request->audit_category);
    //     }

    //     if($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
    //         $suppliers = $suppliers->where('purpose_of_audit', $request->purpose_of_audit);
    //     }

    //     if($request->classification != '0' && $request->has('classification')) {
    //         $suppliers = $suppliers->where('classification', $request->classification);
    //     }

    //     if($request->audit_stat != '0' && $request->has('audit_stat')) {
    //         $suppliers = $suppliers->where('audit_stat', $request->audit_stat);
    //     }

    //     $suppliers = $suppliers->get();

    //     return DataTables::of($suppliers)
    //         ->addColumn('label1', function($supplier){
    //             $result = "";

    //             if($supplier->sending_stat == 1){
    //                 $result .= '<span class="tag tag-warning">Pending</span>';
    //             }
    //             else{
    //                 $result .= '<span class="tag tag-success">Done</span>';
    //             }

    //             return $result;
    //         })
    //         ->addColumn('label2', function($supplier){
    //             $result = "";

    //             if($supplier->audit_stat == 1){
    //                 $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
    //             }
    //             else if($supplier->audit_stat == 2){
    //                 $result .= '<span class="tag tag-success">For Closed-Out</span>';
    //             }
    //             else{
    //                 $result .= '<span class="tag tag-primary">Closed</span>';
    //             }

    //             return $result;
    //         })
    //         ->addColumn('formatted_audit_date', function($supplier){
    //             return Carbon::parse($supplier->audit_date)->toFormattedDateString();
    //         })
    //         ->addColumn('action1', function($supplier) use($is_approver){
    //             $result = '<div class="btn-group">
    //                       <button type="button" class="btn btn-primary dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    //                         <i class="icon-gear"></i>
    //                       </button>
    //                       <div class="dropdown-menu dropdown-menu-right">
    //                         <button class="dropdown-item aViewSuppAudit" supplier-id="' . $supplier->supplier_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalViewSuppAudit" data-keyboard="false">View</button>';

    //             if($supplier->sending_stat != 2){
    //                 if(($supplier->audit_stat == 2 && $supplier->qad_approval == 1) && $is_approver == true){
    //                     if($supplier->audit_stat == 2 && $supplier->o_s_approval == 0 && $is_approver == true){
    //                         $result .= '<button class="dropdown-item aEditSupp" supplier-id="' . $supplier->supplier_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditSuppAudit" data-keyboard="false">Edit</button>';
    //                     }
    //                     else if($supplier->audit_stat == 2 && $supplier->o_s_approval == 0 && $is_approver == false){
    //                         $result .= '<button class="dropdown-item aEditSupp" supplier-id="' . $supplier->supplier_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditSuppAudit" data-keyboard="false">Edit</button>';
    //                     }
    //                 }
    //                 else if($supplier->audit_stat == 1 && $supplier->qad_approval == 1){
    //                     $result .= '<button class="dropdown-item aEditSupp" supplier-id="' . $supplier->supplier_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditSuppAudit" data-keyboard="false">Edit</button>';
    //                 }
    //             }

                            
    //             $result .= '</div>
    //                     </div>';

    //             return $result;
    //         })
    //         ->addColumn('approval_stat', function($supplier){
    //             $result = "";

    //             if($supplier->audit_stat == 1 && $supplier->qad_approval == 1){
    //                 $result .= '<span class="tag tag-success">Approved By QAD</span>';
    //             }
    //             else if($supplier->audit_stat == 1 && $supplier->qad_approval == 0){
    //                 $result .= '<span class="tag tag-warning">For QAD Approval</span>';
    //             }
    //             else if($supplier->audit_stat == 2 && $supplier->o_s_approval == 1){
    //                 $result .= '<span class="tag tag-success">Approved By Section Head</span>';
    //             }
    //             else if($supplier->audit_stat == 2 && $supplier->o_s_approval == 0){
    //                 $result .= '<span class="tag tag-warning">For Section Head Approval</span>';
    //             }
    //             else{
    //                 $result .= '<span class="tag tag-success">Approved By QAD</span>';
    //             }

    //             return $result;
    //         })
    //         ->rawColumns(['action1', 'label1', 'label2', 'formatted_audit_date', 'approval_stat'])
    //         ->make(true);
    // }

    public function view_open_supplier_audit_by_stat(Request $request){
        session_start();
        $user_access = $request->user_access;

        $is_approver = false;
        $responsible_department[] = $_SESSION['rapidx_department_id'];
        $user_id = $_SESSION['rapidx_user_id'];

        $approvers = Approver::where('approver', $_SESSION["rapidx_user_id"])
                    ->where('approver_stat', '1')
                    ->get();

        if(count($approvers) > 0){
            $is_approver = true;
            for($index = 0; $index < count($approvers); $index++){
                $responsible_department[] = $approvers[$index]->department_id;
            }
        }

        $suppliers = Supplier::on('mysql')
                    ->with(
                        ['supplier_departments' => function($query) use ($responsible_department){
                            $query->where('supplier_department_stat', 1);
                            // if($responsible_department != 0) {
                                $query->whereIn('responsible_department_id', $responsible_department);
                            // }
                        }, 'supplier_departments.departments'])
                    ->withCount(['supplier_departments' => function($query) use ($responsible_department){
                            $query->where('supplier_department_stat', 1);
                            // if($responsible_department != 0) {
                                $query->whereIn('responsible_department_id', $responsible_department);
                            // }
                        }])
                    ->where('qad_approval', 1)
                    ->having('supplier_departments_count', '>', 0);

        if($request->date_from != '0'){
            $suppliers = $suppliers->where('audit_date_from', '>=', $request->date_from);
        }

        if($request->date_to != '0'){
            $suppliers = $suppliers->where('audit_date_to', '<=', $request->date_to);
        }

        if($request->audit_category != '0' && $request->has('audit_category')) {
            $suppliers = $suppliers->where('audit_category', $request->audit_category);
        }

        if($request->audit_type != '0' && $request->has('audit_type')) {
            $suppliers = $suppliers->where('audit_type', $request->audit_type);
        }

        if($request->audit_type_pref_rep_ctrl_no != '0' && $request->has('audit_type_pref_rep_ctrl_no')) {
            $suppliers = $suppliers->where('audit_report_control_no', 'LIKE', $request->audit_type_pref_rep_ctrl_no . "%");
        }

        if($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
            $suppliers = $suppliers->where('purpose_of_audit', $request->purpose_of_audit);
        }

        if($request->classification != '0' && $request->has('classification')) {
            $suppliers = $suppliers->where('classification', $request->classification);
        }

        if($request->audit_stat != '0' && $request->has('audit_stat')) {
            $suppliers = $suppliers->where('audit_stat', $request->audit_stat);
        }

        if($request->audit_report_control_no != '0' && $request->has('audit_report_control_no')) {
            $suppliers = $suppliers->where('audit_report_control_no', $request->audit_report_control_no);
        }

        if($request->item_no != '0' && $request->has('item_no')) {
            $suppliers = $suppliers->where('item_no', $request->item_no);
        }

        if($request->nc_control_no != '0' && $request->has('nc_control_no')) {
            $suppliers = $suppliers->where('nc_control_no', $request->nc_control_no);
        }

        if($request->evaluation_item != null && $request->has('evaluation_item')) {
            $tuvs = $tuvs->where('evaluation_item_id', $request->evaluation_item);
        }

        if($request->approval_stat != '0' && $request->has('approval_stat')) {
            if($request->approval_stat == 1){
                $suppliers = $suppliers->where(function($query){
                    $query->where('audit_stat', 2);
                    $query->where('o_s_approval', 0);
                });
            }
        }

        // if($request->evaluation_item != null && $request->has('evaluation_item')) {
        //     $suppliers = $suppliers->where('category_of_findings', $request->evaluation_item);
        // }

        $suppliers = $suppliers->where('supplier_audit_stat', 1);

        $suppliers = $suppliers->get();

        return DataTables::of($suppliers)
            ->addColumn('label1', function($supplier){
                $result = "";

                if($supplier->sending_stat == 1){
                    $result .= '<span class="tag tag-warning">Pending</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function($supplier){
                $result = "";

                if($supplier->audit_stat == 1){
                    $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
                }
                else if($supplier->audit_stat == 2){
                    $result .= '<span class="tag tag-success">For Closed-Out</span>';
                }
                else{
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function($supplier){
                $result = '';
                if($supplier->audit_date_from != $supplier->audit_date_to){
                    $result = Carbon::parse($supplier->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($supplier->audit_date_to)->toFormattedDateString();
                }
                else{
                    $result = Carbon::parse($supplier->audit_date_from)->toFormattedDateString();
                }
                return $result;
            })
            ->addColumn('action1', function($supplier) use($is_approver, $user_access){
                $final = '';
                $final = '<button class="btn-actions btn btn-sm btn-info aViewSuppAudit" supplier-id="' . $supplier->supplier_audit_id . '" type="button" data-toggle="modal" data-target="#modalViewSuppAudit" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="View" title="View"><i class="icon-file-text-o"></i></button> ';

                if($supplier->audit_stat == 1){
                    $final .= '<button class="btn-actions btn btn-sm btn-success aEditSupp" supplier-id="' . $supplier->supplier_audit_id . '" type="button" data-toggle="modal" data-target="#modalEditSuppAudit" data-keyboard="false" title="Edit"><i class="icon-edit2"></i></button> ';
                }
                else{
                    if($is_approver){
                        if($supplier->audit_stat == 2 && $supplier->o_s_approval == 0) {
                            $final .= '<button class="btn-actions btn btn-sm btn-success aEditSupp" supplier-id="' . $supplier->supplier_audit_id . '" type="button" data-toggle="modal" data-target="#modalEditSuppAudit" data-keyboard="false" title="Edit"><i class="icon-edit2"></i></button> ';
                        }
                    }
                }

                // Working, uncomment if will use
                if($is_approver && $user_access == 5){
                    if($supplier->o_s_approval == '0' && $supplier->audit_stat == 2){

                        $final .= '<button class="btn-actions btn btn-sm btn-primary aOSApprovalSuppAudit" type="button" supplier-id="' . $supplier->supplier_audit_id . '" o-s-approval="1" data-toggle="modal" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="Approve" title="Approve"><i class="icon-like"></i></button> '; 
                    }
                }
                return $final;
            })
            ->addColumn('check_box_send', function($supplier){
                $result = "";

                if($supplier->qad_approval == '0'){
                    $result = "disabled";
                }

                if($supplier->deadline_of_submission == null){
                    $result = "disabled";
                }

                return '<center><input type="checkbox" class="chkSendIntAudit" supplier-id="' . $supplier->supplier_audit_id . '" ' . $result . '></center>';
            })
            ->addColumn('approval_stat', function($supplier){
                $result = "";

                if($supplier->audit_stat == 1 && $supplier->qad_approval == 1){
                    $result .= '<span class="tag tag-success">Approved By QAD</span>';
                }
                else if($supplier->audit_stat == 1 && $supplier->qad_approval == 0){
                    $result .= '<span class="tag tag-warning">For QAD Approval</span>';
                }
                else if($supplier->audit_stat == 2 && $supplier->o_s_approval == 1){
                    $result .= '<span class="tag tag-success">Approved By Section Head</span>';
                }
                else if($supplier->audit_stat == 2 && $supplier->o_s_approval == 0){
                    $result .= '<span class="tag tag-warning">For Section Head Approval</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Approved By QAD</span>';
                }

                return $result;
            })
            ->rawColumns(['action1', 'label1', 'label2', 'formatted_audit_date', 'check_box_send', 'approval_stat'])
            ->make(true);
    }

    public function edit_open_supplier_audit(Request $request){
        session_start();
        date_default_timezone_set('Asia/Manila');
        $data = $request->all();

        $validator = Validator::make($data, [
            // 'root_cause' => ['required', 'string'],
            // 'improvement_plan' => ['required', 'string'],
            // 'commitment_date' => ['required', 'string'],
            // 'person_in_charge' => ['required'],
            // 'audit_stat' => ['required', 'string'],
        ]);

        if ($validator->passes()) {
            DB::beginTransaction();

            try{
                if(isset($request->audit_stat)){
                    Supplier::where('supplier_audit_id', $request->supplier_audit_id)
                        ->increment('update_version', 1,
                        [
                            'root_cause' => $request->root_cause,
                            'improvement_action' => $request->improvement_plan,
                            'commitment_date' => $request->commitment_date,
                            'audit_stat' => $request->audit_stat,
                            'last_updated_by' => $_SESSION["rapidx_user_id"],
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]
                    );
                }
                else{
                    Supplier::where('supplier_audit_id', $request->supplier_audit_id)
                        ->increment('update_version', 1,
                        [
                            'root_cause' => $request->root_cause,
                            'improvement_action' => $request->improvement_plan,
                            'commitment_date' => $request->commitment_date,
                            'last_updated_by' => $_SESSION["rapidx_user_id"],
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]
                    );
                }

                if(isset($request->person_in_charge)){
                    SupplierPersonInCharges::whereNotIn('person_in_charge_id', $request->person_in_charge)
                        ->where('supplier_audit_id', $request->supplier_audit_id)
                        ->delete();

                    for($index = 0; $index < count($request->person_in_charge); $index++) {
                        $supplier_per_in_charges = SupplierPersonInCharges::where('supplier_audit_id', $request->supplier_audit_id)
                            ->where('person_in_charge_id', $request->person_in_charge[$index])
                            ->get();

                        if(count($supplier_per_in_charges) <= 0) {
                            SupplierPersonInCharges::insert([
                                'supplier_audit_id' => $request->supplier_audit_id,
                                'person_in_charge_id' => $request->person_in_charge[$index],
                                'created_by' => $_SESSION['rapidx_user_id'],
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                        }

                    }
                }
                else{
                    SupplierPersonInCharges::where('supplier_audit_id', $request->supplier_audit_id)
                        ->delete();
                }

                DB::commit();
                return response()->json(['result' => 1]);
            }
            catch(\Exeption $e){
                DB::rollback();
                return response()->json(['result' => 0]);
            }
        }
        else{
            return response()->json(['result' => '0', 'error' => $validator->messages()]);
        }
    }

    public function get_chart_supplier_audit_by_stat(Request $request){
        session_start();
        date_default_timezone_set('Asia/Manila');

        $year = $request->year;
        $month_from = 04;
        $month_to = 03;
      
        $date_from = date($year . '-' . $month_from . '-' . '01');
        $date_to = date(($year + 1) . '-' . $month_to . '-' . 't');
      
        $date_create_from = date_create($date_from);
        $date_create_to = date_create($date_to);

        $suppliers = Supplier::with(
                        ['supplier_departments' => function($query){
                            $query->where('supplier_department_stat', 1);
                        }, 'supplier_departments.departments'])
                    ->withCount(['supplier_departments' => function($query){
                            $query->where('supplier_department_stat', 1);
                        }])
                    ->having('supplier_departments_count', '>', 0)
                    ->where('audit_date_from', '>=', $date_create_from)
                    ->where('audit_date_to', '<=', $date_create_to)
                    ->get();

        return response()->json(['suppliers' => $suppliers]);
    }

    public function view_supplier_audit_history(Request $request){
        session_start();
        
        $suppliers = Supplier::on('mysql')->where('responsible_department', $_SESSION["rapidx_department_id"])
                        ->where('audit_stat', 2)
                        ->where('sending_stat', 2)
                        ->get();

        return DataTables::of($suppliers)
            ->addColumn('label1', function($supplier){
                $result = "";

                if($supplier->sending_stat == 1){
                    $result .= '<span class="tag tag-warning">Pending</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function($supplier){
                $result = "";

                if($supplier->audit_stat == 1){
                    $result .= '<span class="tag tag-warning">Open</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Closed</span>';
                }

                return $result;
            })
            ->addColumn('action1', function($supplier){
                // $result = '<div class="btn-group">
                //           <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                //             Action
                //           </button>
                //           <div class="dropdown-menu dropdown-menu-right">
                //             <button class="dropdown-item aViewSuppAudit" supplier-id="' . $supplier->supplier_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalViewSuppAudit" data-keyboard="false">View</button>';   

                // $result .= '</div>
                //         </div>';

                return '<button class="btn btn-primary aViewSuppAudit" supplier-id="' . $supplier->supplier_audit_id . '" type="button" data-toggle="modal" data-target="#modalViewSuppAudit" data-keyboard="false">View</button>';
            })
            ->rawColumns(['action1', 'label1', 'label2'])
            ->make(true);
    }

    public function view_report_supplier_audit_by_stat(Request $request){
        session_start();
        $responsible_department = $request->responsible_department;

        $suppliers = Supplier::on('mysql')
                    ->with(
                        ['supplier_departments' => function($query) use ($responsible_department){
                            $query->where('supplier_department_stat', 1);
                            if($responsible_department != 0) {
                                $query->whereIn('responsible_department_id', $responsible_department);
                            }
                        }, 'supplier_departments.departments', 'person_in_charges', 'person_in_charges.person_in_charge_record'])
                    ->withCount(['supplier_departments' => function($query) use ($responsible_department){
                            $query->where('supplier_department_stat', 1);
                            if($responsible_department != 0) {
                                $query->whereIn('responsible_department_id', $responsible_department);
                            }
                        }])
                    ->having('supplier_departments_count', '>', 0);

        if($request->date_from != '0'){
            $suppliers = $suppliers->where('audit_date_from', '>=', $request->date_from);
        }

        if($request->date_to != '0'){
            $suppliers = $suppliers->where('audit_date_to', '<=', $request->date_to);
        }

        if($request->audit_category != '0' && $request->has('audit_category')) {
            $suppliers = $suppliers->where('audit_category', $request->audit_category);
        }

        if($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
            $suppliers = $suppliers->where('purpose_of_audit', $request->purpose_of_audit);
        }

        if($request->classification != '0' && $request->has('classification')) {
            $suppliers = $suppliers->where('classification', $request->classification);
        }

        if($request->audit_stat != '0' && $request->has('audit_stat')) {
            $suppliers = $suppliers->where('audit_stat', $request->audit_stat);
        }

        $suppliers = $suppliers->get();

        return DataTables::of($suppliers)
            ->addColumn('label1', function($supplier){
                $result = "";

                if($supplier->sending_stat == 1){
                    $result .= '<span class="tag tag-warning">Pending</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function($supplier){
                $result = "";

                if($supplier->audit_stat == 1){
                    $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
                }
                else if($supplier->audit_stat == 2){
                    $result .= '<span class="tag tag-success">For Closed-Out</span>';
                }
                else{
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function($supplier){
                return Carbon::parse($supplier->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($supplier->audit_date_to)->toFormattedDateString();
            })
            ->rawColumns(['label1', 'label2', 'formatted_audit_date'])
            ->make(true);
    }

    public function change_supplier_audit_stat(Request $request) {
        date_default_timezone_set('Asia/Manila');
        session_start();
        DB::beginTransaction();

        try{
            Supplier::where('supplier_audit_id', $request->supplier_audit_id)
                ->increment('update_version', 1, 
                    [
                        'supplier_audit_stat' => $request->supplier_audit_stat,
                        'last_updated_by' => $_SESSION["rapidx_user_id"],
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );
            DB::commit();
            return response()->json(['result' => "1"]);
        }
        catch(\Exception $e) {
            DB::rollback();
            // throw $e;
            return response()->json(['result' => "0"]);
        } 
    }

    public function get_supplier_unique_evaluation_items(Request $request){
        session_start();
        $supplier_audits = Supplier::select('evaluation_item')->distinct()->get();

        return response()->json(['supplier_audits' => $supplier_audits]);
    }
}
