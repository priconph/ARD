<?php

namespace App\Http\Controllers;

use Mail;
use App\User;
use DataTables;
use App\Model\TUV;
use Carbon\Carbon;
use App\Model\Approver;
use App\Model\Department;
use Illuminate\Support\Str;
use App\Mail\SendNewTUVMail;
use Illuminate\Http\Request;
use App\Model\EmailRecipient;
use App\Model\TUVDepartments;
use App\Mail\SendTUVBatchMail;
use App\Jobs\SendNewTUVMailJob;
use App\Jobs\SendTUVBatchMailJob;
use App\Jobs\TUVBeforeDueMailJob;
use App\Model\TuvAuditAttachment;
use App\Exports\AuditResultExport;
use Illuminate\Support\Facades\DB;
use App\Model\TUVConPersonInCharges;
use App\Model\TUVSysPersonInCharges;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use App\Jobs\SendAuditResultEmailJob;
use App\Model\TUVCorrPersonInCharges;
use App\Http\Requests\TUVAuditRequest;
use App\Model\CustomerAuditAttachment;
use App\Model\InternalAuditAttachment;
use App\Model\SupplierAuditAttachment;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\AuditResultRequest;
use Illuminate\Support\Facades\Validator;

class TUVController extends Controller
{
    //
    public function get_tuv_due_date(Request $request)
    {
        // FOR IMPROVEMENT PLAN
        session_start();
        $today = Carbon::now()->format('Y-m-d');
        $data = TUV::where('audit_stat', 1)
            ->whereDate('deadline_for_submission', $today)
            ->get();

        return response()->json(['data' => $data]);
    }

    public function get_tuv_over_due_date(Request $request)
    {
        // FOR IMPROVEMENT PLAN
        session_start();
        $today = Carbon::now()->format('Y-m-d');

        $data = TUV::where('audit_stat', 1)
                ->whereDate('deadline_for_submission', '>', $today)
                ->get();

        return response()->json(['data' => $data]);
    }

    public function get_tuv_before_due_date(Request $request)
    {
        // FOR IMPROVEMENT PLAN
        session_start();
        $days_before = 3;
        $date = Carbon::now()->subDays($days_before + 1)->format('Y-m-d');
        $today = Carbon::now()->format('Y-m-d');

        // return $date;

        $data = TUV::where('audit_stat', 1)
                ->whereDate('deadline_for_submission', '<', $today)
                ->whereDate('deadline_for_submission', '>', $date)
                ->get();

        return response()->json(['data' => $data]);
    }


    public function tuv_export(Request $request)
    {
        session_start();
        date_default_timezone_set('Asia/Manila');

        $responsible_department = 0;
        if($request->responsible_department != '0' && $request->has('responsible_department')) {
           $responsible_department = $request->responsible_department;
           $responsible_department = explode(",", $responsible_department);
        }
        
        $tuvs = TUV::with(
            ['tuv_departments' => function($query) use ($responsible_department){
                $query->where('tuv_department_stat', 1);
                if($responsible_department != 0) {
                    $query->whereIn('responsible_department_id', $responsible_department);
                }
            }, 'tuv_departments.departments', 'user_created_by', 'user_last_updated_by', 'tuv_corr_per_in_charges', 'tuv_con_per_in_charges', 'tuv_sys_per_in_charges', 'tuv_corr_per_in_charges.tuv_corr_per_in_charge_record', 'tuv_con_per_in_charges.tuv_con_per_in_charge_record', 'tuv_sys_per_in_charges.tuv_sys_per_in_charge_record'])
        ->withCount(['tuv_departments' => function($query) use ($responsible_department){
                $query->where('tuv_department_stat', 1);
                if($responsible_department != 0) {
                    $query->whereIn('responsible_department_id', $responsible_department);
                }
            }])
        ->having('tuv_departments_count', '>', 0);

        if($request->date_from != '0'){
            $tuvs = $tuvs->where('audit_date_from', '>=', $request->date_from);
        }

        if($request->date_to != '0'){
            $tuvs = $tuvs->where('audit_date_to', '<=', $request->date_to);
        }

        if($request->audit_category != '0' && $request->has('audit_category')) {
            $tuvs = $tuvs->where('audit_category', $request->audit_category);
        }

        if($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
            $tuvs = $tuvs->where('purpose_of_audit', $request->purpose_of_audit);
        }

        if($request->classification != '0' && $request->has('classification')) {
            $tuvs = $tuvs->where('classification', $request->classification);
        }

        if($request->audit_stat != '0' && $request->has('audit_stat')) {
            $tuvs = $tuvs->where('audit_stat', $request->audit_stat);
        }

        $tuvs = $tuvs->get();

        return Excel::download(new AuditResultExport(1, $tuvs, '', '', ''), 'TUV' . date('Y-m-d H:i:s') . '.xlsx');
    }

    // Completely working using eager loading!
    public function view_tuv_audit_by_stat_v2(Request $request){ 
        session_start();

        $user_access = $request->user_access;

        $is_approver = false;

        $approvers = Approver::where('approver', $_SESSION["rapidx_user_id"])
                    ->where('approver_stat', '1')
                    ->get();

        if(count($approvers) > 0) {
            $is_approver = true;
        }

        // $responsible_department = $request->responsible_department;
        
        $tuvs = TUV::with(
            [
                'tuv_departments' => function($query){
                    $query->where('tuv_department_stat', 1);
                }, 
                'tuv_departments.departments',
                'qad_approver',
                'user_last_updated_by'
            ])
            ->when($request->isArchived == 1, function ($query) {
                $query->where('tuv_audit_stat', 5);
            }, function ($query) {
                $query->where('tuv_audit_stat', '<>', 5);
            });
       
        if($request->filterYear != '' || $request->filterYear != null) {
            $tuvs = $tuvs->whereYear('audit_date_from', $request->filterYear);

        }
        $tuvs = $tuvs->get();


        return DataTables::of($tuvs)
            ->addColumn('raw_ctrl_no', function($tuv){
                return $tuv->ctrl_no . $tuv->tuv_audit_id;
            })
            ->addColumn('raw_audit_status', function($tuv){
                $result = "";

                if($tuv->audit_stat == 1){
                    $result .= '<span class="tag tag-warning">Open</span>';
                }
                else if($tuv->audit_stat == 2){
                    $result .= '<span class="tag tag-warning">For QAD Approval</span>';
                }
                else{
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function($tuv){
                $result = '';
                if($tuv->audit_date_from != $tuv->audit_date_to){
                    $result = Carbon::parse($tuv->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($tuv->audit_date_to)->toFormattedDateString();
                }
                else{
                    $result = Carbon::parse($tuv->audit_date_from)->toFormattedDateString();
                }
                return $result;
            })
            ->addColumn('action1', function($tuv) use ($request){
                $result = '';
                $result = '<button class="btn-actions btn btn-sm btn-info aViewTUVAudit" tuv-id="' . $tuv->tuv_audit_id . '" type="button" data-toggle="modal" data-target="#modalViewTUVAudit" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="View" title="View"><i class="icon-file-text-o"></i></button> ';


                if($tuv->tuv_audit_stat == 1 || $tuv->tuv_audit_stat == 4) {
                    // $result .= '<button class="btn-actions btn btn-sm btn-success aEditTUV" tuv-id="' . $tuv->tuv_audit_id . '" type="button" data-toggle="modal" data-target="#modalEditTUVAudit" data-keyboard="false" title="Edit"><i class="icon-edit2"></i></button> ';
                    $result .= '<button class="btn-actions btn btn-sm btn-warning aEditTUV" tuv-id="' . $tuv->tuv_audit_id . '" type="button" data-toggle="modal" data-target="#modalTUVAuditDetails" data-keyboard="false" title="Edit"><i class="icon-edit2"></i></button> ';

                    $result .= '<button class="btn-actions btn btn-sm btn-danger aChangeTUVAuditStat" type="button" tuv-id="' . $tuv->tuv_audit_id . '" tuv-audit-stat="5" data-toggle="modal" data-target="#modalChangeTUVAuditStat" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="Archive" title="Archive"><i class="icon-trash2"></i></button> ';
                }

                if($tuv->audit_stat == 1 && $request->isArchived != 1){
                    $result .= "<button class='btn btn-success btn-sm btnProceedApproval' type='button' data-aType='tuv' data-id='{$tuv->tuv_audit_id}'><i class='icon-paper-plane'></i></button>";
                }

                if($request->isArchived == 1){
                    $result .= '<button class="btn-actions btn btn-sm btn-success aChangeTUVAuditStat" type="button" tuv-id="' . $tuv->tuv_audit_id . '" tuv-audit-stat="1" data-toggle="modal" data-target="#modalChangeTUVAuditStat" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="Restore" title="Restore"><i class="icon-history2"></i></button> ';
                }

                return $result;
            })
            ->addColumn('audit_category_label', function($tuv){
                if($tuv->audit_category == 1){
                    return "ISO9001 / IATF16949";
                }
                else{
                    return "ISO14001";   
                }
            })
            ->addColumn('check_box_send', function($tuv){
                return '<center><input type="checkbox" class="chkSendTUVAudit" tuv-id="' . $tuv->tuv_audit_id . '"></center>';
            })
            ->addColumn('approval_stat', function($tuv){
                $result = "---";
                if($tuv->tuv_audit_stat == 2){
                    $result = '<span class="tag tag-warning">Pending</span>';
                }
                else if($tuv->tuv_audit_stat == 3){
                    $result = '<span class="tag tag-success">approved</span>';
                    $result .= "<br><em>Approved by: {$tuv->qad_approver->name}</em>";
                }
                else if($tuv->tuv_audit_stat == 4){
                    $result = '<span class="tag tag-danger">disapproved</span>';
                    $result .= "<br><em>Disapproved by: {$tuv->user_last_updated_by->name}</em>";
                    $result .= "<br><em><strong>Remarks</strong>: {$tuv->qad_dis_remarks}</em>";
                }

                return $result;
            })
            ->addColumn('raw_responsible_department', function($tuv){
                $result = "";

                foreach($tuv->tuv_departments as $tuv_department) {
                    $result .= $tuv_department->departments[0]->department_name . ", ";
                }
                return $result;
            })
            ->addColumn('raw_audit_category', function($tuv){
                if($tuv->audit_category == 1){
                    return "ISO9001 / IATF16949";
                }
                else if($tuv->audit_category == 2){
                    return "ISO14001";   
                }
                else if($tuv->audit_category == 3){
                    return "ISO9001";   
                }
                else{
                    return "";   
                }
            })
            ->rawColumns(['action1', 'raw_audit_status', 'formatted_audit_date', 'check_box_send', 'approval_stat', 'raw_responsible_department'])
            ->make(true);
    }

    public function view_tuv_audit_by_stat(Request $request){ // original Controller for audit_result_v1 blade
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
        
        $tuvs = TUV::with(
                        ['tuv_departments' => function($query) use ($responsible_department){
                            $query->where('tuv_department_stat', 1);
                            if($responsible_department != 0) {
                                $query->whereIn('responsible_department_id', $responsible_department);
                            }
                        }, 'tuv_departments.departments'])
                    ->withCount(['tuv_departments' => function($query) use ($responsible_department){
                            $query->where('tuv_department_stat', 1);
                            if($responsible_department != 0) {
                                $query->whereIn('responsible_department_id', $responsible_department);
                            }
                        }])
                    ->having('tuv_departments_count', '>', 0);
                    // ->where('tuv_audit_stat', 1);

        if($request->audit_id != '0'){
            $tuvs = $tuvs->where('tuv_audit_id', $request->audit_id);
        }

        if($request->date_from != '0'){
            $tuvs = $tuvs->where('audit_date_from', '>=', $request->date_from);
        }

        if($request->date_to != '0'){
            $tuvs = $tuvs->where('audit_date_to', '<=', $request->date_to);
        }

        if($request->audit_category != '0' && $request->has('audit_category')) {
            $tuvs = $tuvs->where('audit_category', $request->audit_category);
        }

        if($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
            $tuvs = $tuvs->where('purpose_of_audit', $request->purpose_of_audit);
        }

        if($request->classification != '0' && $request->has('classification')) {
            $tuvs = $tuvs->where('classification', $request->classification);
        }

        if($request->audit_stat != '0' && $request->has('audit_stat')) {
            $tuvs = $tuvs->where('audit_stat', $request->audit_stat);
        }

        if($request->item_no != '0' && $request->has('item_no')) {
            $tuvs = $tuvs->where('item_no', $request->item_no);
        }

        // if($request->evaluation_item != null && $request->has('evaluation_item')) {
        //     $tuvs = $tuvs->where('evaluation_item_id', $request->evaluation_item);
        // }

        if($request->evaluation_item != null && $request->has('evaluation_item')) {
            if($request->evaluation_item == 'Blank'){
                $tuvs = $tuvs->where('evaluation_item_id', null);
            }
            else{
                $tuvs = $tuvs->where('evaluation_item_id', $request->evaluation_item);
            }
        }

        if($request->approval_stat != '0' && $request->has('approval_stat')) {
            if($request->approval_stat == 1){
                $tuvs = $tuvs->where(function($query){
                    $query->where('audit_stat', 1);
                    $query->where('qad_approval', 0);
                });
            }
            else if($request->approval_stat == 2){
                $tuvs = $tuvs->where(function($query){
                    $query->where('audit_stat', 2);
                    $query->where('o_s_approval', 0);
                });
            }
        }

        if($request->evaluation_item != null && $request->has('evaluation_item')) {
            if($request->evaluation_item == 'Blank'){
                $tuvs = $tuvs->where('evaluation_item_id', null);
            }
            else{
                $tuvs = $tuvs->where('evaluation_item_id', $request->evaluation_item);
            }
        }

        $tuvs = $tuvs->where('tuv_audit_stat', $request->tuv_audit_stat);

        $tuvs = $tuvs->get();

        return DataTables::of($tuvs)
            ->addColumn('label1', function($tuv){
                $result = "";

                if($tuv->sending_stat == 1){
                    $result .= '<span class="tag tag-warning">Pending</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function($tuv){
                $result = "";

                if($tuv->audit_stat == 1){
                    // $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
                    $result .= '<span class="tag tag-warning">Open</span>';
                }
                // else if($tuv->audit_stat == 2){
                //     $result .= '<span class="tag tag-success">For Closed-Out</span>';
                // }
                else{
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function($tuv){
                $result = '';
                if($tuv->audit_date_from != $tuv->audit_date_to){
                    $result = Carbon::parse($tuv->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($tuv->audit_date_to)->toFormattedDateString();
                }
                else{
                    $result = Carbon::parse($tuv->audit_date_from)->toFormattedDateString();
                }
                return $result;
            })
            ->addColumn('action1', function($tuv) use($is_approver, $user_access){
                $test = '';
                $test = '<button class="btn-actions btn btn-sm btn-info aViewTUVAudit" tuv-id="' . $tuv->tuv_audit_id . '" type="button" data-toggle="modal" data-target="#modalViewTUVAudit" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="View" title="View"><i class="icon-file-text-o"></i></button> ';


                if($tuv->tuv_audit_stat == 1) {
                    // $test .= '<button class="btn-actions btn btn-sm btn-success aEditTUV" tuv-id="' . $tuv->tuv_audit_id . '" type="button" data-toggle="modal" data-target="#modalEditTUVAudit" data-keyboard="false" title="Edit"><i class="icon-edit2"></i></button> ';
                    $test .= '<button class="btn-actions btn btn-sm btn-success aEditTUV" tuv-id="' . $tuv->tuv_audit_id . '" type="button" data-toggle="modal" data-target="#modalTUVAuditDetails" data-keyboard="false" title="Edit"><i class="icon-edit2"></i></button> ';

                    $test .= '<button class="btn-actions btn btn-sm btn-danger aChangeTUVAuditStat" type="button" tuv-id="' . $tuv->tuv_audit_id . '" tuv-audit-stat="2" data-toggle="modal" data-target="#modalChangeTUVAuditStat" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="Archive" title="Archive"><i class="icon-trash2"></i></button> ';
                }
                else {
                    $test .= '<button class="btn-actions btn btn-sm btn-warning aChangeTUVAuditStat" type="button" tuv-id="' . $tuv->tuv_audit_id . '" tuv-audit-stat="1" data-toggle="modal" data-target="#modalChangeTUVAuditStat" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="Restore" title="Restore"><i class="icon-history2"></i></button> ';
                }

                if($is_approver && $user_access == 4){
                    if($tuv->qad_approval == '0' && $tuv->audit_stat == 1){

                        $test .= '<button class="btn-actions btn btn-sm btn-primary aQADApprovalTUVAudit" type="button" tuv-id="' . $tuv->tuv_audit_id . '" qad-approval="1" data-toggle="modal" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="Approve" title="Approve"><i class="icon-like"></i></button> '; 
                    }
                }

                return $test;
            })
            ->addColumn('audit_category_label', function($tuv){
                if($tuv->audit_category == 1){
                    return "ISO9001 / IATF16949";
                }
                else{
                    return "ISO14001";   
                }
            })
            ->addColumn('check_box_send', function($tuv){
                return '<center><input type="checkbox" class="chkSendTUVAudit" tuv-id="' . $tuv->tuv_audit_id . '"></center>';
            })
            ->addColumn('approval_stat', function($tuv){
                $result = "";

                if($tuv->audit_stat == 1 && $tuv->qad_approval == 1){
                    $result .= '<span class="tag tag-success">Approved By QAD</span>';
                }
                else if($tuv->audit_stat == 1 && $tuv->qad_approval == 0){
                    $result .= '<span class="tag tag-warning">For QAD Approval</span>';
                }
                else if($tuv->audit_stat == 2 && $tuv->o_s_approval == 1){
                    $result .= '<span class="tag tag-success">Approved By Section Head</span>';
                }
                else if($tuv->audit_stat == 2 && $tuv->o_s_approval == 0){
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

    public function view_tuv_audit_for_approval(Request $request){
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
            $tuvs = TUV::with(
                            ['tuv_departments' => function($query) use ($responsible_department){
                                $query->where('tuv_department_stat', 1);
                                if($responsible_department != 0) {
                                    $query->whereIn('responsible_department_id', $responsible_department);
                                }
                            }, 'tuv_departments.departments', 'tuv_corr_per_in_charges', 'tuv_con_per_in_charges', 'tuv_sys_per_in_charges'])
                        ->withCount(['tuv_departments' => function($query) use ($responsible_department){
                                $query->where('tuv_department_stat', 1);
                                if($responsible_department != 0) {
                                    $query->whereIn('responsible_department_id', $responsible_department);
                                }
                            }])
                        ->where('qad_approval', 0)
                        ->having('tuv_departments_count', '>', 0);
        }
        else{
            $responsible_department = [];

            foreach ($approvers as $approver) {
                $responsible_department[] = $approver->department_id;
            }

            if($is_approver == false){
                $responsible_department[] = $_SESSION['rapidx_department_id'];
            }

            $tuvs = TUV::on('mysql')
                        ->with(
                            ['tuv_departments' => function($query) use ($responsible_department){
                                $query->where('tuv_department_stat', 1);
                                if($responsible_department != 0) {
                                    $query->whereIn('responsible_department_id', $responsible_department);
                                }
                            }, 'tuv_departments.departments','tuv_corr_per_in_charges', 'tuv_con_per_in_charges', 'tuv_sys_per_in_charges'])
                        ->withCount(['tuv_departments' => function($query) use ($responsible_department){
                                $query->where('tuv_department_stat', 1);
                                if($responsible_department != 0) {
                                    $query->whereIn('responsible_department_id', $responsible_department);
                                }
                            }])
                        ->where('o_s_approval', 0)
                        ->where('audit_stat', 2)
                        ->where('tuv_audit_stat', 1)
                        ->having('tuv_departments_count', '>', 0);
        }

        if($request->date_from != '0'){
            $tuvs = $tuvs->where('audit_date_from', '>=', $request->date_from);
        }

        if($request->date_to != '0'){
            $tuvs = $tuvs->where('audit_date_to', '<=', $request->date_to);
        }

        if($request->audit_category != '0' && $request->has('audit_category')) {
            $tuvs = $tuvs->where('audit_category', $request->audit_category);
        }

        if($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
            $tuvs = $tuvs->where('purpose_of_audit', $request->purpose_of_audit);
        }

        if($request->classification != '0' && $request->has('classification')) {
            $tuvs = $tuvs->where('classification', $request->classification);
        }

        if($request->audit_stat != '0' && $request->has('audit_stat')) {
            $tuvs = $tuvs->where('audit_stat', $request->audit_stat);
        }

        $tuvs = $tuvs->get();

        return DataTables::of($tuvs)
            ->addColumn('label1', function($tuv){
                $result = "";

                if($tuv->sending_stat == 1){
                    $result .= '<span class="tag tag-warning">Pending</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function($tuv){
                $result = "";

                if($tuv->audit_stat == 1){
                    $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
                }
                else if($tuv->audit_stat == 2){
                    $result .= '<span class="tag tag-success">For Closed-Out</span>';
                }
                else{
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function($tuv){
                return Carbon::parse($tuv->audit_date_from)->toFormattedDateString() . " - " . Carbon::parse($tuv->audit_date_to)->toFormattedDateString();
            })
            // ->addColumn('action1', function($tuv){
            //     $result = '<div class="btn-group">
            //               <button type="button" class="btn btn-primary dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            //                 <i class="icon-gear"></i>
            //               </button>
            //               <div class="dropdown-menu dropdown-menu-right">
            //                 <button class="dropdown-item aViewTUVAudit" tuv-id="' . $tuv->tuv_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalViewTUVAudit" data-keyboard="false">View</button>';

            //     // if($tuv->sending_stat != 2){
            //         $result .= '<button class="dropdown-item aEditTUV" tuv-id="' . $tuv->tuv_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditTUVAudit" data-keyboard="false">Edit</button>';
            //     // }            

            //     if($tuv->sending_stat == 1){
            //         $result .= '<button class="dropdown-item aDoneTUVAudit" type="button" tuv-id="' . $tuv->tuv_audit_id . '" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalDoneTUVAudit" data-keyboard="false">Done</button>';
            //     }

            //     if($tuv->sending_stat == 2) {
            //         $result .= '<button class="dropdown-item aPendTUVAudit" type="button" style="padding: 1px 1px; text-align: center;" tuv-id="' . $tuv->tuv_audit_id . '" data-toggle="modal" data-target="#modalPendTUVAudit" data-keyboard="false">Pend</button>';
            //     }

            //     if($tuv->audit_stat != 1){
            //         $result .= '<button class="dropdown-item aChangeTUVAuditStat" type="button" tuv-id="' . $tuv->tuv_audit_id . '" audit-stat="1" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalChangeAuditStatTUVAudit" data-keyboard="false">For Improvement Plan</button>';
            //     }

            //     if($tuv->audit_stat != 2){
            //         $result .= '<button class="dropdown-item aChangeTUVAuditStat" type="button" tuv-id="' . $tuv->tuv_audit_id . '" audit-stat="2" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalChangeAuditStatTUVAudit" data-keyboard="false">For Close-Out</button>';
            //     }

            //     if($tuv->audit_stat != 3){
            //         $result .= '<button class="dropdown-item aChangeTUVAuditStat" type="button" tuv-id="' . $tuv->tuv_audit_id . '" audit-stat="3" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalChangeAuditStatTUVAudit" data-keyboard="false">For Close</button>';
            //     }
                            
            //     $result .= '</div>
            //             </div>';

            //     return $result;
            // })
            ->addColumn('action1', function($tuv) use($user_access, $is_approver){
                $result = '<div class="btn-group">
                          <button type="button" class="btn btn-primary dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-gear"></i>
                          </button>
                          <div class="dropdown-menu dropdown-menu-right">
                            <button class="dropdown-item aViewTUVAudit" tuv-id="' . $tuv->tuv_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalViewTUVAudit" data-keyboard="false">View</button>';

                if($user_access == 4){
                    if($tuv->sending_stat != 2){
                        $result .= '<button class="dropdown-item aEditTUV" tuv-id="' . $tuv->tuv_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditTUVAudit" data-keyboard="false">Edit</button>';
                    }
                }
                else{
                    if($tuv->sending_stat != 2){
                        if(($tuv->audit_stat == 2 && $tuv->qad_approval == 1) && $is_approver == true){
                            if($tuv->audit_stat == 2 && $tuv->o_s_approval == 0 && $is_approver == true){
                                $result .= '<button class="dropdown-item aEditTUV" tuv-id="' . $tuv->tuv_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditTUVAudit" data-keyboard="false">Edit</button>';
                            }
                            else if($tuv->audit_stat == 2 && $tuv->o_s_approval == 0 && $is_approver == false){
                                $result .= '<button class="dropdown-item aEditTUV" tuv-id="' . $tuv->tuv_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditTUVAudit" data-keyboard="false">Edit</button>';
                            }
                        }
                        else if($tuv->audit_stat == 1 && $tuv->qad_approval == 1){
                            $result .= '<button class="dropdown-item aEditTUV" tuv-id="' . $tuv->tuv_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditTUVAudit" data-keyboard="false">Edit</button>';
                        }
                    }
                }
                            
                $result .= '</div>
                        </div>';

                return $result;
            })
            // ->addColumn('check_box_send', function($tuv){
            //     return '<center><input type="checkbox" class="chkSendTUVAudit" tuv-id="' . $tuv->tuv_audit_id . '"></center>';
            // })
            ->addColumn('check_box_send', function($tuv) use ($is_approver, $user_access){
                $result = "disabled";

                if($user_access == 4){
                    if($tuv->qad_approval == '0'){
                        $result = "";
                    }
                }
                else{
                    if($tuv->o_s_approval == '0'){
                        $result = "";
                    }
                }

                if($is_approver == false){
                    $result = "disabled";
                }

                return '<center><input type="checkbox" class="chkSendTUVAudit" tuv-id="' . $tuv->tuv_audit_id . '" ' . $result . '></center>';
            })
            ->addColumn('approval_stat', function($tuv){
                $result = "";

                if($tuv->audit_stat == 1 && $tuv->qad_approval == 1){
                    $result .= '<span class="tag tag-success">Approved By QAD</span>';
                }
                else if($tuv->audit_stat == 1 && $tuv->qad_approval == 0){
                    $result .= '<span class="tag tag-warning">For QAD Approval</span>';
                }
                else if($tuv->audit_stat == 2 && $tuv->o_s_approval == 1){
                    $result .= '<span class="tag tag-success">Approved By Section Head</span>';
                }
                else if($tuv->audit_stat == 2 && $tuv->o_s_approval == 0){
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
    public function view_tuv_audit_for_approval_v2(Request $request){
        session_start();

        $approver = Approver::with([
            'department' => function($query){
                $query->where('department_name', 'LIKE', '%QAD%');
            }
        ])
        ->where('approver', $_SESSION["rapidx_user_id"])
        ->where('approver_stat', '1')
        ->get();

        $QADApprover = collect($approver)->filter(function ($item) {
            return $item->department !=  null;
        });

        $tuv_audits = TUV::with([
            'tuv_departments' => function($query) use ($request){
                $query->where('tuv_department_stat', 1);
            }, 
            'tuv_departments.departments'
        ])
        ->whereYear('created_at', '>=', '2026')
        ->where('audit_stat', 2)
        ->where('tuv_audit_stat', 2)
        ->get();

        return DataTables::of($tuv_audits)
        ->addColumn('action', function($tuv_audit) use ($QADApprover){
            $result = "";
            $result .= "<button class='btn btn-sm btn-info aViewTUVAudit' style='margin-right: 2px;' tuv-id='{$tuv_audit->tuv_audit_id}' type='button' data-toggle='modal' data-target='#modalViewTUVAudit' data-keyboard='false' title='View Audit Result'><i class='icon-eye'></i></button>";
            if(count($QADApprover) > 0){
                $result .= "<button class='btn btn-sm btn-success btnApprove' style='margin-right: 2px;' data-id='{$tuv_audit->tuv_audit_id}' data-classification='tuv' title='Approve Audit Result'><i class='icon-circle-check'></i></button>";
                $result .= "<button class='btn btn-sm btn-danger btnDisapprove' data-id='{$tuv_audit->tuv_audit_id}' data-classification='tuv' title='Disapprove Audit Result'><i class='icon-times'></i></button>";
            }
           return $result;
        })
        ->addColumn('formatted_audit_date', function($tuv){
            return Carbon::parse($tuv->audit_date_from)->toFormattedDateString() . " - " . Carbon::parse($tuv->audit_date_to)->toFormattedDateString();
        })
        ->addColumn('raw_catergory', function($tuv_audit){
            $result = "";
            if($tuv_audit->audit_category == 1){
                $result = 'ISO9001 / IATF16949';
            }
            else if($tuv_audit->audit_category == 2){
                $result = 'ISO14001';
            }
            return $result;
        })
        ->addColumn('res_dept', function($tuv_audit){
            $result = "";
            foreach($tuv_audit->tuv_departments as $tuv_department){
                $result .= '<span class="tag tag-info">' . $tuv_department->departments[0]->department_name . '</span> ';
            }
            return $result;
        })
        ->rawColumns(['action', 'formatted_audit_date', 'raw_catergory', 'res_dept'])
        ->make(true);
    }

    public function view_batch_tuv_audit_by_stat(Request $request){
        session_start();
        $tuv_audit_id = [];
        if(isset($request->tuv_audit_id) || $request->tuv_audit_id != null){
            $tuv_audit_id = $request->tuv_audit_id;
        }
        $tuvs = TUV::with([
                        'tuv_departments',
                        'tuv_departments.departments'
                    ])
                    ->whereIn('tuv_audit_id', $tuv_audit_id)
                    ->get();

        return DataTables::of($tuvs)
            ->addColumn('label1', function($tuv){
                $result = "";

                if($tuv->sending_stat == 1){
                    $result .= '<span class="tag tag-warning">Pending</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function($tuv){
                $result = "";

                if($tuv->audit_stat == 1){
                    $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
                }
                else if($tuv->audit_stat == 2){
                    $result .= '<span class="tag tag-success">For Closed-Out</span>';
                }
                else{
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function($tuv){
                return Carbon::parse($tuv->audit_date_from)->toFormattedDateString() . " - " . Carbon::parse($tuv->audit_date_to)->toFormattedDateString();
            })
            ->addColumn('action1', function($tuv){
                $result = '<div class="btn-group">
                          <button type="button" class="btn btn-primary dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-gear"></i>
                          </button>
                          <div class="dropdown-menu dropdown-menu-right">
                            <button class="dropdown-item aViewTUVAudit" tuv-id="' . $tuv->tuv_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalViewTUVAudit" data-keyboard="false">View</button>';

                    $result .= '<button class="dropdown-item aRemoveTUVInBatch" tuv-id="' . $tuv->tuv_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalRemoveTUVInBatch" data-keyboard="false">Remove</button>';
                            
                $result .= '</div>
                        </div>';

                return $result;
            })
            ->rawColumns(['action1', 'label1', 'label2', 'formatted_audit_date'])
            ->make(true);
    }

    // Completely working using eager loading!
    public function view_report_tuv_audit_by_stat(Request $request){
        session_start();
        $responsible_department = $request->responsible_department;
        
        $tuvs = TUV::with(
                        ['tuv_departments' => function($query) use ($responsible_department){
                            $query->where('tuv_department_stat', 1);
                            if($responsible_department != 0) {
                                $query->whereIn('responsible_department_id', $responsible_department);
                            }
                        }, 'tuv_departments.departments', 'user_created_by', 'user_last_updated_by', 'tuv_corr_per_in_charges', 'tuv_con_per_in_charges', 'tuv_sys_per_in_charges', 'tuv_corr_per_in_charges.tuv_corr_per_in_charge_record', 'tuv_con_per_in_charges.tuv_con_per_in_charge_record', 'tuv_sys_per_in_charges.tuv_sys_per_in_charge_record'])
                    ->withCount(['tuv_departments' => function($query) use ($responsible_department){
                            $query->where('tuv_department_stat', 1);
                            if($responsible_department != 0) {
                                $query->whereIn('responsible_department_id', $responsible_department);
                            }
                        }])
                    ->having('tuv_departments_count', '>', 0);

        if($request->date_from != '0'){
            $tuvs = $tuvs->where('audit_date_from', '>=', $request->date_from);
        }

        if($request->date_to != '0'){
            $tuvs = $tuvs->where('audit_date_to', '<=', $request->date_to);
        }

        if($request->audit_category != '0' && $request->has('audit_category')) {
            $tuvs = $tuvs->where('audit_category', $request->audit_category);
        }

        if($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
            $tuvs = $tuvs->where('purpose_of_audit', $request->purpose_of_audit);
        }

        if($request->classification != '0' && $request->has('classification')) {
            $tuvs = $tuvs->where('classification', $request->classification);
        }

        if($request->audit_stat != '0' && $request->has('audit_stat')) {
            $tuvs = $tuvs->where('audit_stat', $request->audit_stat);
        }

        // if($request->evaluation_item != null && $request->has('evaluation_item')) {
        //     $tuvs = $tuvs->where('evaluation_item', $request->evaluation_item);
        // }

        $tuvs = $tuvs->get();

        return DataTables::of($tuvs)
            ->addColumn('label1', function($tuv){
                $result = "";

                if($tuv->sending_stat == 1){
                    $result .= '<span class="tag tag-warning">Pending</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function($tuv){
                $result = "";

                if($tuv->audit_stat == 1){
                    $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
                }
                else if($tuv->audit_stat == 2){
                    $result .= '<span class="tag tag-success">For Closed-Out</span>';
                }
                else{
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            ->addColumn('lbl_audit_category', function($tuv){
                $result = "";

                if($tuv->audit_category == 1){
                    $result .= 'ISO9001 / IATF16949';
                }
                else if($tuv->audit_category == 2){
                    $result .= 'ISO14001';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function($tuv){
                return Carbon::parse($tuv->audit_date_from)->toFormattedDateString() . " - " . Carbon::parse($tuv->audit_date_to)->toFormattedDateString();
            })
            ->addColumn('closed_out_audit_image', function($tuv){
                $path = "";

                if($tuv->img_corrective_action != '' || $tuv->img_corrective_action != null){
                    $path = asset('public/storage/audit_result_images/tuv/' . $tuv->img_corrective_action);
                }
                else{
                    $path = asset('public/storage/image-icon.png');
                }
                return '<center><img src="' . $path . '" style="max-width: 100px; max-height: 200px; min-width: 50px; min-height: 50px;" class="commonAuditImg"></center>';
            })
            ->rawColumns(['label1', 'label2', 'formatted_audit_date', 'closed_out_audit_image'])
            ->make(true);
    }

    public function add_tuv_audit(Request $request){
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
            'audit_category' => ['required', 'string', 'max:255'],
            'purpose_of_audit' => ['required', 'string', 'max:255'],
            'audit_date_from' => ['required', 'max:255'],
            'audit_date_to' => ['required', 'max:255'],
            'auditors' => ['required', 'string', 'max:255'],
            'classification' => ['required', 'string', 'max:255'],
            // 'deadline_for_submission' => ['required', 'max:255'],
            // 'deadline_for_submission_days' => ['numeric', 'required', 'max:255'],
            'standard_clause' => ['required', 'string', 'max:255'],
            'statement_of_nc' => ['required', 'string'],
            'responsible_department' => ['required'],
            'objective_evidence' => ['required', 'string'],
            // 'evaluation_item' => ['required', 'string'],
        ]);

        if ($validator->passes()) {
            // $audit_stat = 1;

            $extension = "";
            $pdf_extension = "";
            if(isset($request->img_corrective_action)) {
               $extension = $request->file('img_corrective_action')->getClientOriginalExtension();
            }

            if(isset($request->pdf_corrective_action)) {
               $pdf_extension = $request->file('pdf_corrective_action')->getClientOriginalExtension();
            }

            DB::beginTransaction();

            try{
                $image_name = "";
                if(isset($request->img_corrective_action)) {
                      $image_name = date('YmdHis') . Str::random(5) . '.' . $extension;
                }

                $pdf_name = "";
                if(isset($request->pdf_corrective_action)) {
                    $pdf_name = date('YmdHis') . Str::random(5) . '.' . $pdf_extension;
                }

                $tuv_audit_id = TUV::on('mysql')->insertGetId([
                    'audit_category' => $request->audit_category,
                    'purpose_of_audit' => $request->purpose_of_audit,
                    'audit_date_from' => $request->audit_date_from,
                    'audit_date_to' => $request->audit_date_to,
                    'auditors' => $request->auditors,
                    'item_no' => $request->item_no,
                    // 'evaluation_item' => $request->evaluation_item,
                    'evaluation_item_id' => $request->evaluation_item_id,
                    'classification' => $request->classification,
                    'deadline_for_submission' => $request->deadline_for_submission,
                    'deadline_for_submission_days' => $request->deadline_for_submission_days,
                    'actual_date_of_submission' => $request->actual_date_of_submission,
                    'sending_stat' => 1,
                    'email_stat' => $email_stat,
                    'in_batch' => 0,
                    'standard_clause' => $request->standard_clause,
                    'statement_of_nc' => $request->statement_of_nc,
                    'objective_evidence' => $request->objective_evidence,
                    'root_cause_analysis' => $request->root_cause_analysis,
                    'correction' => $request->correction,
                    'containment' => $request->containment,
                    'systematic' => $request->systematic,
                    'correction_commitment_date' => $request->correction_commitment_date,
                    'correction_person_in_charge' => $request->txt_correction_person_in_charge,
                    'containment_commitment_date' => $request->containment_commitment_date,
                    'containment_person_in_charge' => $request->txt_containment_person_in_charge,
                    'systematic_commitment_date' => $request->systematic_commitment_date,
                    'systematic_person_in_charge' => $request->txt_systematic_person_in_charge,
                    'corrective_action' => $request->corrective_action,
                    'img_corrective_action' => $image_name,
                    'pdf_corrective_action' => $pdf_name,
                    'qad_approval' => $request_approval,
                    'o_s_approval' => 0,
                    'qad_approved_by' => 0,
                    'o_s_approved_by' => 0,
                    'audit_stat' => $request->audit_stat,
                    'tuv_audit_stat' => 1,
                    'created_by' => $_SESSION["rapidx_user_id"],
                    'last_updated_by' => $_SESSION["rapidx_user_id"],
                    'update_version' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                for($index = 0; $index < count($request->responsible_department); $index++) {
                    TUVDepartments::insert([
                        'tuv_audit_id' => $tuv_audit_id,
                        'responsible_department_id' => $request->responsible_department[$index],
                        'tuv_department_stat' => 1,
                        'created_by' => $_SESSION['rapidx_user_id'],
                        'last_updated_by' => $_SESSION['rapidx_user_id'],
                        'update_version' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }

                if(isset($request->correction_person_in_charge)){
                    for($index = 0; $index < count($request->correction_person_in_charge); $index++) {
                        TUVCorrPersonInCharges::insert([
                            'tuv_audit_id' => $tuv_audit_id,
                            'person_in_charge_id' => $request->correction_person_in_charge[$index],
                            'created_by' => $_SESSION['rapidx_user_id'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }

                if(isset($request->containment_person_in_charge)){
                    for($index = 0; $index < count($request->containment_person_in_charge); $index++) {
                        TUVConPersonInCharges::insert([
                            'tuv_audit_id' => $tuv_audit_id,
                            'person_in_charge_id' => $request->containment_person_in_charge[$index],
                            'created_by' => $_SESSION['rapidx_user_id'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }

                if(isset($request->systematic_person_in_charge)){
                    for($index = 0; $index < count($request->systematic_person_in_charge); $index++) {
                        TUVSysPersonInCharges::insert([
                            'tuv_audit_id' => $tuv_audit_id,
                            'person_in_charge_id' => $request->systematic_person_in_charge[$index],
                            'created_by' => $_SESSION['rapidx_user_id'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }

                DB::commit();

                if(isset($request->send_email)){
                    dispatch(new SendNewTUVMailJob($tuv_audit_id));
                }

                if(isset($request->img_corrective_action)) {
                    if(!Storage::putFileAs('public/audit_result_images/tuv', $request->file('img_corrective_action'), $image_name)){
                        return response()->json(['upload_result' => 0]);
                    }
                }

                if(isset($request->pdf_corrective_action)) {
                    if(!Storage::putFileAs('public/audit_result_pdf/tuv', $request->file('pdf_corrective_action'), $pdf_name)){
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

    public function send_tuv_batch(Request $request){
        session_start();
        try{
            dispatch(new SendTUVBatchMailJob($request->tuv_audit_id));
            // dispatch(new TUVBeforeDueMailJob());
            return response()->json(['result' => 1]);
        }
        catch(\Exception $e){
            return response()->json(['result' => $e]);
        }

        // $arr_responsible_department_id = [];
        // $arr_responsible_department_name = [];

        // $tuvs = TUV::with(
        //                 ['tuv_departments' => function($query){
        //                     $query->where('tuv_department_stat', 1);
        //                 }, 'tuv_departments.departments', 'user_created_by', 'user_last_updated_by', 'tuv_corr_per_in_charges', 'tuv_con_per_in_charges', 'tuv_sys_per_in_charges', 'tuv_corr_per_in_charges.tuv_corr_per_in_charge_record', 'tuv_con_per_in_charges.tuv_con_per_in_charge_record', 'tuv_sys_per_in_charges.tuv_sys_per_in_charge_record'])
        //             ->withCount(['tuv_departments' => function($query){
        //                     $query->where('tuv_department_stat', 1);
        //                 }])
        //             ->having('tuv_departments_count', '>', 0)
        //             ->whereIn('tuv_audit_id', $request->tuv_audit_id)
        //             ->get();


        // $final_tuv_audits = [];

        // $collect_tuvs = collect($tuvs);

        // for($index = 0; $index < count($tuvs); $index++){
        //     for($index2 = 0; $index2 < count($tuvs[$index]->tuv_departments); $index2++){
        //         $arr_responsible_department_id[] = $tuvs[$index]->tuv_departments[$index2]->departments[0]->department_id;
        //         $arr_responsible_department_name[] = $tuvs[$index]->tuv_departments[$index2]->departments[0]->department_name;
        //     }
        // }

        // $email_recipients = EmailRecipient::with([
        //         'department',
        //         'receiver_info',
        //     ])
        //     ->whereIn('department_id', $arr_responsible_department_id)
        //     ->get();

        // $arrAddressTo = [];
        // $arrCC = [];

        // for($index = 0; $index < count($arr_responsible_department_id); $index++){
        //     $current_res_dept_id = $arr_responsible_department_id[$index];

        //     $collected_tuvs_per_department = $collect_tuvs->filter(function ($value, $key) use ($current_res_dept_id) {
        //                                         return collect($value['tuv_departments'])->contains('responsible_department_id', $current_res_dept_id);
        //                                     });           $final_tuv_audits = $collected_tuvs_per_department->flatten(1);

        //     $arrAddressTo = collect($email_recipients)->where('address_type', 1)->where('department_id', $current_res_dept_id)->flatten(1)->pluck('receiver_info.email');
        //     $arrCC = collect($email_recipients)->where('address_type', 2)->where('department_id', $current_res_dept_id)->flatten(1)->pluck('receiver_info.email');

        //     $newTUVBatchMail = new SendTUVBatchMail($final_tuv_audits, $arr_responsible_department_name[$index]);
        //     Mail::to($arrAddressTo)->cc($arrCC)->send($newTUVBatchMail);
        // }


        // return response()->json([
        //                         // 'tuvs' => $tuvs, 
        //                         // 'collected_tuvs_per_department' => $final_tuv_audits,
        //                         // 'final_tuv_audits_id' => $final_tuv_audits_id,
        //                         'final_tuv_audits' => $final_tuv_audits,
        //                         // 'arr_responsible_department_id' => $arr_responsible_department_id, 
        //                         // 'arr_responsible_department_name' => $arr_responsible_department_name, 
        //                         // 'email_recipients' => $email_recipients,
        //                         'arr_address_to' => $arrAddressTo->pluck('receiver_info.email'),
        //                         'arr_cc' => $arrCC->pluck('receiver_info.email'),
        //                         // 'collect_tuvs' => $collect_tuvs,
        //                         // 'collect_tuvs' => collect($tuvs)->whereInStrict('tuv_audit_id', [22, 23])->all(),
        //                     ]);
    }

    public function approve_tuv_batch(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();
        DB::beginTransaction();

        try{
            if($request->user_access == 4){
                TUV::whereIn('tuv_audit_id', $request->tuv_audit_id)
                    ->increment('update_version', 1, 
                        [
                            'qad_approval' => 1,
                            'qad_approved_by' => $_SESSION["rapidx_user_id"],
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]
                    );
            }
            else{
                TUV::whereIn('tuv_audit_id', $request->tuv_audit_id)
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

    public function edit_tuv_audit(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        $data = $request->all();

        $validator = Validator::make($data, [
            'audit_category' => ['required', 'string', 'max:255'],
            'purpose_of_audit' => ['required', 'string', 'max:255'],
            'audit_date_from' => ['required', 'max:255'],
            'audit_date_to' => ['required', 'max:255'],
            'auditors' => ['required', 'string', 'max:255'],
            'classification' => ['required', 'string', 'max:255'],
            // 'deadline_for_submission' => ['required', 'max:255'],
            // 'deadline_for_submission_days' => ['numeric', 'required', 'max:255'],
            'standard_clause' => ['required', 'string', 'max:255'],
            'statement_of_nc' => ['required', 'string'],
            'responsible_department' => ['required'],
            'objective_evidence' => ['required', 'string'],
            // 'evaluation_item' => ['required', 'string'],
            'audit_stat' => ['required'],
            'sending_stat' => ['required']
        ]);

        if ($validator->passes()) {
            $extension = "";
            if(isset($request->img_corrective_action)){
                $extension = $request->file('img_corrective_action')->getClientOriginalExtension();
            }

            $pdf_extension = "";
            if(isset($request->pdf_corrective_action)) {
               $pdf_extension = $request->file('pdf_corrective_action')->getClientOriginalExtension();
            }

            DB::beginTransaction();

            try{
                $image_name = "";
                if(isset($request->img_corrective_action)){
                    $image_name = date('YmdHis') . Str::random(5) . '.' . $extension;
                }
                else{
                    if($request->current_img_corrective_action != "" && isset($request->current_img_corrective_action)){

                         $image_name = $request->current_img_corrective_action;
                    }
                    else{
                        $image_name = "";
                    }
                }

                if(isset($request->remove_img_corrective_action)){
                    $image_name = "";
                }


                $pdf_name = "";
                if(isset($request->pdf_corrective_action)){
                    $pdf_name = date('YmdHis') . Str::random(5) . '.' . $pdf_extension;
                }
                else{
                    if($request->current_pdf_corrective_action != "" && isset($request->current_pdf_corrective_action)){

                         $pdf_name = $request->current_pdf_corrective_action;
                    }
                    else{
                        $pdf_name = "";
                    }
                }

                if(isset($request->remove_pdf_corrective_action)){
                    $pdf_name = "";
                }

                TUV::where('tuv_audit_id', $request->tuv_audit_id)
                    ->increment('update_version', 1, 
                        [
                            'audit_category' => $request->audit_category,
                            'purpose_of_audit' => $request->purpose_of_audit,
                            'audit_date_from' => $request->audit_date_from,
                            'audit_date_to' => $request->audit_date_to,
                            'auditors' => $request->auditors,
                            'item_no' => $request->item_no,
                            // 'evaluation_item' => $request->evaluation_item,
                            'evaluation_item_id' => $request->evaluation_item_id,
                            'classification' => $request->classification,
                            'actual_date_of_submission' => $request->actual_date_of_submission,
                            'deadline_for_submission' => $request->deadline_for_submission,
                            'deadline_for_submission_days' => $request->deadline_for_submission_days,
                            'standard_clause' => $request->standard_clause,
                            'statement_of_nc' => $request->statement_of_nc,
                            'objective_evidence' => $request->objective_evidence,
                            'root_cause_analysis' => $request->root_cause_analysis,
                            'correction' => $request->correction,
                            'containment' => $request->containment,
                            'systematic' => $request->systematic,
                            'correction_commitment_date' => $request->correction_commitment_date,
                            'correction_person_in_charge' => $request->txt_correction_person_in_charge,
                            'containment_commitment_date' => $request->containment_commitment_date,
                            'containment_person_in_charge' => $request->txt_containment_person_in_charge,
                            'systematic_commitment_date' => $request->systematic_commitment_date,
                            'systematic_person_in_charge' => $request->txt_systematic_person_in_charge,
                            'corrective_action' => $request->corrective_action,
                            'img_corrective_action' => $image_name,
                            'pdf_corrective_action' => $pdf_name,
                            'audit_stat' => $request->audit_stat,
                            'sending_stat' => $request->sending_stat,
                            'last_updated_by' => $_SESSION["rapidx_user_id"],
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]
                    );

                // $tuvs = TUV::on('mysql')
                //     ->with(['tuv_departments'])
                //     ->get();

                TUVDepartments::on('mysql')->where('tuv_audit_id', $request->tuv_audit_id)
                    ->whereNotIn('responsible_department_id', $request->responsible_department)
                    ->increment('update_version', 1, 
                        [
                            'tuv_department_stat' => 2,
                            'updated_at' => date('Y-m-d H:i:s'),
                            'last_updated_by' => $_SESSION['rapidx_user_id'],
                        ]
                    );

                TUVDepartments::on('mysql')->where('tuv_audit_id', $request->tuv_audit_id)
                    ->whereIn('responsible_department_id', $request->responsible_department)
                    ->increment('update_version', 1, 
                        [
                            'tuv_department_stat' => 1,
                            'updated_at' => date('Y-m-d H:i:s'),
                            'last_updated_by' => $_SESSION['rapidx_user_id'],
                        ]
                    );

                for($index = 0; $index < count($request->responsible_department); $index++) {
                    $tuv_depts = TUVDepartments::on('mysql')
                        ->where('tuv_audit_id', $request->tuv_audit_id)
                        ->where('responsible_department_id', $request->responsible_department[$index])
                        ->get();

                    if(count($tuv_depts) <= 0) {
                        TUVDepartments::insert([
                            'tuv_audit_id' => $request->tuv_audit_id,
                            'responsible_department_id' => $request->responsible_department[$index],
                            'tuv_department_stat' => 1,
                            'created_by' => $_SESSION['rapidx_user_id'],
                            'last_updated_by' => $_SESSION['rapidx_user_id'],
                            'update_version' => 1,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    }

                }

                if(isset($request->correction_person_in_charge)){
                    TUVCorrPersonInCharges::whereNotIn('person_in_charge_id', $request->correction_person_in_charge)
                        ->where('tuv_audit_id', $request->tuv_audit_id)
                        ->delete();

                    for($index = 0; $index < count($request->correction_person_in_charge); $index++) {
                        $tuv_per_in_charges = TUVCorrPersonInCharges::where('tuv_audit_id', $request->tuv_audit_id)
                            ->where('person_in_charge_id', $request->correction_person_in_charge[$index])
                            ->get();

                        if(count($tuv_per_in_charges) <= 0) {
                            TUVCorrPersonInCharges::insert([
                                'tuv_audit_id' => $request->tuv_audit_id,
                                'person_in_charge_id' => $request->correction_person_in_charge[$index],
                                'created_by' => $_SESSION['rapidx_user_id'],
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                        }

                    }
                }
                else{
                    TUVCorrPersonInCharges::where('tuv_audit_id', $request->tuv_audit_id)
                        ->delete();
                }

                if(isset($request->containment_person_in_charge)){
                    TUVConPersonInCharges::whereNotIn('person_in_charge_id', $request->containment_person_in_charge)
                        ->where('tuv_audit_id', $request->tuv_audit_id)
                        ->delete();

                    for($index = 0; $index < count($request->containment_person_in_charge); $index++) {
                        $tuv_per_in_charges = TUVConPersonInCharges::where('tuv_audit_id', $request->tuv_audit_id)
                            ->where('person_in_charge_id', $request->containment_person_in_charge[$index])
                            ->get();

                        if(count($tuv_per_in_charges) <= 0) {
                            TUVConPersonInCharges::insert([
                                'tuv_audit_id' => $request->tuv_audit_id,
                                'person_in_charge_id' => $request->containment_person_in_charge[$index],
                                'created_by' => $_SESSION['rapidx_user_id'],
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                        }

                    }
                }
                else{
                    TUVConPersonInCharges::where('tuv_audit_id', $request->tuv_audit_id)
                        ->delete();
                }

                if(isset($request->systematic_person_in_charge)){
                    TUVSysPersonInCharges::whereNotIn('person_in_charge_id', $request->systematic_person_in_charge)
                        ->where('tuv_audit_id', $request->tuv_audit_id)
                        ->delete();

                    for($index = 0; $index < count($request->systematic_person_in_charge); $index++) {
                        $tuv_per_in_charges = TUVSysPersonInCharges::where('tuv_audit_id', $request->tuv_audit_id)
                            ->where('person_in_charge_id', $request->systematic_person_in_charge[$index])
                            ->get();

                        if(count($tuv_per_in_charges) <= 0) {
                            TUVSysPersonInCharges::insert([
                                'tuv_audit_id' => $request->tuv_audit_id,
                                'person_in_charge_id' => $request->systematic_person_in_charge[$index],
                                'created_by' => $_SESSION['rapidx_user_id'],
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                        }

                    }
                }
                else{
                    TUVSysPersonInCharges::where('tuv_audit_id', $request->tuv_audit_id)
                        ->delete();
                }

                DB::commit();

                if(!isset($request->remove_img_corrective_action)){
                    if(isset($request->img_corrective_action)){
                        if(!Storage::putFileAs('public/audit_result_images/tuv', $request->file('img_corrective_action'), $image_name)){
                            return response()->json(['upload_result' => 0]);
                        }
                    }
                }

                if(!isset($request->remove_pdf_corrective_action)){
                    if(isset($request->pdf_corrective_action)){
                        if(!Storage::putFileAs('public/audit_result_pdf/tuv', $request->file('pdf_corrective_action'), $pdf_name)){
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

    public function get_tuv_audit_by_id(Request $request){
        session_start();
        $tuv_audits = TUV::with(
                        [
                            'tuv_departments' => function($query){
                            $query->where('tuv_department_stat', 1);
                            }, 
                            'tuv_departments.departments', 
                            'user_created_by', 
                            'user_last_updated_by', 
                            'evaluation_item_info', 
                            'tuv_corr_per_in_charges', 
                            'tuv_con_per_in_charges', 
                            'tuv_sys_per_in_charges', 
                            'tuv_corr_per_in_charges.tuv_corr_per_in_charge_record', 
                            'tuv_con_per_in_charges.tuv_con_per_in_charge_record', 
                            'tuv_sys_per_in_charges.tuv_sys_per_in_charge_record',
                            'tuv_attachments' => function($query){
                                $query->whereNull('deleted_at');
                            }
                        ])
                ->where('tuv_audit_id', $request->tuv_audit_id)
                ->get();

        return response()->json(['tuv_audits' => $tuv_audits]);
    }

    public function close_tuv_audit(Request $request){
        session_start();
        date_default_timezone_set('Asia/Manila');
        
        DB::beginTransaction();

        try{
            TUV::on('mysql')->where('tuv_audit_id', $request->tuv_audit_id)
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

    public function open_tuv_audit(Request $request){
        session_start();
        date_default_timezone_set('Asia/Manila');
        
        DB::beginTransaction();

        try{
            TUV::on('mysql')->where('tuv_audit_id', $request->tuv_audit_id)
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

    public function done_tuv_audit(Request $request){
        session_start();
        date_default_timezone_set('Asia/Manila');
        
        DB::beginTransaction();

        try{
            TUV::on('mysql')->where('tuv_audit_id', $request->tuv_audit_id)
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

    public function pend_tuv_audit(Request $request){
        session_start();
        date_default_timezone_set('Asia/Manila');
        
        DB::beginTransaction();

        try{
            TUV::on('mysql')->where('tuv_audit_id', $request->tuv_audit_id)
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

    // public function view_open_tuv_audit_by_stat(Request $request){
    //     session_start();
    //     // $is_approver = false;
    //     // $responsible_department[] = $_SESSION['rapidx_department_id'];
    //     // $user_id = $_SESSION['rapidx_user_id'];

    //     // $approvers = Approver::where('approver', $user_id)
    //     //                         ->where('approver_stat', 1)
    //     //                         ->get();

    //     // // return $approvers;

    //     // if(count($approvers) > 0){
    //     //     $is_approver = true;
    //     //     // $responsible_department = [];
    //     //     for($index = 0; $index < count($approvers); $index++){
    //     //         $responsible_department[] = $approvers[$index]->department_id;
    //     //     }
    //     // }

    //     // return $responsible_department;

    //     $is_approver = false;
    //     $responsible_department[] = $_SESSION['rapidx_department_id'];
    //     $user_id = $_SESSION['rapidx_user_id'];

    //     $approvers = Approver::where('approver', $_SESSION["rapidx_user_id"])
    //                 ->where('approver_stat', '1')
    //                 ->get();

    //     if(count($approvers) > 0){
    //         $is_approver = true;
    //         for($index = 0; $index < count($approvers); $index++){
    //             $responsible_department[] = $approvers[$index]->department_id;
    //         }
    //     }

    //     $tuvs = TUV::with(
    //                     ['tuv_departments' => function($query) use ($responsible_department){
    //                         $query->where('tuv_department_stat', 1);
    //                         // if(count($responsible_department) > 0) {
    //                             $query->whereIn('responsible_department_id', $responsible_department);
    //                         // }
    //                     }, 'tuv_departments.departments'])
    //                 ->withCount(['tuv_departments' => function($query) use ($responsible_department){
    //                         $query->where('tuv_department_stat', 1);
    //                         // if(count($responsible_department) > 0) {
    //                             $query->whereIn('responsible_department_id', $responsible_department);
    //                         // }
    //                     }])
    //                 ->where('qad_approval', 1)
    //                 ->where('tuv_audit_stat', 1)
    //                 ->having('tuv_departments_count', '>', 0);

    //     if($request->date_from != '0'){
    //         $tuvs = $tuvs->where('audit_date_from', '>=', $request->date_from);
    //     }

    //     if($request->date_to != '0'){
    //         $tuvs = $tuvs->where('audit_date_to', '<=', $request->date_to);
    //     }

    //     if($request->audit_category != '0' && $request->has('audit_category')) {
    //         $tuvs = $tuvs->where('audit_category', $request->audit_category);
    //     }

    //     if($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
    //         $tuvs = $tuvs->where('purpose_of_audit', $request->purpose_of_audit);
    //     }

    //     if($request->classification != '0' && $request->has('classification')) {
    //         $tuvs = $tuvs->where('classification', $request->classification);
    //     }

    //     if($request->audit_stat != '0' && $request->has('audit_stat')) {
    //         $tuvs = $tuvs->where('audit_stat', $request->audit_stat);
    //     }

    //     $tuvs = $tuvs->get();

    //     return DataTables::of($tuvs)
    //         ->addColumn('label1', function($tuv){
    //             $result = "";

    //             if($tuv->sending_stat == 1){
    //                 $result .= '<span class="tag tag-warning">Pending</span>';
    //             }
    //             else{
    //                 $result .= '<span class="tag tag-success">Done</span>';
    //             }

    //             return $result;
    //         })
    //         ->addColumn('label2', function($tuv){
    //             $result = "";

    //             if($tuv->audit_stat == 1){
    //                 $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
    //             }
    //             else if($tuv->audit_stat == 2){
    //                 $result .= '<span class="tag tag-success">For Closed-Out</span>';
    //             }
    //             else{
    //                 $result .= '<span class="tag tag-primary">Closed</span>';
    //             }

    //             return $result;
    //         })
    //         ->addColumn('formatted_audit_date', function($tuv){
    //             return Carbon::parse($tuv->audit_date_from)->toFormattedDateString() . " - " . Carbon::parse($tuv->audit_date_to)->toFormattedDateString();
    //         })
    //         ->addColumn('action1', function($tuv){
    //             $result = '<div class="btn-group">
    //                       <button type="button" class="btn btn-primary dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    //                         <i class="icon-gear"></i>
    //                       </button>
    //                       <div class="dropdown-menu dropdown-menu-right">
    //                         <button class="dropdown-item aViewTUVAudit" tuv-id="' . $tuv->tuv_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalViewTUVAudit" data-keyboard="false">View</button>';

    //             if($tuv->sending_stat != 2 && $tuv->audit_stat == 1){
    //                 $result .= '<button class="dropdown-item aEditTUV" tuv-id="' . $tuv->tuv_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditTUVAudit" data-keyboard="false">Edit</button>';
    //             }
                            
    //             $result .= '</div>
    //                     </div>';

    //             return $result;
    //         })
    //         ->addColumn('approval_stat', function($tuv){
    //             $result = "";

    //             if($tuv->audit_stat == 1 && $tuv->qad_approval == 1){
    //                 $result .= '<span class="tag tag-success">Approved By QAD</span>';
    //             }
    //             else if($tuv->audit_stat == 1 && $tuv->qad_approval == 0){
    //                 $result .= '<span class="tag tag-warning">For QAD Approval</span>';
    //             }
    //             else if($tuv->audit_stat == 2 && $tuv->o_s_approval == 1){
    //                 $result .= '<span class="tag tag-success">Approved By Section Head</span>';
    //             }
    //             else if($tuv->audit_stat == 2 && $tuv->o_s_approval == 0){
    //                 $result .= '<span class="tag tag-warning">For Section Head Approval</span>';
    //             }
    //             else{
    //                 $result .= '<span class="tag tag-success">Approved By QAD</span>';
    //             }

    //             return $result;
    //         })
    //         ->rawColumns(['action1', 'label1', 'label2', 'formatted_audit_date', 'check_box_send', 'approval_stat'])
    //         ->make(true);
    // }

    public function view_open_tuv_audit_by_stat(Request $request){
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

        $tuvs = TUV::on('mysql')
                    ->with(
                        ['tuv_departments' => function($query) use ($responsible_department){
                            $query->where('tuv_department_stat', 1);
                            // if($responsible_department != 0) {
                                $query->whereIn('responsible_department_id', $responsible_department);
                            // }
                        }, 'tuv_departments.departments'])
                    ->withCount(['tuv_departments' => function($query) use ($responsible_department){
                            $query->where('tuv_department_stat', 1);
                            // if($responsible_department != 0) {
                                $query->whereIn('responsible_department_id', $responsible_department);
                            // }
                        }])
                    ->where('qad_approval', 1)
                    ->having('tuv_departments_count', '>', 0);

        if($request->date_from != '0'){
            $tuvs = $tuvs->where('audit_date_from', '>=', $request->date_from);
        }

        if($request->date_to != '0'){
            $tuvs = $tuvs->where('audit_date_to', '<=', $request->date_to);
        }

        if($request->audit_category != '0' && $request->has('audit_category')) {
            $tuvs = $tuvs->where('audit_category', $request->audit_category);
        }

        if($request->audit_type != '0' && $request->has('audit_type')) {
            $tuvs = $tuvs->where('audit_type', $request->audit_type);
        }

        if($request->audit_type_pref_rep_ctrl_no != '0' && $request->has('audit_type_pref_rep_ctrl_no')) {
            $tuvs = $tuvs->where('audit_report_control_no', 'LIKE', $request->audit_type_pref_rep_ctrl_no . "%");
        }

        if($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
            $tuvs = $tuvs->where('purpose_of_audit', $request->purpose_of_audit);
        }

        if($request->classification != '0' && $request->has('classification')) {
            $tuvs = $tuvs->where('classification', $request->classification);
        }

        if($request->audit_stat != '0' && $request->has('audit_stat')) {
            $tuvs = $tuvs->where('audit_stat', $request->audit_stat);
        }

        if($request->audit_report_control_no != '0' && $request->has('audit_report_control_no')) {
            $tuvs = $tuvs->where('audit_report_control_no', $request->audit_report_control_no);
        }

        if($request->item_no != '0' && $request->has('item_no')) {
            $tuvs = $tuvs->where('item_no', $request->item_no);
        }

        if($request->nc_control_no != '0' && $request->has('nc_control_no')) {
            $tuvs = $tuvs->where('nc_control_no', $request->nc_control_no);
        }

        if($request->evaluation_item != null && $request->has('evaluation_item')) {
            $tuvs = $tuvs->where('evaluation_item_id', $request->evaluation_item);
        }

        if($request->approval_stat != '0' && $request->has('approval_stat')) {
            if($request->approval_stat == 1){
                $tuvs = $tuvs->where(function($query){
                    $query->where('audit_stat', 2);
                    $query->where('o_s_approval', 0);
                });
            }
        }

        // if($request->evaluation_item != null && $request->has('evaluation_item')) {
        //     $tuvs = $tuvs->where('category_of_findings', $request->evaluation_item);
        // }

        $tuvs = $tuvs->where('tuv_audit_stat', 1);

        $tuvs = $tuvs->get();

        return DataTables::of($tuvs)
            ->addColumn('label1', function($tuv){
                $result = "";

                if($tuv->sending_stat == 1){
                    $result .= '<span class="tag tag-warning">Pending</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function($tuv){
                $result = "";

                if($tuv->audit_stat == 1){
                    $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
                }
                else if($tuv->audit_stat == 2){
                    $result .= '<span class="tag tag-success">For Closed-Out</span>';
                }
                else{
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function($tuv){
                $result = '';
                if($tuv->audit_date_from != $tuv->audit_date_to){
                    $result = Carbon::parse($tuv->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($tuv->audit_date_to)->toFormattedDateString();
                }
                else{
                    $result = Carbon::parse($tuv->audit_date_from)->toFormattedDateString();
                }
                return $result;
            })
            ->addColumn('action1', function($tuv) use($is_approver, $user_access){
                $final = '';
                $final = '<button class="btn-actions btn btn-sm btn-info aViewTUVAudit" tuv-id="' . $tuv->tuv_audit_id . '" type="button" data-toggle="modal" data-target="#modalViewTUVAudit" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="View" title="View"><i class="icon-file-text-o"></i></button> ';

                if($tuv->audit_stat == 1){
                    $final .= '<button class="btn-actions btn btn-sm btn-success aEditTUV" tuv-id="' . $tuv->tuv_audit_id . '" type="button" data-toggle="modal" data-target="#modalEditTUVAudit" data-keyboard="false" title="Edit"><i class="icon-edit2"></i></button> ';
                }
                else{
                    if($is_approver){
                        if($tuv->audit_stat == 2 && $tuv->o_s_approval == 0) {
                            $final .= '<button class="btn-actions btn btn-sm btn-success aEditTUV" tuv-id="' . $tuv->tuv_audit_id . '" type="button" data-toggle="modal" data-target="#modalEditTUVAudit" data-keyboard="false" title="Edit"><i class="icon-edit2"></i></button> ';
                        }
                    }
                }

                // Working, uncomment if will use
                if($is_approver && $user_access == 5){
                    if($tuv->o_s_approval == '0' && $tuv->audit_stat == 2){

                        $final .= '<button class="btn-actions btn btn-sm btn-primary aOSApprovalTUVAudit" type="button" tuv-id="' . $tuv->tuv_audit_id . '" o-s-approval="1" data-toggle="modal" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="Approve" title="Approve"><i class="icon-like"></i></button> '; 
                    }
                }
                return $final;
            })
            ->addColumn('check_box_send', function($tuv){
                $result = "";

                if($tuv->qad_approval == '0'){
                    $result = "disabled";
                }

                if($tuv->deadline_of_submission == null){
                    $result = "disabled";
                }

                return '<center><input type="checkbox" class="chkSendTUVAudit" tuv-id="' . $tuv->tuv_audit_id . '" ' . $result . '></center>';
            })
            ->addColumn('approval_stat', function($tuv){
                $result = "";

                if($tuv->audit_stat == 1 && $tuv->qad_approval == 1){
                    $result .= '<span class="tag tag-success">Approved By QAD</span>';
                }
                else if($tuv->audit_stat == 1 && $tuv->qad_approval == 0){
                    $result .= '<span class="tag tag-warning">For QAD Approval</span>';
                }
                else if($tuv->audit_stat == 2 && $tuv->o_s_approval == 1){
                    $result .= '<span class="tag tag-success">Approved By Section Head</span>';
                }
                else if($tuv->audit_stat == 2 && $tuv->o_s_approval == 0){
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

    public function edit_open_tuv_audit(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        $data = $request->all();

        $validator = Validator::make($data, [
            // 'root_cause_analysis' => ['required', 'string'],
            // 'correction' => ['required', 'string'],
            // 'containment' => ['required', 'string'],
            // 'systematic' => ['required', 'string'],
            // 'correction_person_in_charge' => ['required', 'numeric'],
            // 'containment_person_in_charge' => ['required', 'string'],
            // 'systematic_person_in_charge' => ['required', 'string'],
            // 'correction_commitment_date' => ['required', 'date'],
            // 'containment_commitment_date' => ['required', 'date'],
            // 'systematic_commitment_date' => ['required', 'date'],
        ]);

        if($validator->passes()){
            try{
                if(isset($request->audit_stat)){
                    TUV::where('tuv_audit_id', $request->tuv_audit_id)
                        ->increment('update_version', 1, 
                            [
                                'root_cause_analysis' => $request->root_cause_analysis,
                                'correction' => $request->correction,
                                'containment' => $request->containment,
                                'systematic' => $request->systematic,
                                'audit_stat' => $request->audit_stat,
                                'correction_commitment_date' => $request->correction_commitment_date,
                                'containment_commitment_date' => $request->containment_commitment_date,
                                'systematic_commitment_date' => $request->systematic_commitment_date,
                                'audit_stat' => $request->audit_stat,
                                'last_updated_by' => $_SESSION["rapidx_user_id"],
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]
                        );
                }
                else{
                    TUV::where('tuv_audit_id', $request->tuv_audit_id)
                        ->increment('update_version', 1, 
                            [
                                'root_cause_analysis' => $request->root_cause_analysis,
                                'correction' => $request->correction,
                                'containment' => $request->containment,
                                'systematic' => $request->systematic,
                                'audit_stat' => $request->audit_stat,
                                'correction_commitment_date' => $request->correction_commitment_date,
                                'containment_commitment_date' => $request->containment_commitment_date,
                                'systematic_commitment_date' => $request->systematic_commitment_date,
                                'last_updated_by' => $_SESSION["rapidx_user_id"],
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]
                        );
                }

                if(isset($request->correction_person_in_charge)){
                    TUVCorrPersonInCharges::whereNotIn('person_in_charge_id', $request->correction_person_in_charge)
                        ->where('tuv_audit_id', $request->tuv_audit_id)
                        ->delete();

                    for($index = 0; $index < count($request->correction_person_in_charge); $index++) {
                        $tuv_per_in_charges = TUVCorrPersonInCharges::where('tuv_audit_id', $request->tuv_audit_id)
                            ->where('person_in_charge_id', $request->correction_person_in_charge[$index])
                            ->get();

                        if(count($tuv_per_in_charges) <= 0) {
                            TUVCorrPersonInCharges::insert([
                                'tuv_audit_id' => $request->tuv_audit_id,
                                'person_in_charge_id' => $request->correction_person_in_charge[$index],
                                'created_by' => $_SESSION['rapidx_user_id'],
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                        }

                    }
                }
                else{
                    TUVCorrPersonInCharges::where('tuv_audit_id', $request->tuv_audit_id)
                        ->delete();
                }

                if(isset($request->containment_person_in_charge)){
                    TUVConPersonInCharges::whereNotIn('person_in_charge_id', $request->containment_person_in_charge)
                        ->where('tuv_audit_id', $request->tuv_audit_id)
                        ->delete();

                    for($index = 0; $index < count($request->containment_person_in_charge); $index++) {
                        $tuv_per_in_charges = TUVConPersonInCharges::where('tuv_audit_id', $request->tuv_audit_id)
                            ->where('person_in_charge_id', $request->containment_person_in_charge[$index])
                            ->get();

                        if(count($tuv_per_in_charges) <= 0) {
                            TUVConPersonInCharges::insert([
                                'tuv_audit_id' => $request->tuv_audit_id,
                                'person_in_charge_id' => $request->containment_person_in_charge[$index],
                                'created_by' => $_SESSION['rapidx_user_id'],
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                        }

                    }
                }
                else{
                    TUVConPersonInCharges::where('tuv_audit_id', $request->tuv_audit_id)
                        ->delete();
                }

                if(isset($request->systematic_person_in_charge)){
                    TUVSysPersonInCharges::whereNotIn('person_in_charge_id', $request->systematic_person_in_charge)
                        ->where('tuv_audit_id', $request->tuv_audit_id)
                        ->delete();

                    for($index = 0; $index < count($request->systematic_person_in_charge); $index++) {
                        $tuv_per_in_charges = TUVSysPersonInCharges::where('tuv_audit_id', $request->tuv_audit_id)
                            ->where('person_in_charge_id', $request->systematic_person_in_charge[$index])
                            ->get();

                        if(count($tuv_per_in_charges) <= 0) {
                            TUVSysPersonInCharges::insert([
                                'tuv_audit_id' => $request->tuv_audit_id,
                                'person_in_charge_id' => $request->systematic_person_in_charge[$index],
                                'created_by' => $_SESSION['rapidx_user_id'],
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                        }

                    }
                }
                else{
                    TUVSysPersonInCharges::where('tuv_audit_id', $request->tuv_audit_id)
                        ->delete();
                }

                DB::commit();
                return response()->json(['result' => "1"]);
            }
            catch(\Exception $e) {
                DB::rollback();
                return response()->json(['result' => "0"]);
            }  
        }
        else{
            return response()->json(['result' => '0', 'error' => $validator->messages()]);
        }
    }

    public function get_chart_tuv_audit_by_stat(Request $request){
        session_start();
        date_default_timezone_set('Asia/Manila');

        $year = $request->year;
        $month_from = '04';
        $month_to = '03';
      
        $date_from = date($year . '-' . $month_from . '-' . '01');
        $date_to = date(($year + 1) . '-' . $month_to . '-' . 't');
      
        $date_create_from = date_create($date_from);
        $date_create_to = date_create($date_to);

        $tuvs = TUV::with(
                        ['tuv_departments' => function($query){
                            $query->where('tuv_department_stat', 1);
                        }, 'tuv_departments.departments'])
                    ->withCount(['tuv_departments' => function($query){
                            $query->where('tuv_department_stat', 1);
                        }])
                    ->having('tuv_departments_count', '>', 0)
                    ->where('audit_date_from', '>=', $date_create_from)
                    ->where('audit_date_to', '<=', $date_create_to)
                    ->get();

        return response()->json(['tuvs' => $tuvs]);
    }

    public function view_tuv_audit_history(Request $request){
        session_start();
        $tuvs = TUV::on('mysql')->where('responsible_department', $_SESSION["rapidx_department_id"])
                    ->where('audit_stat', 2)
                    ->where('sending_stat', 2)
                    ->get();

        return DataTables::of($tuvs)
            ->addColumn('label1', function($tuv){
                $result = "";

                if($tuv->sending_stat == 1){
                    $result .= '<span class="tag tag-warning">Pending</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function($tuv){
                $result = "";

                if($tuv->audit_stat == 1){
                    $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Closed</span>';
                }

                return $result;
            })
            ->addColumn('action1', function($tuv){
                // $result = '<div class="btn-group">
                //           <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                //             Action
                //           </button>
                //           <div class="dropdown-menu dropdown-menu-right">
                //             <button class="dropdown-item aViewTUVAudit" tuv-id="' . $tuv->tuv_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalViewTUVAudit" data-keyboard="false">View</button>';                            
                // $result .= '</div>
                //         </div>';

                return '<button class="btn btn-primary aViewTUVAudit" tuv-id="' . $tuv->tuv_audit_id . '" type="button" data-toggle="modal" data-target="#modalViewTUVAudit" data-keyboard="false">View</button>';
            })
            ->rawColumns(['action1', 'label1', 'label2'])
            ->make(true);
    }

    public function view_single_tuv_audit(Request $request){
        session_start();
        $responsible_department = $request->responsible_department;        
        $tuvs = TUV::where('in_batch', 0)
                    ->with(
                        ['tuv_departments' => function($query) use ($responsible_department){
                            $query->where('tuv_department_stat', 1);
                            if($responsible_department != 0) {
                                $query->where('responsible_department_id', $responsible_department);
                            }
                        }, 'tuv_departments.departments'])
                    ->withCount(['tuv_departments' => function($query) use ($responsible_department){
                            $query->where('tuv_department_stat', 1);
                            if($responsible_department != 0) {
                                $query->where('responsible_department_id', $responsible_department);
                            }
                        }])
                    ->having('tuv_departments_count', '>', 0);

        if($request->date_from != '0' && $request->date_to != '0') {
            $tuvs = $tuvs->whereBetween('audit_date', [$request->date_from, $request->date_to]);
        }

        if($request->audit_category != '0' && $request->has('audit_category')) {
            $tuvs = $tuvs->where('audit_category', $request->audit_category);
        }

        if($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
            $tuvs = $tuvs->where('purpose_of_audit', $request->purpose_of_audit);
        }

        if($request->classification != '0' && $request->has('classification')) {
            $tuvs = $tuvs->where('classification', $request->classification);
        }

        if($request->audit_stat != '0' && $request->has('audit_stat')) {
            $tuvs = $tuvs->where('audit_stat', $request->audit_stat);
        }

        $tuvs = $tuvs->get();

        return DataTables::of($tuvs)
            ->addColumn('label1', function($tuv){
                $result = "";

                if($tuv->sending_stat == 1){
                    $result .= '<span class="tag tag-warning">Pending</span>';
                }
                else{
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function($tuv){
                $result = "";

                if($tuv->audit_stat == 1){
                    $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
                }
                else if($tuv->audit_stat == 2){
                    $result .= '<span class="tag tag-success">For Closed-Out</span>';
                }
                else{
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function($tuv){
                return Carbon::parse($tuv->audit_date)->toFormattedDateString();
            })
            ->addColumn('action1', function($tuv){
                $result = '<div class="btn-group">
                          <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-cog"></i>
                          </button>
                          <div class="dropdown-menu dropdown-menu-right">
                            <button class="dropdown-item aViewTUVAudit" tuv-id="' . $tuv->tuv_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalViewTUVAudit" data-keyboard="false">View</button>';
                
                if(($tuv->sending_stat == 1 || $tuv->audit_stat == 1 || $tuv->audit_stat == 2) && $tuv->in_batch == 0) {
                    $result .= '<button class="dropdown-item aAddToBatchTUVAudit" type="button" tuv-id="' . $tuv->tuv_audit_id . '" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalAddToBatchTUVAudit" data-keyboard="false">Add to Batch</button>';
                }

                            
                $result .= '</div>
                        </div>';

                return $result;
            })
            ->rawColumns(['action1', 'label1', 'label2', 'formatted_audit_date'])
            ->make(true);
    }

    // For testing
    public function auto_email(Request $request){
        session_start();
        date_default_timezone_set('Asia/Manila');
        $today = Carbon::now();

        $tuv_for_imp_plan_over_and_due = TUV::with(
                        ['tuv_departments' => function($query){
                            $query->where('tuv_department_stat', 1);
                        }, 'tuv_departments.departments', 'user_created_by', 'user_last_updated_by', 'tuv_corr_per_in_charge', 'tuv_con_per_in_charge', 'tuv_sys_per_in_charge'])
                ->where('sending_stat', 1)
                ->where('audit_stat', 1)
                ->whereDate('deadline_for_submission', '>=', $today->toDateString())
                ->get();

        $tuv_for_imp_plan_before_due = TUV::with(
                        ['tuv_departments' => function($query){
                            $query->where('tuv_department_stat', 1);
                        }, 'tuv_departments.departments', 'user_created_by', 'user_last_updated_by', 'tuv_corr_per_in_charge', 'tuv_con_per_in_charge', 'tuv_sys_per_in_charge'])
                ->where('sending_stat', 1)
                ->where('audit_stat', 1)
                ->whereDate('deadline_for_submission', '=', $today->addDays(1)->toDateString())
                ->get();

        // $tuv_for_imp_plan_before_due_id = $tuv_for_imp_plan_before_due->map(function ($tuv_for_imp_plan_before_due_id) {
        //         return collect($tuv_for_imp_plan_before_due_id->toArray())
        //             ->only(['tuv_audit_id'])
        //             ->all();
        // });

        return response()->json(['tuv_for_imp_plan_over_and_due' => $tuv_for_imp_plan_over_and_due, 'tuv_for_imp_plan_before_due' => $tuv_for_imp_plan_before_due]);
    }

    public function change_tuv_audit_stat(Request $request) {
        date_default_timezone_set('Asia/Manila');
        session_start();
        DB::beginTransaction();

        try{
            TUV::where('tuv_audit_id', $request->tuv_audit_id)
                ->increment('update_version', 1, 
                    [
                        'tuv_audit_stat' => $request->tuv_audit_stat,
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
    public function get_tuv_chart_by_audit_category(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        $from = $request->from;
        $to = $request->to;
        // $month_from = '01';
        // $month_to = '12';
      
        // $date_from = $from . '-' . $month_from . '-' . '01';
        // $date_to = $to . '-' . $month_to . '-' . '31';

        $date_from = $from . '-' . '01';
        $date_to = $to . '-' . '31';

        $date_create_from = Carbon::parse($date_from)->format('Y-m-d');
        $date_create_to = Carbon::parse($date_to)->format('Y-m-d');

        // return $date_create_to;

        // $from = $request->from;
        // $to = $request->to;
      
        // $date_from = $from . '-01-01';
        // $date_to = $to. '-12-31';

        // $date_create_from = Carbon::parse($date_from)->format('Y-m-d');
        // $date_create_to = Carbon::parse($date_to)->format('Y-m-d');

        $tuv_audits = TUV::whereDate('audit_date_from', '>=', $date_create_from)
                    ->whereDate('audit_date_from', '<=', $date_create_to)
                    ->where('audit_category', $request->audit_category)
                    ->where('tuv_audit_stat', 1)
                ->get();

        return response()->json(['tuv_audits' => $tuv_audits, 'date_from' => $date_create_from, 'date_to' => $date_create_to]);
    }

    public function get_tuv_chart_by_classification2(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        $from = $request->from;
        $to = $request->to;

        $date_from = $from . '-' . '01';
        $date_to = $to . '-' . '31';

        $date_create_from = Carbon::parse($date_from)->format('Y-m-d');
        $date_create_to = Carbon::parse($date_to)->format('Y-m-d');

        // $tuv_audits = TUV::whereDate('audit_date_from', '>=', $date_create_from)
        //             ->whereDate('audit_date_from', '<=', $date_create_to)
        //             ->where('tuv_audit_stat', 1)
        //             ->get();

        $tuv_audits = TUVDepartments::with([
                        'department_info',
                        'tuv_audit_info' => function($query) use ($date_create_from, $date_create_to, $request){
                            $query->whereDate('audit_date_from', '>=', $date_create_from);
                            $query->whereDate('audit_date_from', '<=', $date_create_to);
                            $query->where('tuv_audit_stat', 1);
                            $query->where('audit_category', $request->audit_category);
                        },
                    ])
                    ->get();

        $total_count = 0;

        $tuv_audit_nc = collect($tuv_audits)->where('tuv_audit_info', '!=', null)->where('tuv_audit_info.classification', 'NC')->flatten(1);
        $tuv_audit_ofi = collect($tuv_audits)->where('tuv_audit_info', '!=', null)->where('tuv_audit_info.classification', 'OFI')->flatten(1);

        $total_count = $tuv_audit_nc->count() + $tuv_audit_ofi->count();
        $nc_total_count = $tuv_audit_nc->count();
        $ofi_total_count = $tuv_audit_ofi->count();

        $nc_departments = $tuv_audit_nc->groupBy('responsible_department_id')->map(function ($row) {
            return $row->count('responsible_department_id');
        });

        $nc_final_data = [];

        foreach ($nc_departments as $key => $value) {
            $department_name = collect($tuv_audit_nc->where('responsible_department_id', $key)->flatten(1)->first()->department_info)->flatten(2);
            $nc_final_data[] = [
                'department_name' => $department_name[1],
                'responsible_department_id' => $key,
                'count' => $value,
            ];
        }

        $ofi_departments = $tuv_audit_ofi->groupBy('responsible_department_id')->map(function ($row) {
            return $row->count('responsible_department_id');
        });

        $ofi_final_data = [];

        foreach ($ofi_departments as $key => $value) {
            $department_name = collect($tuv_audit_ofi->where('responsible_department_id', $key)->flatten(1)->first()->department_info)->flatten(2);
            $ofi_final_data[] = [
                'department_name' => $department_name[1],
                'responsible_department_id' => $key,
                'count' => $value,
            ];
        }

        return response()->json(['tuv_audit_nc' => $tuv_audit_nc, 'tuv_audit_ofi' => $tuv_audit_ofi, 'date_from' => $date_create_from, 'date_to' => $date_create_to, 'total_count' => $total_count, 'nc_total_count' => $nc_total_count, 'ofi_total_count' => $ofi_total_count, 'nc_final_data' => $nc_final_data, 'ofi_final_data' => $ofi_final_data]);
    }

    public function get_tuv_chart_by_classification1(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        // $from = $request->from;
        // $to = $request->to;
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

        $tuv_audits = TUV::whereDate('audit_date_from', '>=', $date_create_from)
                    ->whereDate('audit_date_from', '<=', $date_create_to)
                    ->where('tuv_audit_stat', 1)
                    ->get();

        $tuv_audit_nc = collect($tuv_audits)->where('classification', 'NC')->flatten(1);
        $tuv_audit_ofi = collect($tuv_audits)->where('classification', 'OFI')->flatten(1);

        return response()->json(['tuv_audits' => $tuv_audits, 'tuv_audit_nc' => $tuv_audit_nc, 'tuv_audit_ofi' => $tuv_audit_ofi, 'date_from' => $date_create_from, 'date_to' => $date_create_to]);
    }

    public function get_tuv_chart_by_classification(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        $from = $request->from;
        $to = $request->to;
        $month_from = '01';
        $month_to = '12';
      
        $date_from = $from . '-' . $month_from . '-' . '01';
        $date_to = $to . '-' . $month_to . '-' . '31';

        $date_create_from = Carbon::parse($date_from)->format('Y-m-d');
        $date_create_to = Carbon::parse($date_to)->format('Y-m-d');

        $tuv_audits = TUV::where('tuv_audit_stat', 1)
                            ->where('audit_category', $request->audit_category)
                            ->whereDate('audit_date_from', '>=', $date_create_from)
                            ->whereDate('audit_date_from', '<=', $date_create_to)
                            ->get();

        $nc_tuv_audit_id = collect($tuv_audits)->where('classification', "NC")->pluck('tuv_audit_id')->flatten(1);

        $ofi_tuv_audit_id = collect($tuv_audits)->where('classification', "OFI")->pluck('tuv_audit_id')->flatten(1);

        $nc_tuv_departments = TUVDepartments::with([
                                'department_info',
                                // 'tuv_audit_info' => function($query) use ($request){
                                //     $query->where('tuv_audit_stat', 1);
                                //     $query->where('classification', 'NC');
                                //     $query->where('audit_category', $request->audit_category);
                                // },
                            ])
                            ->whereIn('tuv_audit_id', $nc_tuv_audit_id)
                            ->groupBy('responsible_department_id')
                            ->select('responsible_department_id',  DB::raw('count(*) as total_count'))
                            ->orderBy('total_count', 'desc')
                            ->get();

        $ofi_tuv_departments = TUVDepartments::with([
                                'department_info',
                                // 'tuv_audit_info' => function($query) use ($request){
                                //     $query->where('tuv_audit_stat', 1);
                                //     $query->where('classification', 'OFI');
                                //     $query->where('audit_category', $request->audit_category);
                                // },
                            ])
                            ->whereIn('tuv_audit_id', $ofi_tuv_audit_id)
                            ->groupBy('responsible_department_id')
                            ->select('responsible_department_id',  DB::raw('count(*) as total_count'))
                            ->orderBy('total_count', 'desc')
                            ->get();

        // $nc_tuv_departments = collect($tuv_departments)->where('tuv_audit_info.tuv_audit_stat', 1)
        //                         ->where('tuv_audit_info.classification', 'NC')
        //                         ->where('tuv_audit_info.audit_category', $request->audit_category)
        //                         ->flatten(1);

        // $ofi_tuv_departments = collect($tuv_departments)->where('tuv_audit_info.tuv_audit_stat', 1)
        //                         ->where('tuv_audit_info.classification', 'OFI')
        //                         ->where('tuv_audit_info.audit_category', $request->audit_category)
        //                         ->flatten(1);

        return response()->json(['nc_tuv_departments' => $nc_tuv_departments, 'ofi_tuv_departments' => $ofi_tuv_departments]);
    }

    // public function get_tuv_chart_by_areas_for_improvement(Request $request){
    //     date_default_timezone_set('Asia/Manila');
    //     session_start();

    //     $from = $request->from;
    //     $to = $request->to;
    //     $month_from = '01';
    //     $month_to = '12';
      
    //     $date_from = $from . '-' . $month_from . '-' . '01';
    //     $date_to = $to . '-' . $month_to . '-' . '31';

    //     $date_create_from = Carbon::parse($date_from)->format('Y-m-d');
    //     $date_create_to = Carbon::parse($date_to)->format('Y-m-d');

    //     $tuv_audits = TUV::where('tuv_audit_stat', 1)
    //                         ->where('audit_category', $request->audit_category)
    //                         ->whereDate('audit_date_from', '>=', $date_create_from)
    //                         ->whereDate('audit_date_from', '<=', $date_create_to)
    //                         ->groupBy('evaluation_item')
    //                         ->select('evaluation_item',  DB::raw('count(*) as total_count'))
    //                         ->orderBy('total_count', 'desc')
    //                         ->get();

    //     return response()->json(['tuv_audits' => $tuv_audits]);
    // }

    public function get_tuv_chart_by_areas_for_improvement(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        $from = $request->from;
        $to = $request->to;

        $date_from = $from . '-' . '01';
        $date_to = $to . '-' . '31';

        $date_create_from = Carbon::parse($date_from)->format('Y-m-d');
        $date_create_to = Carbon::parse($date_to)->format('Y-m-d');

        $tuv_audits = TUV::where('tuv_audit_stat', 1)
                            ->where('audit_category', $request->audit_category)
                            ->whereDate('audit_date_from', '>=', $date_create_from)
                            ->whereDate('audit_date_from', '<=', $date_create_to)
                            ->groupBy('evaluation_item')
                            ->select('evaluation_item',  DB::raw('count(*) as total_count'))
                            ->orderBy('total_count', 'desc')
                            ->get();

        return response()->json(['tuv_audits' => $tuv_audits]);
    }

    public function get_tuv_unique_evaluation_items(Request $request){
        session_start();
        $tuv_audits = TUV::select('evaluation_item')->distinct()->get();

        return response()->json(['tuv_audits' => $tuv_audits]);
    }

    public function dt_get_tuv_audit_attachments(Request $request){
        // $tuv_attachments = TuvAuditAttachment::
    }

    public function add_tuv_audit_v2(TUVAuditRequest $request){
        date_default_timezone_set('Asia/Manila');
        session_start();
        $data = $request->validated();

        DB::beginTransaction();
        try{
            if(isset($request->hidden_tuv_audit_id)){ // Edit
                $extension = "";
                $pdf_extension = "";
                $image_name = "";
                $pdf_name = "";

                if(isset($request->img_corrective_action)) {
                    $extension = $request->file('img_corrective_action')->getClientOriginalExtension();
                    $image_name = date('YmdHis') . Str::random(5) . '.' . $extension;
                    if(!Storage::putFileAs('public/audit_result_images/tuv', $request->file('img_corrective_action'), $image_name)){
                        return response()->json(['upload_result' => 0]);
                    }
                }

                if(isset($request->pdf_corrective_action)) {
                    $pdf_extension = $request->file('pdf_corrective_action')->getClientOriginalExtension();
                    $pdf_name = date('YmdHis') . Str::random(5) . '.' . $pdf_extension;
                    if(!Storage::putFileAs('public/audit_result_pdf/tuv', $request->file('pdf_corrective_action'), $pdf_name)){
                        return response()->json(['upload_result' => 0]);
                    }
                }

                TUV::on('mysql')->where('tuv_audit_id', $request->hidden_tuv_audit_id)
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
                        $attachment_id = TuvAuditAttachment::on('mysql')->insertGetId([
                            'tuv_audit_id' => $request->hidden_tuv_audit_id,
                            'filename' => $original_filename,
                            'path' => 'audit_result_attachments/tuv/',
                            'remarks' => $data['remarks'],
                            // 'uploaded_by' => $_SESSION['rapidx_user_id'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        // Path where file will be saved
                        Storage::putFileAs('public/audit_result_attachments/tuv', $data['file'], "$attachment_id.$ext");
                    }
                }
            }
            else{ // Create
                // $tuv_audit_id = TUV::on('mysql')->insertGetId([
                //     'audit_category' => $request->audit_category,
                //     'ctrl_no' => "QAD-TUV-",
                //     'purpose_of_audit' => $request->purpose_of_audit,
                //     'audit_date_from' => $request->audit_date_from,
                //     'audit_date_to' => $request->audit_date_to,
                //     'auditors' => $request->auditors,
                //     // 'item_no' => $request->item_no,
                //     // 'evaluation_item_id' => $request->evaluation_item_id,
                //     'classification' => $request->classification,
                //     'deadline_for_submission' => $request->deadline_for_submission,
                //     // 'deadline_for_submission_days' => $request->deadline_for_submission_days,
                //     'actual_date_of_submission' => $request->actual_date_of_submission,
                //     // 'standard_clause' => $request->standard_clause,
                //     'o_s_approval' => 0,
                //     'qad_approved_by' => 0,
                //     'o_s_approved_by' => 0,
                //     'audit_stat' => 1,
                //     'tuv_audit_stat' => 1,
                //     'created_by' => $_SESSION["rapidx_user_id"],
                //     'last_updated_by' => $_SESSION["rapidx_user_id"],
                //     'update_version' => 1,
                //     'created_at' => date('Y-m-d H:i:s'),
                //     'updated_at' => date('Y-m-d H:i:s'),
                // ]);
                $data = [
                    'audit_category' => $request->audit_category,
                    'ctrl_no' => $request->audit_control_number,
                    'purpose_of_audit' => $request->purpose_of_audit,
                    'audit_date_from' => $request->audit_date_from,
                    'audit_date_to' => $request->audit_date_to,
                    'auditors' => $request->auditors,
                    // 'item_no' => $request->item_no,
                    // 'evaluation_item_id' => $request->evaluation_item_id,
                    'classification' => $request->classification,
                    'deadline_for_submission' => $request->deadline_for_submission,
                    // 'deadline_for_submission_days' => $request->deadline_for_submission_days,
                    'actual_date_of_submission' => $request->actual_date_of_submission,
                    // 'standard_clause' => $request->standard_clause,
                    'o_s_approval' => 0,
                    'qad_approved_by' => 0,
                    'o_s_approved_by' => 0,
                    'audit_stat' => 1,
                    'tuv_audit_stat' => 1,
                    'created_by' => $_SESSION["rapidx_user_id"],
                    'last_updated_by' => $_SESSION["rapidx_user_id"],
                    'update_version' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'audit_title' => $request->audit_title,
                ];

                // Only add these fields if classification is CPAR
                // if ($request->classification === 'CPAR') {
                    $data['number_OFI'] = $request->number_OFI;
                    $data['number_major_nc'] = $request->number_major_nc;
                    $data['number_minor_nc'] = $request->number_minor_nc;
                    $data['number_possitive_feedback'] = $request->number_possitive_feedback;
                // }

                $tuv_audit_id = TUV::on('mysql')->insertGetId($data);

                for($index = 0; $index < count($request->responsible_department); $index++) {
                    TUVDepartments::insert([
                        'tuv_audit_id' => $tuv_audit_id,
                        'responsible_department_id' => $request->responsible_department[$index],
                        'tuv_department_stat' => 1,
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
                        $attachment_id = TuvAuditAttachment::on('mysql')->insertGetId([
                            'tuv_audit_id' => $tuv_audit_id,
                            'filename' => $original_filename,
                            'path' => 'audit_result_attachments/tuv/',
                            'remarks' => $data['remarks'],
                            // 'uploaded_by' => $_SESSION['rapidx_user_id'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        // Path where file will be saved
                        Storage::putFileAs('public/audit_result_attachments/tuv/', $data['file'], "$attachment_id.$ext");
                    }
                }
                // Mail::to($arrAddressTo)->cc($arrCC)->send($audit_result_email);

                dispatch(new SendAuditResultEmailJob($tuv_audit_id, 'tuv'));

            }
            

            DB::commit();
            return response()->json(['result' => true]);
        }catch(\Throwable $e){
            DB::rollback();
            return $e->getMessage();
        }
    }

    // public function dt_get_attachments(Request $request){
    //     $model = TuvAuditAttachment::class;
    //     $foreignKey = 'tuv_audit_id';
    //     if ($request->type === 'customer') {
    //         $model = CustomerAuditAttachment::class;
    //         $foreignKey = 'customer_audit_id';
    //     }
    //     else if($request->type == 'internal'){
    //         $model = InternalAuditAttachment::class;
    //         $foreignKey = 'internal_audit_id';
    //     }
    //     else if($request->type == 'supplier'){
    //         $model = SupplierAuditAttachment::class;
    //         $foreignKey = 'supplier_audit_id';
    //     }


    //     $tuv_attachments = $model::where($foreignKey, $request->auditId)
    //                         ->get();
    //     return DataTables::of($tuv_attachments)
    //     ->addColumn('attachment_title', function($data){
    //         return $result = '<a href="' . asset('public/storage/' . $data->path .$data->id . '.' . pathinfo($data->filename, PATHINFO_EXTENSION)) . '" target="_blank">' . $data->filename . '</a>';
    //     })
    //     ->addColumn('attachment_remarks', function($data){
    //         return $data->remarks;
    //     })
    //     ->addColumn('action', function($data) use ($request){
    //         return '<button class="btn btn-danger btn-sm aDeleteAttachment" type="button" attachment-id="' . $data->id . '" data-type="'.$request->type.'" ><i class="icon-trash"></i></button>';
    //     })
    //     ->rawColumns(['attachment_title', 'attachment_remarks', 'action'])
    //     ->make(true);
    // }
}
