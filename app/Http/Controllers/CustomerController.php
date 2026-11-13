<?php

namespace App\Http\Controllers;

use App\Exports\AuditResultExport;
use App\Jobs\SendAuditResultEmailJob;
use App\Jobs\SendCustomerBatchMailJob;
use App\Jobs\SendNewCustomerMailJob;
use App\Mail\SendCustomerBatchMail;
use App\Model\Approver;
use App\Model\Customer;
use App\Model\CustomerAuditAttachment;
use App\Model\CustomerDepartments;
use App\Model\CustomerList;
use App\Model\CustomerPersonInCharges;
use App\Model\Department;
use App\Model\EmailRecipient;
use App\User;
use Carbon\Carbon;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Mail;

date_default_timezone_set('Asia/Manila');
class CustomerController extends Controller
{
    //
    public function update_all_person_in_charge(Request $request)
    {
        $customer_audits = Customer::on('mysql')
                    ->with(
                        [
                            'person_in_charges',
                            'person_in_charges.person_in_charge_record'
                        ])
                    ->get();

        $col_data = collect($customer_audits);

        DB::beginTransaction();
        try {
            $arr_test = [];
            for($index = 0; $index < count($col_data); $index++){
                $id = $col_data[$index]->customer_audit_id;
                $arr_person_in_charges_name = [];

                $person_in_charge_names = null;

                for($index2 = 0; $index2 < count($col_data[$index]->person_in_charges); $index2++){
                    $arr_person_in_charges_name[] = $col_data[$index]->person_in_charges[$index2]->person_in_charge_record->name;
                }

                if(count($arr_person_in_charges_name) > 0){
                    $person_in_charge_names = implode(', ', $arr_person_in_charges_name);
                    $arr_test[] = $person_in_charge_names;
                }

                Customer::where('customer_audit_id', $id)
                    ->update([
                        'person_in_charge' => $person_in_charge_names,
                    ]);
            }


            DB::commit();
            return $arr_test;
            return 'Saved!';
        } catch (Exception $e) {
            DB::rollback();
            return $e->messages();
        }

    }

    public function customer_export(Request $request)
    {
        date_default_timezone_set('Asia/Manila');
        session_start();
        
        $responsible_department = 0;
        if($request->responsible_department != '0' && $request->has('responsible_department')) {
           $responsible_department = $request->responsible_department;
           $responsible_department = explode(",", $responsible_department);
        }

        $customers = Customer::on('mysql')
                    ->with(
                        ['customer_departments' => function($query) use ($responsible_department){
                            $query->where('customer_department_stat', 1);
                            if($responsible_department != 0) {
                                $query->whereIn('responsible_department_id', $responsible_department);
                            }
                        }, 'customer_departments.departments', 'person_in_charges', 'person_in_charges.person_in_charge_record'])
                    ->withCount(['customer_departments' => function($query) use ($responsible_department){
                            $query->where('customer_department_stat', 1);
                            if($responsible_department != 0) {
                                $query->whereIn('responsible_department_id', $responsible_department);
                            }
                        }])
                    ->having('customer_departments_count', '>', 0);

        if($request->date_from != '0'){
            $customers = $customers->where('audit_date_from', '>=', $request->date_from);
        }

        if($request->date_to != '0'){
            $customers = $customers->where('audit_date_to', '<=', $request->date_to);
        }

        if($request->audit_category != '0' && $request->has('audit_category')) {
            $customers = $customers->where('audit_category', $request->audit_category);
        }

        if($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
            $customers = $customers->where('purpose_of_audit', $request->purpose_of_audit);
        }

        if($request->classification != '0' && $request->has('classification')) {
            $customers = $customers->where('classification', $request->classification);
        }

        if($request->audit_stat != '0' && $request->has('audit_stat')) {
            $customers = $customers->where('audit_stat', $request->audit_stat);
        }

        if($request->customer_name != '0' && $request->has('customer_name')) {
            $customers = $customers->whereIn('customer_name', $request->customer_name);
        }

        $customers = $customers->get();

        // return $customers;
        
        return Excel::download(new AuditResultExport(2, '', $customers, '', ''), 'CUS' . date('Y-m-d H:i:s') . '.xlsx');
    }

    public function view_report_customer_audit_by_stat(Request $request){
        session_start();
        $responsible_department = $request->responsible_department;

        $customers = Customer::on('mysql')
                    ->with(
                        ['customer_departments' => function($query) use ($responsible_department){
                            $query->where('customer_department_stat', 1);
                            if($responsible_department != 0) {
                                $query->whereIn('responsible_department_id', $responsible_department);
                            }
                        }, 'customer_departments.departments', 'person_in_charges', 'person_in_charges.person_in_charge_record'])
                    ->withCount(['customer_departments' => function($query) use ($responsible_department){
                            $query->where('customer_department_stat', 1);
                            if($responsible_department != 0) {
                                $query->whereIn('responsible_department_id', $responsible_department);
                            }
                        }])
                    ->having('customer_departments_count', '>', 0);

        if($request->date_from != '0'){
            $customers = $customers->where('audit_date_from', '>=', $request->date_from);
        }

        if($request->date_to != '0'){
            $customers = $customers->where('audit_date_to', '<=', $request->date_to);
        }

        if($request->audit_category != '0' && $request->has('audit_category')) {
            $customers = $customers->where('audit_category', $request->audit_category);
        }

        if($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
            $customers = $customers->where('purpose_of_audit', $request->purpose_of_audit);
        }

        if($request->classification != '0' && $request->has('classification')) {
            $customers = $customers->where('classification', $request->classification);
        }

        if($request->audit_stat != '0' && $request->has('audit_stat')) {
            $customers = $customers->where('audit_stat', $request->audit_stat);
        }

        if($request->customer_name != '0' && $request->has('customer_name')) {
            $customers = $customers->whereIn('customer_name', $request->customer_name);
        }

        if($request->item_no != '0' && $request->has('item_no')) {
            $customers = $customers->where('item_no', $request->item_no);
        }

        // if($request->evaluation_item != null && $request->has('evaluation_item')) {
        //     $customers = $customers->where('evaluation_item', $request->evaluation_item);
        // }

        $customers = $customers->get();
        // $customers->audit_date_from = Carbon::parse($customer->audit_date_from)->toFormattedDateString();

        return DataTables::of($customers)
            ->addColumn('label1', function($customer){
                $result = "";

                if($customer->sending_stat == 1){
                    $result .= '<span class="tag tag-warning">Pending</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function($customer){
                $result = "";

                if($customer->audit_stat == 1){
                    $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
                }
                else if($customer->audit_stat == 2){
                    $result .= '<span class="tag tag-success">For Closed-Out</span>';
                }
                else{
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function($customer){
                return Carbon::parse($customer->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($customer->audit_date_to)->toFormattedDateString();
            })
            ->addColumn('illustration_image', function($customer){
                $path = "";

                if($customer->img_illustration != '' || $customer->img_illustration != null){
                    $path = asset('public/storage/audit_result_images/customer/' . $customer->img_illustration);
                }
                else{
                    $path = asset('public/storage/image-icon.png');
                }
                return '<center><img src="' . $path . '" style="max-width: 100px; max-height: 200px; min-width: 50px; min-height: 50px;" class="commonAuditImg"></center>';
            })
            ->addColumn('closed_out_audit_image', function($customer){
                $path = "";

                if($customer->img_corrective_action != '' || $customer->img_corrective_action != null){
                    $path = asset('public/storage/audit_result_images/customer/' . $customer->img_corrective_action);
                }
                else{
                    $path = asset('public/storage/image-icon.png');
                }
                return '<center><img src="' . $path . '" style="max-width: 100px; max-height: 200px; min-width: 50px; min-height: 50px;" class="commonAuditImg"></center>';
            })
            ->rawColumns(['label1', 'label2', 'formatted_audit_date', 'closed_out_audit_image', 'illustration_image'])
            ->make(true);
    }

    public function view_customer_audit_by_stat(Request $request){
        session_start();
        $user_access = $request->user_access;

        $is_approver = false;

        $approvers = Approver::where('approver', $_SESSION["rapidx_user_id"])
                    ->where('approver_stat', '1')
                    ->get();

        if(count($approvers) > 0) {
            $is_approver = true;
        }

        $responsible_department = $request->responsible_department;

        $customers = Customer::on('mysql')
                    ->with(
                        ['customer_departments' => function($query) use ($responsible_department){
                            $query->where('customer_department_stat', 1);
                            if($responsible_department != 0) {
                                $query->whereIn('responsible_department_id', $responsible_department);
                            }
                        }, 'customer_departments.departments'])
                    ->withCount(['customer_departments' => function($query) use ($responsible_department){
                            $query->where('customer_department_stat', 1);
                            if($responsible_department != 0) {
                                $query->whereIn('responsible_department_id', $responsible_department);
                            }
                        }])
                    ->having('customer_departments_count', '>', 0);
                    // ->where('customer_audit_stat', 1);
        
        if($request->audit_id != '0'){
            $customers = $customers->where('customer_audit_id', $request->audit_id);
        }

        if($request->date_from != '0'){
            $customers = $customers->where('audit_date_from', '>=', $request->date_from);
        }

        if($request->date_to != '0'){
            $customers = $customers->where('audit_date_to', '<=', $request->date_to);
        }

        if($request->audit_category != '0' && $request->has('audit_category')) {
            $customers = $customers->where('audit_category', $request->audit_category);
        }

        if($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
            $customers = $customers->where('purpose_of_audit', $request->purpose_of_audit);
        }

        if($request->classification != '0' && $request->has('classification')) {
            $customers = $customers->where('classification', $request->classification);
        }

        if($request->audit_stat != '0' && $request->has('audit_stat')) {
            $customers = $customers->where('audit_stat', $request->audit_stat);
        }

        if($request->customer_name != '0' && $request->has('customer_name')) {
            $customers = $customers->whereIn('customer_name', $request->customer_name);
        }

        if($request->item_no != '0' && $request->has('item_no')) {
            $customers = $customers->where('item_no', $request->item_no);
        }

        // if($request->evaluation_item != null && $request->has('evaluation_item')) {
        //     $tuvs = $tuvs->where('evaluation_item_id', $request->evaluation_item);
        // }

        if($request->evaluation_item != null && $request->has('evaluation_item')) {
            if($request->evaluation_item == 'Blank'){
                $customers = $customers->where('evaluation_item_id', null);
            }
            else{
                $customers = $customers->where('evaluation_item_id', $request->evaluation_item);
            }
        }

        if($request->approval_stat != '0' && $request->has('approval_stat')) {
            if($request->approval_stat == 1){
                $customers = $customers->where(function($query){
                    $query->where('audit_stat', 1);
                    $query->where('qad_approval', 0);
                });
            }
            else if($request->approval_stat == 2){
                $customers = $customers->where(function($query){
                    $query->where('audit_stat', 2);
                    $query->where('o_s_approval', 0);
                });
            }
        }

        // if($request->evaluation_item != null && $request->has('evaluation_item')) {
        //     $customers = $customers->where('evaluation_item', $request->evaluation_item);
        // }
        
        $customers = $customers->where('customer_audit_stat', $request->customer_audit_stat);

        $customers = $customers->get();

        return DataTables::of($customers)
            ->addColumn('label1', function($customer){
                $result = "";

                if($customer->sending_stat == 1){
                    $result .= '<span class="tag tag-warning">Pending</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function($customer){
                $result = "";

                if($customer->audit_stat == 1){
                    $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
                }
                else if($customer->audit_stat == 2){
                    $result .= '<span class="tag tag-success">For Closed-Out</span>';
                }
                else{
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            // ->addColumn('formatted_audit_date', function($customer){
            //     return Carbon::parse($customer->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($customer->audit_date_to)->toFormattedDateString();
            // })
            ->addColumn('formatted_audit_date', function($customer){
                $result = '';
                if($customer->audit_date_from != $customer->audit_date_to){
                    $result = Carbon::parse($customer->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($customer->audit_date_to)->toFormattedDateString();
                }
                else{
                    $result = Carbon::parse($customer->audit_date_from)->toFormattedDateString();
                }
                return $result;
            })
            ->addColumn('action1', function($customer) use($is_approver, $user_access){
                $result = '<center><div class="btn-group">
                          <button type="button" class="btn btn-primary dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-gear"></i>
                          </button>
                          <div class="dropdown-menu dropdown-menu-right">
                            <button class="dropdown-item aViewCusAudit" customer-id="' . $customer->customer_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalViewCusAudit" data-keyboard="false">View</button>';

                // if($customer->sending_stat != 2){
                    $result .= '<button class="dropdown-item aEditCusAudit" customer-id="' . $customer->customer_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditCusAudit" data-keyboard="false">Edit</button>';
                // }            

                // if($customer->audit_stat == 1 || $customer->audit_stat == 2){
                //     $result .= '<button class="dropdown-item aCloseCusAudit" type="button" customer-id="' . $customer->customer_audit_id . '" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalCloseCusAudit" data-keyboard="false">Close</button>';
                // }
                // else if($customer->audit_stat == 3 && $customer->sending_stat == 1){
                //     $result .= '<button class="dropdown-item aOpenCusAudit" type="button" style="padding: 1px 1px; text-align: center;" customer-id="' . $customer->customer_audit_id . '" data-toggle="modal" data-target="#modalOpenCusAudit" data-keyboard="false">Open</button>';
                // }

                if($customer->audit_stat != 1){
                    $result .= '<button class="dropdown-item aChangeCusAuditStat" type="button" customer-id="' . $customer->customer_audit_id . '" audit-stat="1" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalChangeAuditStatCusAudit" data-keyboard="false">For Improvement Plan</button>';
                }

                if($customer->audit_stat != 2){
                    $result .= '<button class="dropdown-item aChangeCusAuditStat" type="button" customer-id="' . $customer->customer_audit_id . '" audit-stat="2" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalChangeAuditStatCusAudit" data-keyboard="false">For Close-Out Audit</button>';
                }

                if($customer->audit_stat != 3){
                    $result .= '<button class="dropdown-item aChangeCusAuditStat" type="button" customer-id="' . $customer->customer_audit_id . '" audit-stat="3" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalChangeAuditStatCusAudit" data-keyboard="false">Close</button>';
                }


                if($customer->sending_stat == 1){
                    $result .= '<button class="dropdown-item aDoneCusAudit" type="button" customer-id="' . $customer->customer_audit_id . '" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalDoneCusAudit" data-keyboard="false">Done</button>';
                }

                if($customer->sending_stat == 2) {
                    $result .= '<button class="dropdown-item aPendCusAudit" type="button" customer-id="' . $customer->customer_audit_id . '" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalPendCusAudit" data-keyboard="false">Pend</button>';
                }

                if($customer->customer_audit_stat == 1) {
                    $result .= '<button class="dropdown-item aChangeCusCustomerAuditStat" type="button" customer-id="' . $customer->customer_audit_id . '" customer-audit-stat="2" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalChangeCusCustomerAuditStat" data-keyboard="false">Archive</button>';
                }
                else {
                    $result .= '<button class="dropdown-item aChangeCusCustomerAuditStat" type="button" customer-id="' . $customer->customer_audit_id . '" customer-audit-stat="1" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalChangeCusCustomerAuditStat" data-keyboard="false">Restore</button>';
                }
                            
                $result .= '</div>
                        </div><center>';

                // return $result;

                $test = '';
                $test = '<button class="btn-actions btn btn-sm btn-info aViewCusAudit" customer-id="' . $customer->customer_audit_id . '" type="button" data-toggle="modal" data-target="#modalViewCusAudit" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="View" title="View"><i class="icon-file-text-o"></i></button> ';


                if($customer->customer_audit_stat == 1) {
                    $test .= '<button class="btn-actions btn btn-sm btn-success aEditCusAudit" customer-id="' . $customer->customer_audit_id . '" type="button" data-toggle="modal" data-target="#modalEditCusAudit" data-keyboard="false" title="Edit"><i class="icon-edit2"></i></button> ';

                    $test .= '<button class="btn-actions btn btn-sm btn-danger aChangeCusCustomerAuditStat" type="button" customer-id="' . $customer->customer_audit_id . '" customer-audit-stat="2" data-toggle="modal" data-target="#modalChangeCusCustomerAuditStat" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="Archive" title="Archive"><i class="icon-trash2"></i></button> ';
                }
                else {
                    $test .= '<button class="btn-actions btn btn-sm btn-warning aChangeCusCustomerAuditStat" type="button" customer-id="' . $customer->customer_audit_id . '" customer-audit-stat="1" data-toggle="modal" data-target="#modalChangeCusCustomerAuditStat" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="Restore" title="Restore"><i class="icon-history2"></i></button> ';               
                }

                if($is_approver && $user_access == 4){
                    if($customer->qad_approval == '0' && $customer->audit_stat == 1){

                        $test .= '<button class="btn-actions btn btn-sm btn-primary aQADApprovalCusAudit" type="button" customer-id="' . $customer->customer_audit_id . '" qad-approval="1" data-toggle="modal" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="Approve" title="Approve"><i class="icon-like"></i></button> '; 
                    }
                }
                return $test;
            })
            ->addColumn('check_box_send', function($customer){
                $result = "";

                if($customer->qad_approval == '0'){
                    $result = "disabled";
                }

                return '<center><input type="checkbox" class="chkSendCusAudit" customer-id="' . $customer->customer_audit_id . '" ' . $result . '></center>';
            })
            ->addColumn('approval_stat', function($customer){
                $result = "";

                if($customer->audit_stat == 1 && $customer->qad_approval == 1){
                    $result .= '<span class="tag tag-success">Approved By QAD</span>';
                }
                else if($customer->audit_stat == 1 && $customer->qad_approval == 0){
                    $result .= '<span class="tag tag-warning">For QAD Approval</span>';
                }
                else if($customer->audit_stat == 2 && $customer->o_s_approval == 1){
                    $result .= '<span class="tag tag-success">Approved By Section Head</span>';
                }
                else if($customer->audit_stat == 2 && $customer->o_s_approval == 0){
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

    public function view_customer_audit_by_stat_v2(Request $request){
        session_start();
        $user_access = $request->user_access;

        $is_approver = false;

        $approvers = Approver::where('approver', $_SESSION["rapidx_user_id"])
                    ->where('approver_stat', '1')
                    ->get();

        if(count($approvers) > 0) {
            $is_approver = true;
        }

        $responsible_department = $request->responsible_department;

        $customers = Customer::on('mysql')
        ->with([
            'customer_departments' => function($query) use ($responsible_department){
                $query->where('customer_department_stat', 1);
                // if($responsible_department != 0) {
                //     $query->whereIn('responsible_department_id', $responsible_department);
                // }
            }, 
            'customer_departments.departments',
            'qad_approver',
            'user_last_updated_by'
        ])
        ->when($request->isArchived == 1, function ($query) {
            $query->where('customer_audit_stat', 5);
        }, function ($query) use ($request) {
            $query->where('customer_audit_stat', '<>', 5);
            $query->whereYear('audit_date_from', $request->audit_year);
        });

        if(!is_null($request->customer_name)){
            $customers->where('customer_name', $request->customer_name);
        }
        $customers->get();
        
        return DataTables::of($customers)
            
            ->addColumn('raw_audit_status', function($customer){
                $result = "";

                if($customer->audit_stat == 1){
                    $result .= '<span class="tag tag-warning">Open</span>';
                }
                else if($customer->audit_stat == 2){
                    $result .= '<span class="tag tag-warning">For QAD Approval</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Closed</span>';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function($customer){
                $result = '';
                if($customer->audit_date_from != $customer->audit_date_to){
                    $result = Carbon::parse($customer->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($customer->audit_date_to)->toFormattedDateString();
                }
                else{
                    $result = Carbon::parse($customer->audit_date_from)->toFormattedDateString();
                }
                return $result;
            })
            ->addColumn('action1', function($customer) use ($request){
                $result = '';
                $result = '<button class="btn-actions btn btn-sm btn-info aViewCusAudit" customer-id="' . $customer->customer_audit_id . '" type="button" data-toggle="modal" data-target="#modalViewCusAudit" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="View" title="View"><i class="icon-file-text-o"></i></button> ';


                if($customer->customer_audit_stat == 1 || $customer->customer_audit_stat == 4) {
                    $result .= '<button class="btn-actions btn btn-sm btn-warning aEditCusAudit" customer-id="' . $customer->customer_audit_id . '" type="button" data-toggle="modal" data-target="#modalCustomerAudit" data-keyboard="false" title="Edit"><i class="icon-edit2"></i></button> ';
                    $result .= '<button class="btn-actions btn btn-sm btn-danger aChangeCusCustomerAuditStat" type="button" customer-id="' . $customer->customer_audit_id . '" customer-audit-stat="5" data-toggle="modal" data-target="#modalChangeCusCustomerAuditStat" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="Archive" title="Archive"><i class="icon-trash2"></i></button> ';
                }

                if($customer->audit_stat == 1 && $request->isArchived != 1){
                    $result .= "<br><button class='btn btn-success btn-sm btnProceedApproval' type='button' data-aType='customer' data-id='{$customer->customer_audit_id}' title='Proceed For Approval'><i class='icon-paper-plane'></i></button>";
                }
                if($request->isArchived == 1){
                    $result .= '<br><button class="btn-actions btn btn-sm btn-success aChangeCusAuditStat" type="button" customer-id="' . $customer->customer_audit_id . '" customer-audit-stat="1" data-toggle="modal" data-target="#modalChangeCusAuditStat" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="Restore" title="Restore"><i class="icon-history2"></i></button> ';
                }
               
                return $result;
            })
            ->addColumn('check_box_send', function($customer){
                $result = "";

                if($customer->qad_approval == '0'){
                    $result = "disabled";
                }

                return '<center><input type="checkbox" class="chkSendCusAudit" customer-id="' . $customer->customer_audit_id . '" ' . $result . '></center>';
            })
            ->addColumn('approval_stat', function($customer){
                $result = "---";
                if($customer->customer_audit_stat == 2){
                    $result = '<span class="tag tag-warning">Pending</span>';
                }
                else if($customer->customer_audit_stat == 3){
                    $result = '<span class="tag tag-success">approved</span>';
                    $result .= "<br><em>Approved by: {$customer->qad_approver->name}</em>";
                }
                else if($customer->customer_audit_stat == 4){
                    $result = '<span class="tag tag-danger">disapproved</span>';
                    $result .= "<br><em>Disapproved by: {$customer->user_last_updated_by->name}</em>";
                    $result .= "<br><em><strong>Remarks</strong>: {$customer->qad_dis_remarks}</em>";
                }
              
                return $result;
            })
             ->addColumn('raw_responsible_department', function($customer){
                $result = "";

                foreach($customer->customer_departments as $customer_department) {
                    $result .= $customer_department->departments[0]->department_name . ", ";
                }
                return $result;
            })
            ->rawColumns(['action1', 'raw_audit_status', 'formatted_audit_date', 'check_box_send', 'approval_stat', 'raw_responsible_department'])
            ->make(true);
    }

    public function view_customer_audit_for_approval(Request $request){
        session_start();
        $responsible_department = $request->responsible_department;
        $user_access = $request->user_access;

        $is_approver = false;

        $approvers = Approver::where('approver', $_SESSION["rapidx_user_id"])
                    ->where('approver_stat', '1')
                    ->get();

        if(count($approvers) > 0) {
            $is_approver = true;
        }

        if($user_access == '4'){
            $customers = Customer::on('mysql')
                        ->with(
                            ['customer_departments' => function($query) use ($responsible_department){
                                $query->where('customer_department_stat', 1);
                                if($responsible_department != 0) {
                                    $query->whereIn('responsible_department_id', $responsible_department);
                                }
                            }, 'customer_departments.departments', 'person_in_charges', 'person_in_charges.person_in_charge_record'])
                        ->withCount(['customer_departments' => function($query) use ($responsible_department){
                                $query->where('customer_department_stat', 1);
                                if($responsible_department != 0) {
                                    $query->whereIn('responsible_department_id', $responsible_department);
                                }
                            }])
                        ->where('qad_approval', 0)
                        ->having('customer_departments_count', '>', 0);
        }
        else{
            $responsible_department = [];

            foreach ($approvers as $approver) {
                $responsible_department[] = $approver->department_id;
            }

            if($is_approver == false){
                $responsible_department[] = $_SESSION['rapidx_department_id'];
            }

            $customers = Customer::on('mysql')
                        ->with(
                            ['customer_departments' => function($query) use ($responsible_department){
                                $query->where('customer_department_stat', 1);
                                if($responsible_department != 0) {
                                    $query->whereIn('responsible_department_id', $responsible_department);
                                }
                            }, 'customer_departments.departments', 'person_in_charges', 'person_in_charges.person_in_charge_record'])
                        ->withCount(['customer_departments' => function($query) use ($responsible_department){
                                $query->where('customer_department_stat', 1);
                                if($responsible_department != 0) {
                                    $query->whereIn('responsible_department_id', $responsible_department);
                                }
                            }])
                        ->where('o_s_approval', 0)
                        ->where('audit_stat', 2)
                        ->where('customer_audit_stat', 1)
                        ->having('customer_departments_count', '>', 0);
        }


        if($request->date_from != '0'){
            $customers = $customers->where('audit_date_from', '>=', $request->date_from);
        }

        if($request->date_to != '0'){
            $customers = $customers->where('audit_date_to', '<=', $request->date_to);
        }

        if($request->audit_category != '0' && $request->has('audit_category')) {
            $customers = $customers->where('audit_category', $request->audit_category);
        }

        if($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
            $customers = $customers->where('purpose_of_audit', $request->purpose_of_audit);
        }

        if($request->classification != '0' && $request->has('classification')) {
            $customers = $customers->where('classification', $request->classification);
        }

        if($request->audit_stat != '0' && $request->has('audit_stat')) {
            $customers = $customers->where('audit_stat', $request->audit_stat);
        }

        $customers = $customers->get();

        return DataTables::of($customers)
            ->addColumn('label1', function($customer){
                $result = "";

                if($customer->sending_stat == 1){
                    $result .= '<span class="tag tag-warning">Pending</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function($customer){
                $result = "";

                if($customer->audit_stat == 1){
                    $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
                }
                else if($customer->audit_stat == 2){
                    $result .= '<span class="tag tag-success">For Closed-Out</span>';
                }
                else{
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function($customer){
                return Carbon::parse($customer->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($customer->audit_date_to)->toFormattedDateString();
            })
            ->addColumn('action1', function($customer) use($user_access, $is_approver){
                $result = '<div class="btn-group">
                          <button type="button" class="btn btn-primary dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-gear"></i>
                          </button>
                          <div class="dropdown-menu dropdown-menu-right">
                            <button class="dropdown-item aViewCusAudit" customer-id="' . $customer->customer_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalViewCusAudit" data-keyboard="false">View</button>';

                // if($customer->sending_stat != 2){
                //     $result .= '<button class="dropdown-item aEditCus" customer-id="' . $customer->customer_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditCusAudit" data-keyboard="false">Edit</button>';
                // }            

                // if($customer->audit_stat == 1 || $customer->audit_stat == 2){
                //     $result .= '<button class="dropdown-item aCloseCusAudit" type="button" customer-id="' . $customer->customer_audit_id . '" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalCloseCusAudit" data-keyboard="false">Close</button>';
                // }
                // else if($customer->audit_stat == 3 && $customer->sending_stat == 1){
                //     $result .= '<button class="dropdown-item aOpenCusAudit" type="button" style="padding: 1px 1px; text-align: center;" customer-id="' . $customer->customer_audit_id . '" data-toggle="modal" data-target="#modalOpenCusAudit" data-keyboard="false">Open</button>';
                // }

                // if($customer->sending_stat == 1){
                //     $result .= '<button class="dropdown-item aDoneCusAudit" type="button" customer-id="' . $customer->customer_audit_id . '" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalDoneCusAudit" data-keyboard="false">Done</button>';
                // }

                // if($customer->sending_stat == 2) {
                //     $result .= '<button class="dropdown-item aPendCusAudit" type="button" customer-id="' . $customer->customer_audit_id . '" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalPendCusAudit" data-keyboard="false">Pend</button>';
                // }

                if($user_access == 4){
                    if($customer->sending_stat != 2){
                        $result .= '<button class="dropdown-item aEditCus" customer-id="' . $customer->customer_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditCusAudit" data-keyboard="false">Edit</button>';
                    }
                }
                else{
                    if($customer->sending_stat != 2){
                        if(($customer->audit_stat == 2 && $customer->qad_approval == 1) && $is_approver == true){
                            if($customer->audit_stat == 2 && $customer->o_s_approval == 0 && $is_approver == true){
                                $result .= '<button class="dropdown-item aEditCus" customer-id="' . $customer->customer_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditCusAudit" data-keyboard="false">Edit</button>';
                            }
                            else if($customer->audit_stat == 2 && $customer->o_s_approval == 0 && $is_approver == false){
                                $result .= '<button class="dropdown-item aEditCus" customer-id="' . $customer->customer_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditCusAudit" data-keyboard="false">Edit</button>';
                            }
                        }
                        else if($customer->audit_stat == 1 && $customer->qad_approval == 1){
                            $result .= '<button class="dropdown-item aEditCus" customer-id="' . $customer->customer_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditCusAudit" data-keyboard="false">Edit</button>';
                        }
                    }
                }
                            
                $result .= '</div>
                        </div>';

                return $result;
            })
            ->addColumn('check_box_send', function($customer) use ($is_approver, $user_access){
                $result = "disabled";

                if($user_access == 4){
                    if($customer->qad_approval == '0'){
                        $result = "";
                    }
                }
                else{
                    if($customer->o_s_approval == '0'){
                        $result = "";
                    }
                }

                if($is_approver == false){
                    $result = "disabled";
                }

                return '<center><input type="checkbox" class="chkSendCusAudit" customer-id="' . $customer->customer_audit_id . '" ' . $result . '></center>';
            })
            ->addColumn('approval_stat', function($customer){
                $result = "";

                if($customer->audit_stat == 1 && $customer->qad_approval == 1){
                    $result .= '<span class="tag tag-success">Approved By QAD</span>';
                }
                else if($customer->audit_stat == 1 && $customer->qad_approval == 0){
                    $result .= '<span class="tag tag-warning">For QAD Approval</span>';
                }
                else if($customer->audit_stat == 2 && $customer->o_s_approval == 1){
                    $result .= '<span class="tag tag-success">Approved By Section Head</span>';
                }
                else if($customer->audit_stat == 2 && $customer->o_s_approval == 0){
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
    public function view_customer_audit_for_approval_v2(Request $request){
        session_start();

        $customer_audit = Customer::with([
            'customer_departments' => function($query){
                $query->where('customer_department_stat', 1);
            },
            'customer_departments.departments'
        ])
        ->whereYear('created_at', '>=', '2026')
        ->where('audit_stat', 2)
        ->where('customer_audit_stat', 2)
        ->get();

        return DataTables::of($customer_audit)
            ->addColumn('action', function($customer){
                $result = "";
                $result .= "<button class='btn btn-sm btn-info aViewCusAudit' style='margin-right: 2px;' customer-id='{$customer->customer_audit_id}' type='button' data-toggle='modal' data-target='#modalViewCusAudit' data-keyboard='false' title='View Audit Result'><i class='icon-eye'></i></button>";
                $result .= "<button class='btn btn-sm btn-success btnApprove' style='margin-right: 2px;' data-id='{$customer->customer_audit_id}' data-classification='customer' title='Approve Audit Result'><i class='icon-circle-check'></i></button>";
                $result .= "<button class='btn btn-sm btn-danger btnDisapprove' data-id='{$customer->customer_audit_id}' data-classification='customer' title='Disapprove Audit Result'><i class='icon-times'></i></button>";
                return $result;
            })
             ->addColumn('formatted_audit_date', function($customer){
                return Carbon::parse($customer->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($customer->audit_date_to)->toFormattedDateString();
            })
            ->rawColumns(['action', 'formatted_audit_date'])
            ->make(true);
    }

    public function view_batch_customer_audit_by_stat(Request $request){
        session_start();
        $customer_audit_id = [];
        if(isset($request->customer_audit_id) || $request->customer_audit_id != null){
            $customer_audit_id = $request->customer_audit_id;
        }
        $customers = Customer::with([
                                    'customer_departments',
                                    'customer_departments.departments'
                                ])
                                ->whereIn('customer_audit_id', $customer_audit_id)
                                ->get();

        return DataTables::of($customers)
            ->addColumn('label1', function($customer){
                $result = "";

                if($customer->sending_stat == 1){
                    $result .= '<span class="tag tag-warning">Pending</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function($customer){
                $result = "";

                if($customer->audit_stat == 1){
                    $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
                }
                else if($customer->audit_stat == 2){
                    $result .= '<span class="tag tag-success">For Closed-Out</span>';
                }
                else{
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function($customer){
                return Carbon::parse($customer->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($customer->audit_date_to)->toFormattedDateString();
            })
            ->addColumn('action1', function($customer){
                $result = '<div class="btn-group">
                          <button type="button" class="btn btn-primary dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-gear"></i>
                          </button>
                          <div class="dropdown-menu dropdown-menu-right">
                            <button class="dropdown-item aViewCusAudit" customer-id="' . $customer->customer_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalViewCusAudit" data-keyboard="false">View</button>';

                $result .= '<button class="dropdown-item aRemoveCusInBatch" customer-id="' . $customer->customer_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalRemoveCusInBatch" data-keyboard="false">Remove</button>';
                            
                $result .= '</div>
                        </div>';

                return $result;
            })
            ->rawColumns(['action1', 'label1', 'label2', 'formatted_audit_date'])
            ->make(true);
    }

    public function view_single_customer_audit(Request $request){
        session_start();
        $responsible_department = $request->responsible_department;

        $customers = Customer::on('mysql')
                    ->with(
                        ['customer_departments' => function($query) use ($responsible_department){
                            $query->where('customer_department_stat', 1);
                            if($responsible_department != 0) {
                                $query->where('responsible_department_id', $responsible_department);
                            }
                        }, 'customer_departments.departments'])
                    ->withCount(['customer_departments' => function($query) use ($responsible_department){
                            $query->where('customer_department_stat', 1);
                            if($responsible_department != 0) {
                                $query->where('responsible_department_id', $responsible_department);
                            }
                        }])
                    ->where('in_batch', 0)
                    ->where('customer_audit_stat', 1)
                    ->where('audit_stat', 1)
                    ->where('sending_stat', 1)
                    ->having('customer_departments_count', '>', 0);

        if($request->date_from != '0' && $request->date_to != '0') {
            $customers = $customers->whereBetween('audit_date', [$request->date_from, $request->date_to]);
        }

        if($request->audit_category != '0' && $request->has('audit_category')) {
            $customers = $customers->where('audit_category', $request->audit_category);
        }

        if($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
            $customers = $customers->where('purpose_of_audit', $request->purpose_of_audit);
        }

        if($request->classification != '0' && $request->has('classification')) {
            $customers = $customers->where('classification', $request->classification);
        }

        if($request->audit_stat != '0' && $request->has('audit_stat')) {
            $customers = $customers->where('audit_stat', $request->audit_stat);
        }

        if($request->customer_name != '0' && $request->has('customer_name')) {
            $customers = $customers->whereInIn('customer_name', $request->customer_name);
        }

        $customers = $customers->get();

        return DataTables::of($customers)
            ->addColumn('label1', function($customer){
                $result = "";

                if($customer->sending_stat == 1){
                    $result .= '<span class="tag tag-warning">Pending</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function($customer){
                $result = "";

                if($customer->audit_stat == 1){
                    $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
                }
                else if($customer->audit_stat == 2){
                    $result .= '<span class="tag tag-success">For Closed-Out</span>';
                }
                else{
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function($customer){
                return Carbon::parse($customer->audit_date)->toFormattedDateString();
            })
            ->addColumn('action1', function($customer){
                $result = '<div class="btn-group">
                          <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-cog"></i>
                          </button>
                          <div class="dropdown-menu dropdown-menu-right">
                            <button class="dropdown-item aViewCusAudit" customer-id="' . $customer->customer_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalViewCusAudit" data-keyboard="false">View</button>';
                
                if(($customer->sending_stat == 1 || $customer->audit_stat == 1 || $customer->audit_stat == 2) && $customer->in_batch == 0) {
                    $result .= '<button class="dropdown-item aAddToBatchCusAudit" type="button" customer-id="' . $customer->customer_audit_id . '" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalAddToBatchCusAudit" data-keyboard="false">Add to Batch</button>';
                }

                            
                $result .= '</div>
                        </div>';

                return $result;
            })
            ->rawColumns(['action1', 'label1', 'label2', 'formatted_audit_date'])
            ->make(true);
    }

    public function add_customer_audit(Request $request){
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

        $responsible_group = "";

        $data = $request->all();

        $validator = Validator::make($data, [
            'customer_name' => ['required', 'string', 'max:255'],
            'audit_date_from' => ['required', 'date', 'max:255'],
            'audit_date_to' => ['required', 'date', 'max:255'],
            'auditors' => ['required', 'string', 'max:255'],
            // 'deadline_of_submission' => ['required', 'string', 'max:255'],
            // 'deadline_of_submission_days' => ['required', 'max:255'],
            // 'actual_date_of_submission' => ['required', 'string', 'max:255'],
            'process' => ['required', 'string', 'max:255'],
            // 'evaluation_item' => ['required', 'string'],
            'classification' => ['required', 'string'],
            'statement_of_findings' => ['required', 'string'],
            // 'illustration' => ['required', 'string'],
            // 'img_illustration' => ['required'],
            'responsible_group' => ['required'],
            'responsible_department' => ['required'],
            'audit_stat' => ['required'],
            'sending_stat' => ['required'],
            // 'corrective_action' => ['required', 'string', 'max:255'],
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

                for($index = 0; $index < count($request->responsible_group); $index++){
                    $responsible_group .= $request->responsible_group[$index];

                    if($index < (count($request->responsible_group) - 1)){
                        $responsible_group .= ",";
                    }
                }

                $customer_audit_id = Customer::on('mysql')->insertGetId([
                    'customer_name' => $request->customer_name,
                    'audit_date_from' => $request->audit_date_from,
                    'audit_date_to' => $request->audit_date_to,
                    'auditors' => $request->auditors,
                    'item_no' => $request->item_no,
                    'deadline_of_submission' => $request->deadline_of_submission,
                    'deadline_of_submission_days' => $request->deadline_of_submission_days,
                    'actual_date_of_submission' => $request->actual_date_of_submission,
                    'sending_stat' => $request->sending_stat,
                    'email_stat' => $email_stat,
                    'in_batch' => 0,
                    'process' => $request->process,
                    // 'evaluation_item' => $request->evaluation_item,
                    'evaluation_item_id' => $request->evaluation_item_id,
                    'classification' => $request->classification,
                    'statement_of_findings' => $request->statement_of_findings,
                    'illustration' => $request->illustration,
                    'img_illustration' => $img_illustration,
                    'pdf_illustration' => $pdf_illustration,
                    'root_cause' => $request->root_cause,
                    'improvement_plan' => $request->improvement_plan,
                    'commitment_date' => $request->commitment_date,
                    'responsible_group' => $responsible_group,
                    // 'responsible_department' => $request->responsible_department,
                    // old code
                    // 'person_in_charge' => $txt_person_in_charge,
                    'person_in_charge' => $request->txt_person_in_charge,
                    'corrective_action' => $request->corrective_action,
                    'img_corrective_action' => $img_corr_act,
                    'pdf_corrective_action' => $pdf_corr_act,
                    'qad_approval' => $request_approval,
                    'o_s_approval' => 0,
                    'qad_approved_by' => 0,
                    'o_s_approved_by' => 0,
                    'audit_stat' => $request->audit_stat,
                    'customer_audit_stat' => 1,
                    'created_by' => $_SESSION["rapidx_user_id"],
                    'last_updated_by' => $_SESSION["rapidx_user_id"],
                    'update_version' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                for($index = 0; $index < count($request->responsible_department); $index++) {
                    CustomerDepartments::insert([
                        'customer_audit_id' => $customer_audit_id,
                        'responsible_department_id' => $request->responsible_department[$index],
                        'customer_department_stat' => 1,
                        'created_by' => $_SESSION['rapidx_user_id'],
                        'last_updated_by' => $_SESSION['rapidx_user_id'],
                        'update_version' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }

                if(isset($request->person_in_charge)){
                    for($index = 0; $index < count($request->person_in_charge); $index++) {
                        CustomerPersonInCharges::insert([
                            'customer_audit_id' => $customer_audit_id,
                            'person_in_charge_id' => $request->person_in_charge[$index],
                            'created_by' => $_SESSION['rapidx_user_id'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }

                DB::commit();

                // sending mail codes here...
                if(isset($request->send_email)){
                     dispatch(new SendNewCustomerMailJob($customer_audit_id));
                }
                
                if(isset($request->img_corrective_action)){
                    if(!Storage::putFileAs('public/audit_result_images/customer', $request->file('img_corrective_action'), $img_corr_act)){
                        return response()->json(['upload_result' => 0]);
                    }
                }

                if(isset($request->img_illustration)){
                    if(!Storage::putFileAs('public/audit_result_images/customer', $request->file('img_illustration'), $img_illustration)){
                        return response()->json(['upload_result' => 0]);
                    }
                }

                if(isset($request->pdf_corrective_action)){
                    if(!Storage::putFileAs('public/audit_result_pdf/customer', $request->file('pdf_corrective_action'), $pdf_corr_act)){
                        return response()->json(['upload_result' => 0]);
                    }
                }

                if(isset($request->pdf_illustration)){
                    if(!Storage::putFileAs('public/audit_result_pdf/customer', $request->file('pdf_illustration'), $pdf_illustration)){
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

    public function add_customer_audit_v2(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        $email_stat = 0;
        $request_approval = 1;
        $responsible_group = "";

        $data = $request->all();
        DB::beginTransaction();
        try{
            if(isset($request->txtCustomerAuditId)){ // Edit
                // return $request->all();
                $extension = "";
                $pdf_extension = "";
                $image_name = "";
                $pdf_name = "";

                if(isset($request->img_corrective_action)) {
                    $extension = $request->file('img_corrective_action')->getClientOriginalExtension();
                    $image_name = date('YmdHis') . Str::random(5) . '.' . $extension;
                    if(!Storage::putFileAs('public/audit_result_images/customer', $request->file('img_corrective_action'), $image_name)){
                        return response()->json(['upload_result' => 0]);
                    }
                }

                if(isset($request->pdf_corrective_action)) {
                    $pdf_extension = $request->file('pdf_corrective_action')->getClientOriginalExtension();
                    $pdf_name = date('YmdHis') . Str::random(5) . '.' . $pdf_extension;
                    if(!Storage::putFileAs('public/audit_result_pdf/customer', $request->file('pdf_corrective_action'), $pdf_name)){
                        return response()->json(['upload_result' => 0]);
                    }
                }

                Customer::on('mysql')->where('customer_audit_id', $request->txtCustomerAuditId)
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
                        $attachment_id = CustomerAuditAttachment::on('mysql')->insertGetId([
                            'customer_audit_id' => $request->txtCustomerAuditId,
                            'filename' => $original_filename,
                            'path' => 'audit_result_attachments/customer/',
                            'remarks' => $data['remarks'],
                            // 'uploaded_by' => $_SESSION['rapidx_user_id'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        // Path where file will be saved
                        Storage::putFileAs('public/audit_result_attachments/customerview_customer_audit_by_stat', $data['file'], "$attachment_id.$ext");
                    }
                }
            }
            else{ // Create

                $validator = Validator::make($data, [
                    'customer_name'             => ['required', 'string', 'max:255'],
                    'audit_date_from'           => ['required', 'date', 'max:255'],
                    'audit_date_to'             => ['required', 'date', 'max:255'],
                    'auditors'                  => ['required', 'string', 'max:255'],
                    'process'                   => ['required', 'string', 'max:255'],
                    'classification'            => ['required', 'string'],
                    // 'statement_of_findings'     => ['required', 'string'],
                    'responsible_group'         => ['required'],
                    'responsible_department'    => ['required'],
                    'section'                   => ['required'],
                    'actual_date_of_submission' => ['required'],
                    'control_number'            => ['required'],
                    'deadline_of_submission'    => ['required']
                ]);

                if ($validator->passes()) {
                    for($index = 0; $index < count($request->responsible_group); $index++){
                        $responsible_group .= $request->responsible_group[$index];

                        if($index < (count($request->responsible_group) - 1)){
                            $responsible_group .= ",";
                        }
                    }
                    // return $request->all();
                    $data = [
                        'customer_name'               => $request->customer_name,
                        'ctrl_no'                     => $request->control_number,
                        'audit_date_from'             => $request->audit_date_from,
                        'audit_date_to'               => $request->audit_date_to,
                        'auditors'                    => $request->auditors,
                        'deadline_of_submission'      => $request->deadline_of_submission,
                        'deadline_of_submission_days' => $request->deadline_of_submission_days,
                        'actual_date_of_submission'   => $request->actual_date_of_submission,
                        'in_batch'                    => 0,
                        'process'                     => $request->process,
                        'classification'              => $request->classification,
                        'responsible_group'           => $responsible_group,
                        'o_s_approval'                => 0,
                        'qad_approved_by'             => 0,
                        'o_s_approved_by'             => 0,
                        'customer_audit_stat'         => 1,
                        'audit_stat'                  => 1,
                        'created_by'                  => $_SESSION["rapidx_user_id"],
                        'last_updated_by'             => $_SESSION["rapidx_user_id"],
                        'update_version'              => 1,
                        'created_at'                  => date('Y-m-d H:i:s'),
                        'updated_at'                  => date('Y-m-d H:i:s'),
                        'audit_title'                 => $request->audit_title,
                        'section'                     => $request->section,
                    ];
                    // if ($request->classification === 'CPAR') {
                        $data['number_OFI'] = $request->number_OFI;
                        $data['number_major_nc'] = $request->number_major_nc;
                        $data['number_minor_nc'] = $request->number_minor_nc;
                        $data['number_possitive_feedback'] = $request->number_possitive_feedback;
                    // }
                    
                    $customer_audit_id = Customer::on('mysql')->insertGetId($data);

                    for($index = 0; $index < count($request->responsible_department); $index++) {
                        CustomerDepartments::insert([
                            'customer_audit_id'         => $customer_audit_id,
                            'responsible_department_id' => $request->responsible_department[$index],
                            'customer_department_stat'  => 1,
                            'created_by'                => $_SESSION['rapidx_user_id'],
                            'last_updated_by'           => $_SESSION['rapidx_user_id'],
                            'update_version'            => 1,
                            'created_at'                => date('Y-m-d H:i:s'),
                            'updated_at'                => date('Y-m-d H:i:s'),
                        ]);
                    }

                    if(isset($request->attachments)) {
                        for($index = 0; $index < count($request->attachments); $index++) {
                            $data = $request->attachments[$index];
                            $original_filename = $data['file']->getClientOriginalName();
                            $ext = $data['file']->getClientOriginalExtension();
                            $attachment_id = CustomerAuditAttachment::on('mysql')->insertGetId([
                                'customer_audit_id' => $customer_audit_id,
                                'filename' => $original_filename,
                                'path' => 'audit_result_attachments/customer/',
                                'remarks' => $data['remarks'],
                                // 'uploaded_by' => $_SESSION['rapidx_user_id'],
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                            // Path where file will be saved
                            Storage::putFileAs('public/audit_result_attachments/customer', $data['file'], "$attachment_id.$ext");
                        }
                    }

                    dispatch(new SendAuditResultEmailJob($customer_audit_id, 'customer'));
                }
                else{
                    return response()->json(['result' => '0', 'error' => $validator->messages()]);
                }
            }
            
            DB::commit();

            return response()->json(['result' => 1]);
        }
        catch(\Exeption $e){
            DB::rollback();
            return response()->json(['result' => 0]);
        }
      
    }

    public function send_customer_batch(Request $request){
        session_start();
        try{
            dispatch(new SendCustomerBatchMailJob($request->customer_audit_id));
            return response()->json(['result' => 1]);
        }
        catch(\Exception $e){
            return response()->json(['result' => $e]);
        }

        // $arr_responsible_department_id = [];
        // $arr_responsible_department_name = [];

        // $customers = Customer::with(
        //                 ['customer_departments' => function($query){
        //                     $query->where('customer_department_stat', 1);
        //                 }, 'customer_departments.departments'])
        //             ->withCount(['customer_departments' => function($query){
        //                     $query->where('customer_department_stat', 1);
        //                 }])
        //             ->having('customer_departments_count', '>', 0)
        //             ->whereIn('customer_audit_id', $request->customer_audit_id)
        //             ->get();

        // // return $customers;

        // $final_customer_audits = [];

        // $collect_customers = collect($customers)->flatten(1);

        // // $arr_responsible_department_id = collect($customers)->pluck('customer_audit_id')->flatten(1);


        // // return $arr_responsible_department_id;

        // for($index = 0; $index < count($collect_customers); $index++){
        //     for($index2 = 0; $index2 < count($collect_customers[$index]->customer_departments); $index2++){
        //         $arr_responsible_department_id[] = $collect_customers[$index]->customer_departments[$index2]->departments[0]->department_id;
        //         $arr_responsible_department_name[] = $collect_customers[$index]->customer_departments[$index2]->departments[0]->department_name;
        //     }
        // }

        // // return $arr_responsible_department_name;

        // $email_recipients = EmailRecipient::with([
        //         'department',
        //         'receiver_info',
        //     ])
        //     ->whereIn('department_id', $arr_responsible_department_id)
        //     ->get();

        // $arrAddressTo = [];
        // $arrCC = [];

        // // return $email_recipients;

        // // return $arr_responsible_department_name;
        // for($index = 0; $index < count($arr_responsible_department_id); $index++){
        //     $current_res_dept_id = $arr_responsible_department_id[$index];

        //     $collect_customers = $collect_customers->filter(function ($value, $key) use ($current_res_dept_id) {
        //                                         return collect($value['customer_departments'])->contains('responsible_department_id', $current_res_dept_id);
        //                                     });           

        //     $final_customer_audits = $collect_customers->flatten(1);

        //     $arrAddressTo = collect($email_recipients)->where('address_type', 1)->where('department_id', $current_res_dept_id)->flatten(1)->pluck('receiver_info.email');
        //     $arrCC = collect($email_recipients)->where('address_type', 2)->where('department_id', $current_res_dept_id)->flatten(1)->pluck('receiver_info.email');

        //     // return $arrAddressTo;
        //     $newCustomerBatchMail = new SendCustomerBatchMail($final_customer_audits, $arr_responsible_department_name[$index], 'Test Email - Customer Audit Batch Alert');
        //     Mail::to($arrAddressTo)->cc($arrCC)->send($newCustomerBatchMail);
        // }
    }

    public function approve_customer_batch(Request $request){
        session_start();
        date_default_timezone_set('Asia/Manila');
        
        DB::beginTransaction();

        try{
            Customer::where('customer_audit_id', $request->customer_audit_id)
                ->increment('update_version', 1, 
                    [
                        'qad_approval' => 1,
                        'qad_approved_by' => $_SESSION["rapidx_user_id"],
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

    public function get_customer_audit_by_id(Request $request){
        session_start();
        $customer_audits = Customer::on('mysql')
                    ->with(
                        ['customer_departments' => function($query){
                            $query->where('customer_department_stat', 1);
                        }, 'customer_departments.departments', 'user_created_by', 'user_last_updated_by', 'person_in_charges',
                            'evaluation_item_info',
                            'person_in_charges.person_in_charge_record'])
                ->where('customer_audit_id', $request->customer_audit_id)
                ->get();

        return response()->json(['customer_audits' => $customer_audits]);
    }

    public function get_unique_customer_names(Request $request){
        session_start();
        $customer_audits = Customer::select('customer_name')->distinct()->get();

        return response()->json(['customer_audits' => $customer_audits]);
    }

    public function change_customer_audit_stat(Request $request){
        session_start();
        date_default_timezone_set('Asia/Manila');
        
        DB::beginTransaction();

        try{
            Customer::on('mysql')->where('customer_audit_id', $request->customer_audit_id)
                ->increment('update_version', 1, 
                    [
                        'customer_audit_stat' => $request->audit_stat,
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

    public function close_customer_audit(Request $request){
        session_start();
        date_default_timezone_set('Asia/Manila');
        
        DB::beginTransaction();

        try{
            Customer::on('mysql')->where('customer_audit_id', $request->customer_audit_id)
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

    public function open_customer_audit(Request $request){
        session_start();
        date_default_timezone_set('Asia/Manila');
        
        DB::beginTransaction();

        try{
            Customer::on('mysql')->where('customer_audit_id', $request->customer_audit_id)
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

    public function done_customer_audit(Request $request){
        session_start();
        date_default_timezone_set('Asia/Manila');
        
        DB::beginTransaction();

        try{
            Customer::on('mysql')->where('customer_audit_id', $request->customer_audit_id)
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

    public function pend_customer_audit(Request $request){
        session_start();
        date_default_timezone_set('Asia/Manila');
        
        DB::beginTransaction();

        try{
            Customer::on('mysql')->where('customer_audit_id', $request->customer_audit_id)
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

    public function edit_customer_audit(Request $request){
        session_start();

        $data = $request->all();

        $validator = Validator::make($data, [
            'customer_name' => ['required', 'string', 'max:255'],
            'audit_date_from' => ['required', 'date', 'max:255'],
            'audit_date_to' => ['required', 'date', 'max:255'],
            'auditors' => ['required', 'string', 'max:255'],
            // 'deadline_of_submission' => ['required', 'string', 'max:255'],
            // 'deadline_of_submission_days' => ['required', 'numeric', 'min:0', 'max:5'],
            'process' => ['required', 'string', 'max:255'],
            // 'evaluation_item' => ['required', 'string'],
            'classification' => ['required', 'string'],
            'statement_of_findings' => ['required', 'string'],
            'responsible_group' => ['required'],
            'responsible_department' => ['required'],
            'audit_stat' => ['required'],
            'sending_stat' => ['required'],
        ]);

        if ($validator->passes()) {

            DB::beginTransaction();

            try{
                $img_corr_act = "";
                $ext_corr_act = "";
                if(isset($request->img_corrective_action)){
                    $ext_corr_act = $request->file('img_corrective_action')->getClientOriginalExtension();
                    $img_corr_act = date('YmdHis') . Str::random(5) . '.' . $ext_corr_act;
                }
                else{
                    if($request->current_img_corrective_action != "" && isset($request->current_img_corrective_action)){
                        $img_corr_act = $request->current_img_corrective_action;
                    }
                    else{
                        $img_corr_act = "";
                    }
                }

                $pdf_corr_act = "";
                $ext_pdf_corr_act = "";
                if(isset($request->pdf_corrective_action)){
                    $ext_pdf_corr_act = $request->file('pdf_corrective_action')->getClientOriginalExtension();
                    $pdf_corr_act = date('YmdHis') . Str::random(5) . '.' . $ext_pdf_corr_act;
                }
                else{
                    if($request->current_img_corrective_action != "" && isset($request->current_img_corrective_action)){
                        $pdf_corr_act = $request->current_img_corrective_action;
                    }
                    else{
                        $pdf_corr_act = "";
                    }
                }

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

                if(isset($request->remove_img_illustration)){
                    $img_illustration = "";
                }
                if(isset($request->remove_pdf_illustration)){
                    $pdf_illustration = "";
                }

                if(isset($request->remove_img_corrective_action)){
                    $img_corr_act = "";
                }
                if(isset($request->remove_pdf_corrective_action)){
                    $pdf_corr_act = "";
                }

                $responsible_group = "";

                for($index = 0; $index < count($request->responsible_group); $index++){
                    $responsible_group .= $request->responsible_group[$index];

                    if($index < (count($request->responsible_group) - 1)){
                        $responsible_group .= ",";
                    }
                }

                Customer::on('mysql')->where('customer_audit_id', $request->customer_audit_id)
                    ->increment('update_version', 1,
                    [
                        'customer_name' => $request->customer_name,
                        'audit_date_from' => $request->audit_date_from,
                        'audit_date_to' => $request->audit_date_to,
                        'auditors' => $request->auditors,
                        'item_no' => $request->item_no,
                        'deadline_of_submission' => $request->deadline_of_submission,
                        'deadline_of_submission_days' => $request->deadline_of_submission_days,
                        'actual_date_of_submission' => $request->actual_date_of_submission,
                        'process' => $request->process,
                        // 'evaluation_item' => $request->evaluation_item,
                        'evaluation_item_id' => $request->evaluation_item_id,
                        'classification' => $request->classification,
                        'statement_of_findings' => $request->statement_of_findings,
                        'illustration' => $request->illustration,
                        'img_illustration' => $img_illustration,
                        'pdf_illustration' => $pdf_illustration,
                        'root_cause' => $request->root_cause,
                        'improvement_plan' => $request->improvement_plan,
                        'commitment_date' => $request->commitment_date,
                        'responsible_group' => $responsible_group,
                        'audit_stat' => $request->audit_stat,
                        'sending_stat' => $request->sending_stat,
                        // 'person_in_charge' => $request->txt_person_in_charge,
                        // 'responsible_department' => $request->responsible_department,
                        'person_in_charge' => $request->txt_person_in_charge,
                        'corrective_action' => $request->corrective_action,
                        'img_corrective_action' => $img_corr_act,
                        'pdf_corrective_action' => $pdf_corr_act,
                        'last_updated_by' => $_SESSION["rapidx_user_id"],
                        'update_version' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );

                $customers = Customer::on('mysql')
                    ->with(['customer_departments'])
                    ->get();

                CustomerDepartments::on('mysql')->where('customer_audit_id', $request->customer_audit_id)
                    ->whereNotIn('responsible_department_id', $request->responsible_department)
                    ->increment('update_version', 1, 
                        [
                            'customer_department_stat' => 2,
                            'updated_at' => date('Y-m-d H:i:s'),
                            'last_updated_by' => $_SESSION['rapidx_user_id'],
                        ]
                    );

                CustomerDepartments::on('mysql')->where('customer_audit_id', $request->customer_audit_id)
                    ->whereIn('responsible_department_id', $request->responsible_department)
                    ->increment('update_version', 1, 
                        [
                            'customer_department_stat' => 1,
                            'updated_at' => date('Y-m-d H:i:s'),
                            'last_updated_by' => $_SESSION['rapidx_user_id'],
                        ]
                    );

                for($index = 0; $index < count($request->responsible_department); $index++) {
                    $customer_depts = CustomerDepartments::on('mysql')
                        ->where('customer_audit_id', $request->customer_audit_id)
                        ->where('responsible_department_id', $request->responsible_department[$index])
                        ->get();

                    if(count($customer_depts) <= 0) {
                        CustomerDepartments::insert([
                            'customer_audit_id' => $request->customer_audit_id,
                            'responsible_department_id' => $request->responsible_department[$index],
                            'customer_department_stat' => 1,
                            'created_by' => $_SESSION['rapidx_user_id'],
                            'last_updated_by' => $_SESSION['rapidx_user_id'],
                            'update_version' => 1,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    }

                }

                if(isset($request->person_in_charge)){
                    CustomerPersonInCharges::whereNotIn('person_in_charge_id', $request->person_in_charge)
                        ->where('customer_audit_id', $request->customer_audit_id)
                        ->delete();

                    for($index = 0; $index < count($request->person_in_charge); $index++) {
                        $customer_per_in_charges = CustomerPersonInCharges::where('customer_audit_id', $request->customer_audit_id)
                            ->where('person_in_charge_id', $request->person_in_charge[$index])
                            ->get();

                        if(count($customer_per_in_charges) <= 0) {
                            CustomerPersonInCharges::insert([
                                'customer_audit_id' => $request->customer_audit_id,
                                'person_in_charge_id' => $request->person_in_charge[$index],
                                'created_by' => $_SESSION['rapidx_user_id'],
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                        }

                    }
                }
                else{
                    CustomerPersonInCharges::where('customer_audit_id', $request->customer_audit_id)
                        ->delete();
                }

                DB::commit();

                if(!isset($request->remove_img_corrective_action)){
                    if(isset($request->img_corrective_action)){
                        if(!Storage::putFileAs('public/audit_result_images/customer', $request->file('img_corrective_action'), $img_corr_act)){
                            return response()->json(['upload_result' => 0]);
                        }
                    }
                }

                if(!isset($request->remove_img_illustration)){
                    if(isset($request->img_illustration)){
                        if(!Storage::putFileAs('public/audit_result_images/customer', $request->file('img_illustration'), $img_illustration)){
                            return response()->json(['upload_result' => 0]);
                        }
                    }
                }

                if(!isset($request->remove_pdf_illustration)){
                    if(isset($request->pdf_illustration)){
                        if(!Storage::putFileAs('public/audit_result_pdf/customer', $request->file('pdf_illustration'), $pdf_illustration)){
                            return response()->json(['upload_result' => 0]);
                        }
                    }
                }

                if(!isset($request->remove_pdf_corrective_action)){
                    if(isset($request->pdf_corrective_action)){
                        if(!Storage::putFileAs('public/audit_result_pdf/customer', $request->file('pdf_corrective_action'), $pdf_corr_act)){
                            return response()->json(['upload_result' => 0]);
                        }
                    }
                }


                // if(!Storage::putFileAs('public/audit_result_images/customer', $request->file('img_corrective_action'), $img_corr_act)){
                //  return response()->json(['upload_result' => 0]);
                // }

                // if(!Storage::putFileAs('public/audit_result_images/customer', $request->file('img_illustration'), $img_illustration)){
                //  return response()->json(['upload_result' => 0]);
                // }
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

    // Commented at 01-07-2020
    // public function view_open_customer_audit_by_stat(Request $request){
    //     session_start();
    //     $responsible_department = $_SESSION['rapidx_department_id'];

    //     $customers = Customer::on('mysql')
    //                 ->with(
    //                     ['customer_departments' => function($query) use ($responsible_department){
    //                         $query->where('customer_department_stat', 1);
    //                         if($responsible_department != 0) {
    //                             $query->where('responsible_department_id', $responsible_department);
    //                         }
    //                     }, 'customer_departments.departments'])
    //                 ->withCount(['customer_departments' => function($query) use ($responsible_department){
    //                         $query->where('customer_department_stat', 1);
    //                         if($responsible_department != 0) {
    //                             $query->where('responsible_department_id', $responsible_department);
    //                         }
    //                     }])
    //                 ->having('customer_departments_count', '>', 0);

    //     if($request->date_from != '0'){
    //         $customers = $customers->where('audit_date_from', '>=', $request->date_from);
    //     }

    //     if($request->date_to != '0'){
    //         $customers = $customers->where('audit_date_to', '<=', $request->date_to);
    //     }

    //     if($request->audit_category != '0' && $request->has('audit_category')) {
    //         $customers = $customers->where('audit_category', $request->audit_category);
    //     }

    //     if($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
    //         $customers = $customers->where('purpose_of_audit', $request->purpose_of_audit);
    //     }

    //     if($request->classification != '0' && $request->has('classification')) {
    //         $customers = $customers->where('classification', $request->classification);
    //     }

    //     if($request->audit_stat != '0' && $request->has('audit_stat')) {
    //         $customers = $customers->where('audit_stat', $request->audit_stat);
    //     }

    //     if($request->customer_name != '0' && $request->has('customer_name')) {
    //         $customers = $customers->whereIn('customer_name', $request->customer_name);
    //     }

    //     $customers = $customers->get();

    //     return DataTables::of($customers)
    //         ->addColumn('label1', function($customer){
    //             $result = "";

    //             if($customer->sending_stat == 1){
    //                 $result .= '<span class="tag tag-warning">Pending</span>';
    //             }
    //             else{
    //                 $result .= '<span class="tag tag-success">Done</span>';
    //             }

    //             return $result;
    //         })
    //         ->addColumn('label2', function($customer){
    //             $result = "";

    //             if($customer->audit_stat == 1){
    //                 $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
    //             }
    //             else if($customer->audit_stat == 2){
    //                 $result .= '<span class="tag tag-success">For Closed-Out</span>';
    //             }
    //             else{
    //                 $result .= '<span class="tag tag-primary">Closed</span>';
    //             }

    //             return $result;
    //         })
    //         ->addColumn('formatted_audit_date', function($customer){
    //             return Carbon::parse($customer->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($customer->audit_date_to)->toFormattedDateString();
    //         })
    //         ->addColumn('action1', function($customer){
    //             $result = '<div class="btn-group">
    //                       <button type="button" class="btn btn-primary dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    //                         <i class="icon-gear"></i>
    //                       </button>
    //                       <div class="dropdown-menu dropdown-menu-right">
    //                         <button class="dropdown-item aViewCusAudit" customer-id="' . $customer->customer_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalViewCusAudit" data-keyboard="false">View</button>';

    //             if($customer->sending_stat != 2 && $customer->audit_stat == 1){
    //                 $result .= '<button class="dropdown-item aEditCusAudit" customer-id="' . $customer->customer_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditCusAudit" data-keyboard="false">Edit</button>';
    //             }
                            
    //             $result .= '</div>
    //                     </div>';

    //             return $result;
    //         })
    //         ->rawColumns(['action1', 'label1', 'label2', 'formatted_audit_date'])
    //         ->make(true);
    // }

    // public function view_open_customer_audit_by_stat(Request $request){
    //     session_start();
    //     $is_approver = false;
    //     $responsible_department[] = $_SESSION['rapidx_department_id'];
    //     $user_id = $_SESSION['rapidx_user_id'];

    //     $approvers = Approver::where('approver', $user_id)
    //                             ->where('approver_stat', 1)
    //                             ->get();
        
    //     if(count($approvers) > 0){
    //         $is_approver = true;
    //         for($index = 0; $index < count($approvers); $index++){
    //             $responsible_department[] = $approvers[$index]->department_id;
    //         }
    //     }

    //     $customers = Customer::on('mysql')
    //                 ->with(
    //                     ['customer_departments' => function($query) use ($responsible_department){
    //                         $query->where('customer_department_stat', 1);
    //                         // if($responsible_department != 0) {
    //                             $query->whereIn('responsible_department_id', $responsible_department);
    //                         // }
    //                     }, 'customer_departments.departments'])
    //                 ->withCount(['customer_departments' => function($query) use ($responsible_department){
    //                         $query->where('customer_department_stat', 1);
    //                         // if($responsible_department != 0) {
    //                             $query->whereIn('responsible_department_id', $responsible_department);
    //                         // }
    //                     }])
    //                 ->where('qad_approval', 1)
    //                 ->where('customer_audit_stat', 1)
    //                 ->having('customer_departments_count', '>', 0);

    //     if($request->date_from != '0'){
    //         $customers = $customers->where('audit_date_from', '>=', $request->date_from);
    //     }

    //     if($request->date_to != '0'){
    //         $customers = $customers->where('audit_date_to', '<=', $request->date_to);
    //     }

    //     if($request->audit_category != '0' && $request->has('audit_category')) {
    //         $customers = $customers->where('audit_category', $request->audit_category);
    //     }

    //     if($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
    //         $customers = $customers->where('purpose_of_audit', $request->purpose_of_audit);
    //     }

    //     if($request->classification != '0' && $request->has('classification')) {
    //         $customers = $customers->where('classification', $request->classification);
    //     }

    //     if($request->audit_stat != '0' && $request->has('audit_stat')) {
    //         $customers = $customers->where('audit_stat', $request->audit_stat);
    //     }

    //     if($request->customer_name != '0' && $request->has('customer_name')) {
    //         $customers = $customers->whereIn('customer_name', $request->customer_name);
    //     }

    //     $customers = $customers->get();

    //     return DataTables::of($customers)
    //         ->addColumn('label1', function($customer){
    //             $result = "";

    //             if($customer->sending_stat == 1){
    //                 $result .= '<span class="tag tag-warning">Pending</span>';
    //             }
    //             else{
    //                 $result .= '<span class="tag tag-success">Done</span>';
    //             }

    //             return $result;
    //         })
    //         ->addColumn('label2', function($customer){
    //             $result = "";

    //             if($customer->audit_stat == 1){
    //                 $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
    //             }
    //             else if($customer->audit_stat == 2){
    //                 $result .= '<span class="tag tag-success">For Closed-Out</span>';
    //             }
    //             else{
    //                 $result .= '<span class="tag tag-primary">Closed</span>';
    //             }

    //             return $result;
    //         })
    //         ->addColumn('formatted_audit_date', function($customer){
    //             return Carbon::parse($customer->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($customer->audit_date_to)->toFormattedDateString();
    //         })
    //         ->addColumn('action1', function($customer){
    //             $result = '<div class="btn-group">
    //                       <button type="button" class="btn btn-primary dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    //                         <i class="icon-gear"></i>
    //                       </button>
    //                       <div class="dropdown-menu dropdown-menu-right">
    //                         <button class="dropdown-item aViewCusAudit" customer-id="' . $customer->customer_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalViewCusAudit" data-keyboard="false">View</button>';

    //             if($customer->sending_stat != 2 && $customer->audit_stat == 1){
    //                 $result .= '<button class="dropdown-item aEditCusAudit" customer-id="' . $customer->customer_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditCusAudit" data-keyboard="false">Edit</button>';
    //             }
                            
    //             $result .= '</div>
    //                     </div>';

    //             return $result;
    //         })
    //         ->rawColumns(['action1', 'label1', 'label2', 'formatted_audit_date'])
    //         ->make(true);
    // }

    public function view_open_customer_audit_by_stat(Request $request){
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

        $customers = Customer::on('mysql')
                    ->with(
                        ['customer_departments' => function($query) use ($responsible_department){
                            $query->where('customer_department_stat', 1);
                            // if($responsible_department != 0) {
                                $query->whereIn('responsible_department_id', $responsible_department);
                            // }
                        }, 'customer_departments.departments'])
                    ->withCount(['customer_departments' => function($query) use ($responsible_department){
                            $query->where('customer_department_stat', 1);
                            // if($responsible_department != 0) {
                                $query->whereIn('responsible_department_id', $responsible_department);
                            // }
                        }])
                    ->where('qad_approval', 1)
                    ->having('customer_departments_count', '>', 0);

        if($request->date_from != '0'){
            $customers = $customers->where('audit_date_from', '>=', $request->date_from);
        }

        if($request->date_to != '0'){
            $customers = $customers->where('audit_date_to', '<=', $request->date_to);
        }

        if($request->audit_category != '0' && $request->has('audit_category')) {
            $customers = $customers->where('audit_category', $request->audit_category);
        }

        if($request->audit_type != '0' && $request->has('audit_type')) {
            $customers = $customers->where('audit_type', $request->audit_type);
        }

        if($request->audit_type_pref_rep_ctrl_no != '0' && $request->has('audit_type_pref_rep_ctrl_no')) {
            $customers = $customers->where('audit_report_control_no', 'LIKE', $request->audit_type_pref_rep_ctrl_no . "%");
        }

        if($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
            $customers = $customers->where('purpose_of_audit', $request->purpose_of_audit);
        }

        if($request->classification != '0' && $request->has('classification')) {
            $customers = $customers->where('classification', $request->classification);
        }

        if($request->audit_stat != '0' && $request->has('audit_stat')) {
            $customers = $customers->where('audit_stat', $request->audit_stat);
        }

        if($request->audit_report_control_no != '0' && $request->has('audit_report_control_no')) {
            $customers = $customers->where('audit_report_control_no', $request->audit_report_control_no);
        }

        if($request->item_no != '0' && $request->has('item_no')) {
            $customers = $customers->where('item_no', $request->item_no);
        }

        if($request->nc_control_no != '0' && $request->has('nc_control_no')) {
            $customers = $customers->where('nc_control_no', $request->nc_control_no);
        }

        if($request->evaluation_item != null && $request->has('evaluation_item')) {
            $tuvs = $tuvs->where('evaluation_item_id', $request->evaluation_item);
        }

        if($request->approval_stat != '0' && $request->has('approval_stat')) {
            if($request->approval_stat == 1){
                $customers = $customers->where(function($query){
                    $query->where('audit_stat', 2);
                    $query->where('o_s_approval', 0);
                });
            }
        }

        // if($request->evaluation_item != null && $request->has('evaluation_item')) {
        //     $customers = $customers->where('category_of_findings', $request->evaluation_item);
        // }

        $customers = $customers->where('customer_audit_stat', 1);

        $customers = $customers->get();

        return DataTables::of($customers)
            ->addColumn('label1', function($customer){
                $result = "";

                if($customer->sending_stat == 1){
                    $result .= '<span class="tag tag-warning">Pending</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function($customer){
                $result = "";

                if($customer->audit_stat == 1){
                    $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
                }
                else if($customer->audit_stat == 2){
                    $result .= '<span class="tag tag-success">For Closed-Out</span>';
                }
                else{
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function($customer){
                $result = '';
                if($customer->audit_date_from != $customer->audit_date_to){
                    $result = Carbon::parse($customer->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($customer->audit_date_to)->toFormattedDateString();
                }
                else{
                    $result = Carbon::parse($customer->audit_date_from)->toFormattedDateString();
                }
                return $result;
            })
            ->addColumn('action1', function($customer) use($is_approver, $user_access){
                $final = '';
                $final = '<button class="btn-actions btn btn-sm btn-info aViewCusAudit" customer-id="' . $customer->customer_audit_id . '" type="button" data-toggle="modal" data-target="#modalViewCusAudit" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="View" title="View"><i class="icon-file-text-o"></i></button> ';

                if($customer->audit_stat == 1){
                    $final .= '<button class="btn-actions btn btn-sm btn-success aEditCus" customer-id="' . $customer->customer_audit_id . '" type="button" data-toggle="modal" data-target="#modalEditCusAudit" data-keyboard="false" title="Edit"><i class="icon-edit2"></i></button> ';
                }
                else{
                    if($is_approver){
                        if($customer->audit_stat == 2 && $customer->o_s_approval == 0) {
                            $final .= '<button class="btn-actions btn btn-sm btn-success aEditCus" customer-id="' . $customer->customer_audit_id . '" type="button" data-toggle="modal" data-target="#modalEditCusAudit" data-keyboard="false" title="Edit"><i class="icon-edit2"></i></button> ';
                        }
                    }
                }

                // Working, uncomment if will use
                if($is_approver && $user_access == 5){
                    if($customer->o_s_approval == '0' && $customer->audit_stat == 2){

                        $final .= '<button class="btn-actions btn btn-sm btn-primary aOSApprovalCusAudit" type="button" customer-id="' . $customer->customer_audit_id . '" o-s-approval="1" data-toggle="modal" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="Approve" title="Approve"><i class="icon-like"></i></button> '; 
                    }
                }
                return $final;
            })
            ->addColumn('check_box_send', function($customer){
                $result = "";

                if($customer->qad_approval == '0'){
                    $result = "disabled";
                }

                if($customer->deadline_of_submission == null){
                    $result = "disabled";
                }

                return '<center><input type="checkbox" class="chkSendCusAudit" customer-id="' . $customer->customer_audit_id . '" ' . $result . '></center>';
            })
            ->addColumn('approval_stat', function($customer){
                $result = "";

                if($customer->audit_stat == 1 && $customer->qad_approval == 1){
                    $result .= '<span class="tag tag-success">Approved By QAD</span>';
                }
                else if($customer->audit_stat == 1 && $customer->qad_approval == 0){
                    $result .= '<span class="tag tag-warning">For QAD Approval</span>';
                }
                else if($customer->audit_stat == 2 && $customer->o_s_approval == 1){
                    $result .= '<span class="tag tag-success">Approved By Section Head</span>';
                }
                else if($customer->audit_stat == 2 && $customer->o_s_approval == 0){
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

    public function edit_open_customer_audit(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        $data = $request->all();

        $validator = Validator::make($data, [
            'root_cause' => ['required', 'string'],
            'improvement_plan' => ['required', 'string'],
            'commitment_date' => ['required', 'date'],
            'person_in_charge' => ['required', 'string'],
        ]);

        if ($validator->passes()) {
            DB::beginTransaction();

            try{
                Customer::where('customer_audit_id', $request->customer_audit_id)
                    ->increment('update_version', 1,
                    [
                        'root_cause' => $request->root_cause,
                        'improvement_plan' => $request->improvement_plan,
                        'commitment_date' => $request->commitment_date,
                        'person_in_charge' => $request->person_in_charge,
                        'audit_stat' => 2,
                        'last_updated_by' => $_SESSION["rapidx_user_id"],
                        'update_version' => 1,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );

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

    public function get_chart_customer_audit_by_stat(Request $request){
        session_start();
        date_default_timezone_set('Asia/Manila');

        $year = $request->year;
        $month_from = 04;
        $month_to = 03;
      
        $date_from = date($year . '-' . $month_from . '-' . '01');
        $date_to = date(($year + 1) . '-' . $month_to . '-' . 't');
      
        $date_create_from = date_create($date_from);
        $date_create_to = date_create($date_to);

        $customers = Customer::with(
                        ['customer_departments' => function($query){
                            $query->where('customer_department_stat', 1);
                        }, 'customer_departments.departments'])
                    ->withCount(['customer_departments' => function($query){
                            $query->where('customer_department_stat', 1);
                        }])
                    ->having('customer_departments_count', '>', 0)
                    ->where('audit_date_from', '>=', $date_create_from)
                    ->where('audit_date_to', '<=', $date_create_to)
                    ->get();

        return response()->json(['customers' => $customers]);
    }

    public function view_customer_audit_history(Request $request){
        session_start();

        $customers = Customer::on('mysql')
                        // ->join('db_rapidx.departments', 'customer_audits.responsible_department', '=', 'db_rapidx.departments.department_id')
                        // ->whereDate('customer_audits.created_at', Carbon::today())
                        ->where('responsible_department', $_SESSION["rapidx_department_id"])->where('audit_stat', 2)
                        ->where('audit_stat', 2)
                        ->where('sending_stat', 2)
                        ->get();

        return DataTables::of($customers)
            ->addColumn('label1', function($customer){
                $result = "";

                if($customer->sending_stat == 1){
                    $result .= '<span class="tag tag-warning">Pending</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function($customer){
                $result = "";

                if($customer->audit_stat == 1){
                    $result .= '<span class="tag tag-warning">Open</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Closed</span>';
                }

                return $result;
            })
            ->addColumn('action1', function($customer){
                // $result = '<div class="btn-group">
                //           <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                //             Action
                //           </button>
                //           <div class="dropdown-menu dropdown-menu-right">
                //             <button class="dropdown-item aViewCusAudit" customer-id="' . $customer->customer_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalViewCusAudit" data-keyboard="false">View</button>';
                            
                // $result .= '</div>
                //         </div>';

                return '<button class="btn btn-primary aViewCusAudit" customer-id="' . $customer->customer_audit_id . '" type="button" data-toggle="modal" data-target="#modalViewCusAudit" data-keyboard="false">View</button>';
            })
            ->rawColumns(['action1', 'label1', 'label2'])
            ->make(true);
    }

    public function test_customer_auto_email(Request $request){
        session_start();
        date_default_timezone_set('Asia/Manila');
        $tomorrow = Carbon::now();
        $tomorrow = $tomorrow->addDays(1)->toDateString();

        $customer_for_imp_plan_over_and_due = Customer::with(
                        ['customer_departments' => function($query){
                            $query->where('customer_department_stat', 1);
                        }, 'customer_departments.departments', 'person_in_charges', 'person_in_charges.person_in_charge_record'])
                ->where('sending_stat', 1)
                ->where('audit_stat', 1)
                ->whereDate('deadline_of_submission', '<=', $tomorrow)
                ->get();


        $customer_for_imp_plan_before_due = Customer::with(
                        ['customer_departments' => function($query){
                            $query->where('customer_department_stat', 1);
                        }, 'customer_departments.departments', 'person_in_charges', 'person_in_charges.person_in_charge_record'])
                ->where('sending_stat', 1)
                ->where('audit_stat', 1)
                ->whereDate('deadline_of_submission', '=', $tomorrow)
                ->get();

        $customer_for_close_out_over_and_due = Customer::with(
                        ['customer_departments' => function($query){
                            $query->where('customer_department_stat', 1);
                        }, 'customer_departments.departments', 'person_in_charges', 'person_in_charges.person_in_charge_record'])
                ->where('sending_stat', 1)
                ->where('audit_stat', 2)
                ->whereDate('commitment_date', '<=', $tomorrow)
                ->get();

        $customer_for_close_out_before_due = Customer::with(
                        ['customer_departments' => function($query){
                            $query->where('customer_department_stat', 1);
                        }, 'customer_departments.departments', 'person_in_charges', 'person_in_charges.person_in_charge_record'])
                ->where('sending_stat', 1)
                ->where('audit_stat', 2)
                ->whereDate('commitment_date', '=', $tomorrow)
                ->get();

        $customer_audit_ids = [];

        foreach ($customer_for_imp_plan_over_and_due as $customer_for_imp_plan_over_and_due_single) {
            $customer_audit_ids[] = $customer_for_imp_plan_over_and_due_single->customer_audit_id;
        }

        foreach ($customer_for_imp_plan_before_due as $customer_for_imp_plan_before_due_single) {
            $customer_audit_ids[] = $customer_for_imp_plan_before_due_single->customer_audit_id;
        }

        foreach ($customer_for_close_out_over_and_due as $customer_for_close_out_over_and_due_single) {
            $customer_audit_ids[] = $customer_for_close_out_over_and_due_single->customer_audit_id;
        }

        foreach ($customer_for_close_out_before_due as $customer_for_close_out_before_due_single) {
            $customer_audit_ids[] = $customer_for_close_out_before_due_single->customer_audit_id;
        }

        return $customer_audit_ids;
    }

    public function change_customer_cus_audit_stat(Request $request) {
        date_default_timezone_set('Asia/Manila');
        session_start();
        DB::beginTransaction();

        try{
            Customer::where('customer_audit_id', $request->customer_audit_id)
                ->increment('update_version', 1, 
                    [
                        'customer_audit_stat' => $request->customer_audit_stat,
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

    public function get_customer_chart_by_areas_for_improvement(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        $from = $request->from;
        $to = $request->to;
        // $month_from = '01';
        // $month_to = '12';

        // $date_from = $from . '-' . $month_from . '-' . '01';
        // $date_to = $to . '-' . $month_to . '-' . '31';

        // $date_create_from = Carbon::parse($date_from)->format('Y-m-d');
        // $date_create_to = Carbon::parse($date_to)->format('Y-m-d');

        $date_from = $from . '-' . '01';
        $date_to = $to . '-' . '31';

        $date_create_from = Carbon::parse($date_from)->format('Y-m-d');
        $date_create_to = Carbon::parse($date_to)->format('Y-m-d');

        $customer_audits = Customer::where('customer_audit_stat', 1)
                            ->whereDate('audit_date_from', '>=', $date_create_from)
                            ->whereDate('audit_date_from', '<=', $date_create_to)
                            ->groupBy('evaluation_item')
                            ->select('evaluation_item',  DB::raw('count(*) as total_count'))
                            ->orderBy('total_count', 'desc')
                            ->get();

        return response()->json(['customer_audits' => $customer_audits]);
    }

    public function get_customer_chart_by_customer_name(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        $from = $request->from;
        $to = $request->to;

        $date_from = $from . '-' . '01';
        $date_to = $to . '-' . '31';

        $date_create_from = Carbon::parse($date_from)->format('Y-m-d');
        $date_create_to = Carbon::parse($date_to)->format('Y-m-d');

        $customer_audits = Customer::where('customer_audit_stat', 1)
                            ->whereDate('audit_date_from', '>=', $date_create_from)
                            ->whereDate('audit_date_from', '<=', $date_create_to)
                            ->groupBy('customer_name')
                            ->select('customer_name',  DB::raw('count(*) as total_count'))
                            ->orderBy('total_count', 'desc')
                            ->get();

        return response()->json(['customer_audits' => $customer_audits]);
    }

    public function get_customer_unique_evaluation_items(Request $request){
        session_start();
        $customer_audits = Customer::select('evaluation_item')->distinct()->get();

        return response()->json(['customer_audits' => $customer_audits]);
    }

    public function get_customers(Request $request){
        $customers = Customer::select('customer_name')
        ->whereNotNull('customer_name')
        ->where('customer_name', '!=', '')
        ->distinct()
        ->pluck('customer_name');
        return $customers;
    }

    public function save_customer(Request $request){
        session_start();
        DB::beginTransaction();
        try{
            $customerList = new CustomerList();
            if(isset($request->customer_id)){ // Update
                $customer = $customerList->find($request->customer_id);
                $customer->customer_name = $request->customer_name;
                $customer->updated_by    = $_SESSION["rapidx_user_id"];
                $result = $customer->save();
            }
            else{ // Create
                $customerList->customer_name = $request->customer_name;
                $customerList->created_by    = $_SESSION["rapidx_user_id"];
                $result = $customerList->save();
            }
            DB::commit();
            return response()->json(['result' => $result]);
        }catch(\Throwable $e){
            DB::rollback();
            return response()->json(['result' => false, 'error' => $e->getMessage()], 422);
        }
    }
    
    public function dt_get_customer_list(Request $request){
        $customers = CustomerList::get();

        return DataTables::of($customers)
        ->addColumn('action', function($customer){
            $result = "";
            $result .= "<button class='btn btn-sm btn-info btnEditCustomer' data-id='{$customer->id}' data-name='{$customer->customer_name}'><i class='icon-edit'></i></button>";
            if(is_null($customer->deleted_at)){
                $result .= "<button class='btn btn-sm btn-danger btnUpdateStatus' data-id='{$customer->id}' data-name='{$customer->customer_name}' data-status='0'><i class='icon-trash'></i></button>";
            }
            else{
                $result .= "<button class='btn btn-sm btn-success btnUpdateStatus' data-id='{$customer->id}' data-name='{$customer->customer_name}' data-status='1'><i class='icon-redo'></i></button>";
            }
            return $result;
        })
        ->rawColumns(['action'])
        ->make(true);
    }

    public function get_customer_list(Request $request){
        return CustomerList::whereNull('deleted_at')->pluck('customer_name');
    }
}
