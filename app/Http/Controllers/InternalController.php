<?php

namespace App\Http\Controllers;

use Mail;
use App\User;
use DataTables;
use Carbon\Carbon;
use App\Model\Approver;
use App\Model\Internal;
use App\Model\Department;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Model\EmailRecipient;
use App\Exports\AuditResultExport;
use App\Model\InternalDepartments;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendNewInternalMailJob;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use App\Jobs\SendAuditResultEmailJob;
use App\Mail\Internal\InternalMailer;
use App\Jobs\SendInternalBatchMailJob;
use App\Model\InternalAuditAttachment;
use App\Model\InternalPersonInCharges;
use Illuminate\Support\Facades\Storage;
use App\Model\InternalConPersonInCharges;
use App\Model\InternalSysPersonInCharges;
use Illuminate\Support\Facades\Validator;
use App\Model\InternalCorrPersonInCharges;

class InternalController extends Controller
{
    //
    public function update_qad_approval(Request $request)
    {
        if (Internal::whereIn('audit_stat', [2, 3])->update(['qad_approval' => 1])) {
            return 'Ok';
        } else {
            return 'Failed';
        }
    }

    // public function internal_update_all_person_in_charge(Request $request)
    // {
    //     $internal_audits = Internal::on('mysql')
    //                 ->with(
    //                     [
    //                         'internal_corr_per_in_charges',
    //                         'internal_con_per_in_charges',
    //                         'internal_sys_per_in_charges',
    //                         'internal_corr_per_in_charges.internal_corr_per_in_charge_record',
    //                         'internal_con_per_in_charges.internal_con_per_in_charge_record',
    //                         'internal_sys_per_in_charges.internal_sys_per_in_charge_record'
    //                     ])
    //                 ->get();

    //     // return $internal_audits;

    //     $col_data = collect($internal_audits);
    //     $arr_test = [];

    //     DB::beginTransaction();
    //     try {
    //         for($index = 0; $index < count($col_data); $index++){
    //             $id = $col_data[$index]->internal_audit_id;
    //             $arr_corr_person_in_charges_name = [];

    //             $corr_person_in_charge_names = null;

    //             for($index2 = 0; $index2 < count($col_data[$index]->internal_corr_per_in_charges); $index2++){
    //                 if($col_data[$index]->internal_corr_per_in_charges != null){
    //                     if($col_data[$index]->internal_corr_per_in_charges[$index2]->internal_corr_per_in_charge_record != null){
    //                         $arr_corr_person_in_charges_name[] = $col_data[$index]->internal_corr_per_in_charges[$index2]->internal_corr_per_in_charge_record->name;
    //                     }
    //                 }
    //             }

    //             if(count($arr_corr_person_in_charges_name) > 0){
    //                 $corr_person_in_charge_names = implode(', ', $arr_corr_person_in_charges_name);
    //                 $arr_test[] = $corr_person_in_charge_names;
    //             }

    //             Internal::where('internal_audit_id', $id)
    //                 ->update([
    //                     'correction_person_in_charge' => $corr_person_in_charge_names,
    //                 ]);
    //         }

    //         for($index = 0; $index < count($col_data); $index++){
    //             $id = $col_data[$index]->internal_audit_id;
    //             $arr_con_person_in_charges_name = [];

    //             $con_person_in_charge_names = null;

    //             for($index2 = 0; $index2 < count($col_data[$index]->internal_con_per_in_charges); $index2++){
    //                 if($col_data[$index]->internal_con_per_in_charges != null){
    //                     if($col_data[$index]->internal_con_per_in_charges[$index2]->internal_con_per_in_charge_record != null){
    //                         $arr_con_person_in_charges_name[] = $col_data[$index]->internal_con_per_in_charges[$index2]->internal_con_per_in_charge_record->name;
    //                     }
    //                 }
    //             }

    //             if(count($arr_con_person_in_charges_name) > 0){
    //                 $con_person_in_charge_names = implode(', ', $arr_con_person_in_charges_name);
    //                 $arr_test[] = $con_person_in_charge_names;
    //             }

    //             Internal::where('internal_audit_id', $id)
    //                 ->update([
    //                     'containment_person_in_charge' => $con_person_in_charge_names,
    //                 ]);
    //         }

    //         for($index = 0; $index < count($col_data); $index++){
    //             $id = $col_data[$index]->internal_audit_id;
    //             $arr_sys_person_in_charges_name = [];

    //             $sys_person_in_charge_names = null;

    //             for($index2 = 0; $index2 < count($col_data[$index]->internal_sys_per_in_charges); $index2++){
    //                 if($col_data[$index]->internal_sys_per_in_charges != null){
    //                     if($col_data[$index]->internal_sys_per_in_charges[$index2]->internal_sys_per_in_charge_record != null){
    //                         $arr_sys_person_in_charges_name[] = $col_data[$index]->internal_sys_per_in_charges[$index2]->internal_sys_per_in_charge_record->name;
    //                     }
    //                 }
    //             }

    //             if(count($arr_sys_person_in_charges_name) > 0){
    //                 $sys_person_in_charge_names = implode(', ', $arr_sys_person_in_charges_name);
    //                 $arr_test[] = $sys_person_in_charge_names;
    //             }

    //             Internal::where('internal_audit_id', $id)
    //                 ->update([
    //                     'systematic_person_in_charge' => $sys_person_in_charge_names,
    //                 ]);
    //         }


    //         DB::commit();
    //         return $arr_test;
    //         return 'Saved!';
    //     } catch (Exception $e) {
    //         DB::rollback();
    //         return $e->messages();
    //     }

    // }
    
    public function get_internal_due_date(Request $request)
    {
        date_default_timezone_set('Asia/Manila');
        // FOR IMPROVEMENT PLAN AND APPROVED BY QAD
        session_start();
        $today = Carbon::now()->format('Y-m-d');

        $data = Internal::where('audit_stat', 1)
            ->with([
                'internal_departments' => function ($query) {
                    $query->where('internal_department_stat', 1);
                },
                'internal_departments.department_info',

            ])
            ->where('internal_audit_stat', 1)
            ->where('qad_approval', 1)
            ->whereDate('deadline_of_submission', $today)
            ->where('sending_stat', 1)
            ->get();

        $col_data = collect($data);

        $department_ids = $col_data->pluck('internal_departments')->flatten(1)->where('internal_department_stat', 1)->flatten(1)->pluck('responsible_department_id')->unique()->flatten(1);

        // $department_names = $col_data->pluck('internal_departments')->flatten(1)->where('internal_department_stat', 1)->flatten(1)->pluck('responsible_department_id')->unique()->flatten(1);
        $department_names = [];

        if ($col_data->pluck('internal_departments')->flatten(1)->where('internal_department_stat', 1)->flatten(1)->pluck('department_info') != null) {
            $department_names = $col_data->pluck('internal_departments')->flatten(1)->where('internal_department_stat', 1)->flatten(1)->pluck('department_info')->flatten(1)->unique('department_name')->flatten(1)->pluck('department_name');
        }

        // return $department_names;

        $final_data = [];

        for ($index = 0; $index < count($department_ids); $index++) {
            $email_recipients = EmailRecipient::where('email_recipient_stat', 1)
                ->where('department_id', $department_ids[$index])
                ->with([
                    'receiver_info' => function ($query) {
                        $query->where('user_stat', 1);
                    },
                ])
                ->get();

            $col_email_recipients = collect($email_recipients);

            $attention_to_email = $col_email_recipients->where('address_type', 1)->flatten(1)->where('receiver_info', '!=', null)->flatten(1);

            if ($attention_to_email != null) {
                $attention_to_email = $col_email_recipients->where('address_type', 1)->flatten(1)->where('receiver_info', '!=', null)->flatten(1)->pluck('receiver_info.email');
            }

            $cc_to_email = $col_email_recipients->where('address_type', 2)->flatten(1)->where('receiver_info', '!=', null)->flatten(1);

            if ($cc_to_email != null) {
                $cc_to_email = $col_email_recipients->where('address_type', 2)->flatten(1)->where('receiver_info', '!=', null)->flatten(1)->pluck('receiver_info.email');
            }

            $internal_departments = $col_data->pluck('internal_departments')->flatten(1)->where('internal_department_stat', 1)->flatten(1)->where('responsible_department_id', $department_ids[$index])->flatten(1);

            $audit_ids = [];
            if (count($internal_departments) > 0) {
                $audit_ids = $internal_departments->pluck('internal_audit_id');
            }

            $audit_data = $col_data->whereIn('internal_audit_id', $audit_ids)->flatten(1);

            $final_data[] = [
                'department_id' => $department_ids[$index],
                'department_name' => $department_names[$index],
                'attention_to' => $attention_to_email,
                'cc' => $cc_to_email,
                'data' => $audit_data,
            ];
        }
        // return count($final_data);

        for ($index = 0; $index < count($final_data); $index++) {

            try {
                $to_email = $final_data[$index]['attention_to'];
                $to_cc = $final_data[$index]['cc'];
                $data = $final_data[$index];

                $email = new InternalMailer($data, 'QUADS - Internal Audit Alert Notification (Due Date)');
                Mail::to($to_email)->cc($to_cc)->bcc('group-iss-software@pricon.ph')->send($email);
                // Mail::to('group-iss-software@pricon.ph')->cc('group-iss-software@pricon.ph')->bcc('group-iss-software@pricon.ph')->send($email);
            } catch (\Exception $e) {
                return response()->json(['result' => $e]);
            }
        }
        return response()->json(['result' => 1]);
    }

    public function get_internal_over_due_date(Request $request)
    {
        date_default_timezone_set('Asia/Manila');
        // FOR IMPROVEMENT PLAN AND APPROVED BY QAD
        session_start();
        $today = Carbon::now()->format('Y-m-d');
        // return $today;
        $data = Internal::where('audit_stat', 1)
            ->where('qad_approval', 1)
            ->where('internal_audit_stat', 1)
            ->whereDate('deadline_of_submission', '<', $today)
            ->with([
                'internal_departments' => function ($query) {
                    $query->where('internal_department_stat', 1);
                },
                'internal_departments.department_info',

            ])
            ->where('sending_stat', 1)
            ->get();

        // return count($data);

        $col_data = collect($data);
        // return $data;

        // return $data;


        $department_ids = $col_data->pluck('internal_departments')->flatten(1)->where('internal_department_stat', 1)->flatten(1)->pluck('responsible_department_id')->unique()->flatten(1);

        // $department_names = $col_data->pluck('internal_departments')->flatten(1)->where('internal_department_stat', 1)->flatten(1)->pluck('responsible_department_id')->unique()->flatten(1);
        $department_names = [];

        if ($col_data->pluck('internal_departments')->flatten(1)->where('internal_department_stat', 1)->flatten(1)->pluck('department_info') != null) {
            $department_names = $col_data->pluck('internal_departments')->flatten(1)->where('internal_department_stat', 1)->flatten(1)->pluck('department_info')->flatten(1)->unique('department_name')->flatten(1)->pluck('department_name');
        }

        // return $department_names;

        $final_data = [];

        for ($index = 0; $index < count($department_ids); $index++) {
            $email_recipients = EmailRecipient::where('email_recipient_stat', 1)
                ->where('department_id', $department_ids[$index])
                ->with([
                    'receiver_info' => function ($query) {
                        $query->where('user_stat', 1);
                    },
                ])
                ->get();

            $col_email_recipients = collect($email_recipients);

            $attention_to_email = $col_email_recipients->where('address_type', 1)->flatten(1)->where('receiver_info', '!=', null)->flatten(1);

            if ($attention_to_email != null) {
                $attention_to_email = $col_email_recipients->where('address_type', 1)->flatten(1)->where('receiver_info', '!=', null)->flatten(1)->pluck('receiver_info.email');
            }

            $cc_to_email = $col_email_recipients->where('address_type', 2)->flatten(1)->where('receiver_info', '!=', null)->flatten(1);

            if ($cc_to_email != null) {
                $cc_to_email = $col_email_recipients->where('address_type', 2)->flatten(1)->where('receiver_info', '!=', null)->flatten(1)->pluck('receiver_info.email');
            }

            $internal_departments = $col_data->pluck('internal_departments')->flatten(1)->where('internal_department_stat', 1)->flatten(1)->where('responsible_department_id', $department_ids[$index])->flatten(1);

            $audit_ids = [];
            if (count($internal_departments) > 0) {
                $audit_ids = $internal_departments->pluck('internal_audit_id');
            }

            $audit_data = $col_data->whereIn('internal_audit_id', $audit_ids)->flatten(1);

            $final_data[] = [
                'department_id' => $department_ids[$index],
                'department_name' => $department_names[$index],
                'attention_to' => $attention_to_email,
                'cc' => $cc_to_email,
                'data' => $audit_data,
            ];
        }

        // return $final_data;

        for ($index = 0; $index < count($final_data); $index++) {
            try {
                $to_email = $final_data[$index]['attention_to'];
                $to_cc = $final_data[$index]['cc'];
                $data = $final_data[$index];

                // $email = new InternalMailer($data, 'QUADS - Internal Audit Alert Notification (Over Due)');
                // dump($email);
                Mail::to($to_email)->cc($to_cc)->bcc('group-iss-software@pricon.ph')->send($email);
                // Mail::to('group-iss-software@pricon.ph')->cc('group-iss-software@pricon.ph')->bcc('group-iss-software@pricon.ph')->send($email);
            } catch (\Exception $e) {
                return response()->json(['result' => $e]);
            }
        }

        return response()->json(['result' => 1]);

        // return view('mail.internal.mailer')->with(['data' => $final_data[0]]);

        // return response()->json(['data' => $final_data]);
    }

    public function get_internal_before_due_date(Request $request)
    {
        date_default_timezone_set('Asia/Manila');
        // FOR IMPROVEMENT PLAN
        session_start();
        $days_before = 3;
        $date = Carbon::now()->subDays($days_before + 1)->format('Y-m-d');
        $today = Carbon::now()->format('Y-m-d');

        // return $date;

        $data = Internal::where('audit_stat', 1)
            ->with([
                'internal_departments' => function ($query) {
                    $query->where('internal_department_stat', 1);
                },
                'internal_departments.department_info',

            ])
            ->where('sending_stat', 1)
            ->where('qad_approval', 1)
            ->whereDate('deadline_of_submission', '<', $today)
            ->whereDate('deadline_of_submission', '>', $date)
            ->where('internal_audit_stat', 1)
            ->get();

        $col_data = collect($data);

        $department_ids = $col_data->pluck('internal_departments')->flatten(1)->where('internal_department_stat', 1)->flatten(1)->pluck('responsible_department_id')->unique()->flatten(1);

        // $department_names = $col_data->pluck('internal_departments')->flatten(1)->where('internal_department_stat', 1)->flatten(1)->pluck('responsible_department_id')->unique()->flatten(1);
        $department_names = [];

        if ($col_data->pluck('internal_departments')->flatten(1)->where('internal_department_stat', 1)->flatten(1)->pluck('department_info') != null) {
            $department_names = $col_data->pluck('internal_departments')->flatten(1)->where('internal_department_stat', 1)->flatten(1)->pluck('department_info')->flatten(1)->unique('department_name')->flatten(1)->pluck('department_name');
        }

        // return $department_names;

        $final_data = [];

        for ($index = 0; $index < count($department_ids); $index++) {
            $email_recipients = EmailRecipient::where('email_recipient_stat', 1)
                ->where('department_id', $department_ids[$index])
                ->with([
                    'receiver_info' => function ($query) {
                        $query->where('user_stat', 1);
                    },
                ])
                ->get();

            $col_email_recipients = collect($email_recipients);

            $attention_to_email = $col_email_recipients->where('address_type', 1)->flatten(1)->where('receiver_info', '!=', null)->flatten(1);

            if ($attention_to_email != null) {
                $attention_to_email = $col_email_recipients->where('address_type', 1)->flatten(1)->where('receiver_info', '!=', null)->flatten(1)->pluck('receiver_info.email');
            }

            $cc_to_email = $col_email_recipients->where('address_type', 2)->flatten(1)->where('receiver_info', '!=', null)->flatten(1);

            if ($cc_to_email != null) {
                $cc_to_email = $col_email_recipients->where('address_type', 2)->flatten(1)->where('receiver_info', '!=', null)->flatten(1)->pluck('receiver_info.email');
            }

            $internal_departments = $col_data->pluck('internal_departments')->flatten(1)->where('internal_department_stat', 1)->flatten(1)->where('responsible_department_id', $department_ids[$index])->flatten(1);

            $audit_ids = [];
            if (count($internal_departments) > 0) {
                $audit_ids = $internal_departments->pluck('internal_audit_id');
            }

            $audit_data = $col_data->whereIn('internal_audit_id', $audit_ids)->flatten(1);

            $final_data[] = [
                'department_id' => $department_ids[$index],
                'department_name' => $department_names[$index],
                'attention_to' => $attention_to_email,
                'cc' => $cc_to_email,
                'data' => $audit_data,
            ];
        }

        for ($index = 0; $index < count($final_data); $index++) {
            try {
                $to_email = $final_data[$index]['attention_to'];
                $to_cc = $final_data[$index]['cc'];
                $data = $final_data[$index];

                $email = new InternalMailer($data, 'QUADS - Internal Audit Alert Notification (3 Days Before Due Date)');
                Mail::to($to_email)->cc($to_cc)->bcc('group-iss-software@pricon.ph')->send($email);
                // Mail::to('group-iss-software@pricon.ph')->cc('group-iss-software@pricon.ph')->bcc('group-iss-software@pricon.ph')->send($email);
                // dump($email);
            } catch (\Exception $e) {
                return response()->json(['result' => $e]);
            }
        }

        return response()->json(['result' => 1]);

        // return response()->json(['data' => $data]);
    }

    public function internal_export(Request $request)
    {
        date_default_timezone_set('Asia/Manila');
        session_start();

        $responsible_department = 0;

        if ($request->responsible_department != '0' && $request->has('responsible_department')) {
            $responsible_department = $request->responsible_department;
            $responsible_department = explode(",", $responsible_department);
        }

        $internals = Internal::on('mysql')
            ->with(
                ['internal_departments' => function ($query) use ($responsible_department) {
                    $query->where('internal_department_stat', 1);
                    if ($responsible_department != 0) {
                        $query->whereIn('responsible_department_id', $responsible_department);
                    }
                }, 'internal_departments.departments', 'person_in_charges', 'person_in_charges.person_in_charge_record']
            )
            ->withCount(['internal_departments' => function ($query) use ($responsible_department) {
                $query->where('internal_department_stat', 1);
                if ($responsible_department != 0) {
                    $query->whereIn('responsible_department_id', $responsible_department);
                }
            }])
            ->having('internal_departments_count', '>', 0);

        if ($request->date_from != '0') {
            $internals = $internals->where('audit_date_from', '>=', $request->date_from);
        }

        if ($request->date_to != '0') {
            $internals = $internals->where('audit_date_to', '<=', $request->date_to);
        }

        if ($request->audit_category != '0' && $request->has('audit_category')) {
            $internals = $internals->where('audit_category', $request->audit_category);
        }

        if ($request->audit_type != '0' && $request->has('audit_type')) {
            $internals = $internals->where('audit_type', $request->audit_type);
        }

        // if($request->audit_type_pref_rep_ctrl_no != '0' && $request->has('audit_type_pref_rep_ctrl_no')) {
        //     $internals = $internals->where('audit_report_control_no', 'LIKE', $request->audit_type_pref_rep_ctrl_no . "%");
        // }

        if ($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
            $internals = $internals->where('purpose_of_audit', $request->purpose_of_audit);
        }

        if ($request->classification != '0' && $request->has('classification')) {
            $internals = $internals->where('classification', $request->classification);
        }

        if ($request->audit_stat != '0' && $request->has('audit_stat')) {
            $internals = $internals->where('audit_stat', $request->audit_stat);
        }

        if ($request->audit_report_control_no != '0' && $request->has('audit_report_control_no')) {
            $internals = $internals->where('audit_report_control_no', $request->audit_report_control_no);
        }

        if ($request->item_no != '0' && $request->has('item_no')) {
            $internals = $internals->where('item_no', $request->item_no);
        }

        if ($request->nc_control_no != '0' && $request->has('nc_control_no')) {
            $internals = $internals->where('nc_control_no', $request->nc_control_no);
        }

        $internals = $internals->get();

        return Excel::download(new AuditResultExport(3, '', '', $internals, ''), 'INT' . date('Y-m-d H:i:s') . '.xlsx');
    }

    public function view_internal_audit_by_stat(Request $request)
    {
        session_start();
        $responsible_department = $request->responsible_department;

        $user_access = $request->user_access;

        $is_approver = false;

        $approvers = Approver::where('approver', $_SESSION["rapidx_user_id"])
            ->where('approver_stat', '1')
            ->get();

        if (count($approvers) > 0) {
            $is_approver = true;
        }

        $internals = Internal::on('mysql')
            // ->where('internal_audit_stat', 1);
            ->with(
                ['internal_departments' => function ($query) use ($responsible_department) {
                    $query->where('internal_department_stat', 1);
                    if ($responsible_department != 0) {
                        $query->whereIn('responsible_department_id', $responsible_department);
                    }
                }, 'internal_departments.departments']
            )
            ->withCount(['internal_departments' => function ($query) use ($responsible_department) {
                $query->where('internal_department_stat', 1);
                if ($responsible_department != 0) {
                    $query->whereIn('responsible_department_id', $responsible_department);
                }
            }])
            ->where('internal_audit_stat', $request->internal_audit_stat)
            ->having('internal_departments_count', '>', 0);

        if ($request->audit_id != '0') {
            $internals = $internals->where('internal_audit_id', $request->audit_id);
        }

        if ($request->date_from != '0') {
            $internals = $internals->where('audit_date_from', '>=', $request->date_from);
        }

        if ($request->date_to != '0') {
            $internals = $internals->where('audit_date_to', '<=', $request->date_to);
        }

        if ($request->audit_category != '0' && $request->has('audit_category')) {
            $internals = $internals->where('audit_category', $request->audit_category);
        }

        if ($request->audit_type != '0' && $request->has('audit_type')) {
            $internals = $internals->where('audit_type', $request->audit_type);
        }

        // if($request->audit_type_pref_rep_ctrl_no != '0' && $request->has('audit_type_pref_rep_ctrl_no')) {
        //     $internals = $internals->where('audit_report_control_no', 'LIKE', $request->audit_type_pref_rep_ctrl_no . "%");
        // }

        if ($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
            $internals = $internals->where('purpose_of_audit', $request->purpose_of_audit);
        }

        if ($request->classification != '0' && $request->has('classification')) {
            $internals = $internals->where('classification', $request->classification);
        }

        if ($request->audit_stat != '0' && $request->has('audit_stat')) {
            $internals = $internals->where('audit_stat', $request->audit_stat);
        }

        if ($request->approval_stat != '0' && $request->has('approval_stat')) {
            if ($request->approval_stat == 1) {
                $internals = $internals->where(function ($query) {
                    $query->where('audit_stat', 1);
                    $query->where('qad_approval', 0);
                });
            } else if ($request->approval_stat == 2) {
                $internals = $internals->where(function ($query) {
                    $query->where('audit_stat', 2);
                    $query->where('o_s_approval', 0);
                });
            }
        }

        if ($request->audit_report_control_no != '0' && $request->has('audit_report_control_no')) {
            $internals = $internals->where('audit_report_control_no', $request->audit_report_control_no);
        }

        if ($request->item_no != '0' && $request->has('item_no')) {
            $internals = $internals->where('item_no', $request->item_no);
        }

        if ($request->nc_control_no != '0' && $request->has('nc_control_no')) {
            $internals = $internals->where('nc_control_no', $request->nc_control_no);
        }

        if ($request->evaluation_item != null && $request->has('evaluation_item')) {
            if ($request->evaluation_item == 'Blank') {
                $internals = $internals->where('evaluation_item_id', null);
            } else {
                $internals = $internals->where('evaluation_item_id', $request->evaluation_item);
            }
        }

        // if($request->evaluation_item != null && $request->has('evaluation_item')) {
        //     $internals = $internals->where('category_of_findings', $request->evaluation_item);
        // }

        // $internals = $internals->where('internal_audit_stat', $request->internal_audit_stat);

        $internals = $internals->get();

        return DataTables::of($internals)
            ->addColumn('label1', function ($internal) {
                $result = "";

                if ($internal->sending_stat == 1) {
                    $result .= '<span class="tag tag-warning">Pending</span>';
                } else {
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function ($internal) {
                $result = "";

                if ($internal->audit_stat == 1) {
                    $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
                } else if ($internal->audit_stat == 2) {
                    $result .= '<span class="tag tag-success">For Closed-Out</span>';
                } else {
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function ($internal) {
                $result = '';
                if ($internal->audit_date_from != $internal->audit_date_to) {
                    $result = Carbon::parse($internal->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($internal->audit_date_to)->toFormattedDateString();
                } else {
                    $result = Carbon::parse($internal->audit_date_from)->toFormattedDateString();
                }
                return $result;
            })
            ->addColumn('raw_audit_type', function ($internal) {
                $result = "";

                if ($internal->audit_type == 1) {
                    $result .= 'EMS System';
                } else if ($internal->audit_type == 2) {
                    $result .= 'QMS System';
                } else if ($internal->audit_type == 3) {
                    $result .= 'Process';
                } else {
                    $result .= 'Product';
                }

                return $result;
            })
            ->addColumn('action1', function ($internal) use ($is_approver, $user_access) {
                $result = '<center><div class="btn-group">
                          <button type="button" class="btn btn-primary dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-gear"></i>
                          </button>
                          <div class="dropdown-menu dropdown-menu-right">
                            <button class="dropdown-item aViewIntAudit" internal-id="' . $internal->internal_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalViewIntAudit" data-keyboard="false">View</button>';

                $result .= '<button class="dropdown-item aEditInt" internal-id="' . $internal->internal_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditIntAudit" data-keyboard="false">Edit</button>';

                if ($internal->internal_audit_stat == 1) {
                    $result .= '<button class="dropdown-item aChangeIntAuditStat" type="button" internal-id="' . $internal->internal_audit_id . '" internal-audit-stat="2" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalChangeIntAuditStat" data-keyboard="false">Archive</button>';
                } else {
                    $result .= '<button class="dropdown-item aChangeIntAuditStat" type="button" internal-id="' . $internal->internal_audit_id . '" internal-audit-stat="1" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalChangeIntAuditStat" data-keyboard="false">Restore</button>';
                }

                $result .= '</div>
                        </div></center>';

                // return $result;

                $final = '';
                $final = '<button class="btn-actions btn btn-sm btn-info aViewIntAudit" internal-id="' . $internal->internal_audit_id . '" type="button" data-toggle="modal" data-target="#modalViewIntAudit" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="View" title="View"><i class="icon-file-text-o"></i></button> ';


                if ($internal->internal_audit_stat == 1) {
                    $final .= '<button class="btn-actions btn btn-sm btn-success aEditInt" internal-id="' . $internal->internal_audit_id . '" type="button" data-toggle="modal" data-target="#modalEditIntAudit" data-keyboard="false" title="Edit"><i class="icon-edit2"></i></button> ';

                    $final .= '<button class="btn-actions btn btn-sm btn-danger aChangeIntAuditStat" type="button" internal-id="' . $internal->internal_audit_id . '" internal-audit-stat="2" data-toggle="modal" data-target="#modalChangeIntAuditStat" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="Archive" title="Archive"><i class="icon-trash2"></i></button> ';
                } else {
                    $final .= '<button class="btn-actions btn btn-sm btn-warning aChangeIntAuditStat" type="button" internal-id="' . $internal->internal_audit_id . '" internal-audit-stat="1" data-toggle="modal" data-target="#modalChangeIntAuditStat" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="Restore" title="Restore"><i class="icon-history2"></i></button> ';
                }

                if ($is_approver && $user_access == 4) {
                    if ($internal->qad_approval == '0' && $internal->audit_stat == 1) {

                        $final .= '<button class="btn-actions btn btn-sm btn-primary aQADApprovalIntAudit" type="button" internal-id="' . $internal->internal_audit_id . '" qad-approval="1" data-toggle="modal" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="Approve" title="Approve"><i class="icon-like"></i></button> ';

                        // $final .= '<button class="btn-actions btn btn-sm btn-danger aQADApprovalIntAudit" type="button" internal-id="' . $internal->internal_audit_id . '" qad-approval="0" data-toggle="modal" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="Approve" title="Approve"><i class="icon-thumbs-down"></i></button> '; 
                    }
                }
                return $final;
            })
            ->addColumn('check_box_send', function ($internal) {
                $result = "";

                if ($internal->qad_approval == '0') {
                    $result = "disabled";
                }

                if ($internal->deadline_of_submission == null) {
                    $result = "disabled";
                }

                return '<center><input type="checkbox" class="chkSendIntAudit" internal-id="' . $internal->internal_audit_id . '" ' . $result . '></center>';
            })
            ->addColumn('approval_stat', function ($internal) {
                $result = "";

                if ($internal->audit_stat == 1 && $internal->qad_approval == 1) {
                    $result .= '<span class="tag tag-success">Approved By QAD</span>';
                } else if ($internal->audit_stat == 1 && $internal->qad_approval == 0) {
                    $result .= '<span class="tag tag-warning">For QAD Approval</span>';
                } else if ($internal->audit_stat == 2 && $internal->o_s_approval == 1) {
                    $result .= '<span class="tag tag-success">Approved By Section Head</span>';
                } else if ($internal->audit_stat == 2 && $internal->o_s_approval == 0) {
                    $result .= '<span class="tag tag-warning">For Section Head Approval</span>';
                } else {
                    $result .= '<span class="tag tag-success">Approved By QAD</span>';
                }

                return $result;
            })
            ->rawColumns(['action1', 'label1', 'label2', 'formatted_audit_date', 'check_box_send', 'approval_stat'])
            ->make(true);
    }

    public function view_internal_audit_by_stat_v2(Request $request)
    {
        session_start();
        $responsible_department = $request->responsible_department;

        $user_access = $request->user_access;

        $is_approver = false;

        $approvers = Approver::where('approver', $_SESSION["rapidx_user_id"])
            ->where('approver_stat', '1')
            ->get();

        if (count($approvers) > 0) {
            $is_approver = true;
        }

        $internals = Internal::on('mysql')
            ->with(
                [
                    'internal_departments' => function ($query) use ($responsible_department) {
                        $query->where('internal_department_stat', 1);
                        // if ($responsible_department != 0) {
                        //     $query->whereIn('responsible_department_id', $responsible_department);
                        // }
                    }, 
                    'internal_departments.departments',
                    'qad_approver',
                    'user_last_updated_by'
                ]
                )
                ->when($request->isArchived == 1, function ($query) {
                    $query->where('internal_audit_stat', 5);
                }, function ($query) {
                    $query->where('internal_audit_stat', '<>', 5);
                });
                // ->where('internal_audit_stat', '<>', 5);
            // ->withCount(['internal_departments' => function ($query) use ($responsible_department) {
            //     $query->where('internal_department_stat', 1);
            //     if ($responsible_department != 0) {
            //         $query->whereIn('responsible_department_id', $responsible_department);
            //     }
            // }])
            // // ->where('internal_audit_stat', $request->internal_audit_stat)
            // ->having('internal_departments_count', '>', 0);

        if ($request->filterType != '' || $request->filterType != null) {
            $internals = $internals->where('audit_type', $request->filterType);
        }
        if($request->filterYear != '' || $request->filterYear != null) {
            $internals = $internals->whereYear('audit_date_from', $request->filterYear);
        }
        $internals = $internals->get();

        return DataTables::of($internals)
                ->addColumn('raw_audit_status', function ($internal) {
                $result = "";

                if ($internal->audit_stat == 1) {
                    $result .= '<span class="tag tag-warning">Open</span>';
                } else if ($internal->audit_stat == 2) {
                    $result .= '<span class="tag tag-success">For QAD Approval</span>';
                } else {
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function ($internal) {
                $result = '';
                if ($internal->audit_date_from != $internal->audit_date_to) {
                    $result = Carbon::parse($internal->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($internal->audit_date_to)->toFormattedDateString();
                } else {
                    $result = Carbon::parse($internal->audit_date_from)->toFormattedDateString();
                }
                return $result;
            })
            ->addColumn('raw_audit_type', function ($internal) {
                $result = "";

                if ($internal->audit_type == 1) {
                    $result .= 'EMS System';
                } else if ($internal->audit_type == 2) {
                    $result .= 'QMS System';
                } else if ($internal->audit_type == 3) {
                    $result .= 'Process';
                } else {
                    $result .= 'Product';
                }

                return $result;
            })
            ->addColumn('action1', function ($internal) use ($request) {
              
                $result = '';
                $result = '<button class="btn-actions btn btn-sm btn-info aViewIntAudit" internal-id="' . $internal->internal_audit_id . '" type="button" data-toggle="modal" data-target="#modalViewIntAudit" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="View" title="View"><i class="icon-file-text-o"></i></button> ';


                if ($internal->internal_audit_stat == 1 || $internal->internal_audit_stat == 4) {
                    $result .= '<button class="btn-actions btn btn-sm btn-warning aEditInt" internal-id="' . $internal->internal_audit_id . '" type="button" data-toggle="modal" data-target="#modalAddIntAudit" data-keyboard="false" title="Edit"><i class="icon-edit2"></i></button> ';

                    $result .= '<button class="btn-actions btn btn-sm btn-danger aChangeIntAuditStat" type="button" internal-id="' . $internal->internal_audit_id . '" internal-audit-stat="5" data-toggle="modal" data-target="#modalChangeIntAuditStat" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="Archive" title="Archive"><i class="icon-trash2"></i></button> ';
                } 
                if($internal->audit_stat == 1 && $request->isArchived != 1){
                    $result .= "<button class='btn btn-success btn-sm btnProceedApproval' type='button' data-aType='internal' data-id='{$internal->internal_audit_id}'><i class='icon-paper-plane'></i></button>";
                }

                if($request->isArchived == 1){
                    $result .= '<button class="btn-actions btn btn-sm btn-success aChangeIntAuditStat" type="button" internal-id="' . $internal->internal_audit_id . '" internal-audit-stat="1" data-toggle="modal" data-target="#modalChangeIntAuditStat" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="Restore" title="Restore"><i class="icon-history2"></i></button> ';
                }
                return $result;
            })
            ->addColumn('check_box_send', function ($internal) {
                $result = "";

                if ($internal->qad_approval == '0') {
                    $result = "disabled";
                }

                if ($internal->deadline_of_submission == null) {
                    $result = "disabled";
                }

                return '<center><input type="checkbox" class="chkSendIntAudit" internal-id="' . $internal->internal_audit_id . '" ' . $result . '></center>';
            })
            ->addColumn('approval_stat', function ($internal) {
                $result = "----";

                if($internal->internal_audit_stat == 2){
                    $result = '<span class="tag tag-warning">Pending</span>';
                }
                else if($internal->internal_audit_stat == 3){
                    $result = '<span class="tag tag-success">approved</span>';
                    $result .= "<br><em>Approved by: {$internal->qad_approver->name}</em>";
                }
                else if($internal->internal_audit_stat == 4){
                    $result = '<span class="tag tag-danger">disapproved</span>';
                    $result .= "<br><em>Disapproved by: {$internal->user_last_updated_by->name}</em>";
                    $result .= "<br><em><strong>Remarks</strong>: {$internal->qad_dis_remarks}</em>";
                }

                return $result;
            })
             ->addColumn('raw_responsible_department', function($internal){
                $result = "";

                foreach($internal->internal_departments as $internal_department) {
                    $result .= $internal_department->departments[0]->department_name . ", ";
                }
                return $result;
            })
            ->rawColumns(['action1', 'raw_audit_status', 'formatted_audit_date', 'check_box_send', 'approval_stat', 'raw_responsible_department'])
            ->make(true);
    }

    public function view_internal_audit_for_approval(Request $request)
    {
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

        if (count($approvers) > 0) {
            $is_approver = true;
        }

        if ($user_access == '4') {
            $internals = Internal::on('mysql')
                ->with(
                    ['internal_departments' => function ($query) use ($responsible_department) {
                        $query->where('internal_department_stat', 1);
                        if ($responsible_department != 0) {
                            $query->whereIn('responsible_department_id', $responsible_department);
                        }
                    }, 'internal_departments.departments', 'person_in_charges', 'person_in_charges.person_in_charge_record']
                )
                ->withCount(['internal_departments' => function ($query) use ($responsible_department) {
                    $query->where('internal_department_stat', 1);
                    if ($responsible_department != 0) {
                        $query->whereIn('responsible_department_id', $responsible_department);
                    }
                }])
                ->where('qad_approval', 0)
                ->having('internal_departments_count', '>', 0);
        } else {
            $responsible_department = [];

            foreach ($approvers as $approver) {
                $responsible_department[] = $approver->department_id;
            }

            if ($is_approver == false) {
                $responsible_department[] = $_SESSION['rapidx_department_id'];
            }

            $internals = Internal::on('mysql')
                ->with(
                    ['internal_departments' => function ($query) use ($responsible_department) {
                        $query->where('internal_department_stat', 1);
                        if ($responsible_department != 0) {
                            $query->whereIn('responsible_department_id', $responsible_department);
                        }
                    }, 'internal_departments.departments', 'person_in_charges', 'person_in_charges.person_in_charge_record']
                )
                ->withCount(['internal_departments' => function ($query) use ($responsible_department) {
                    $query->where('internal_department_stat', 1);
                    if ($responsible_department != 0) {
                        $query->whereIn('responsible_department_id', $responsible_department);
                    }
                }])
                ->where('o_s_approval', 0)
                ->where('audit_stat', 2)
                ->where('internal_audit_stat', 1)
                ->having('internal_departments_count', '>', 0);
        }


        if ($request->date_from != '0') {
            $internals = $internals->where('audit_date_from', '>=', $request->date_from);
        }

        if ($request->date_to != '0') {
            $internals = $internals->where('audit_date_to', '<=', $request->date_to);
        }

        if ($request->audit_category != '0' && $request->has('audit_category')) {
            $internals = $internals->where('audit_category', $request->audit_category);
        }

        if ($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
            $internals = $internals->where('purpose_of_audit', $request->purpose_of_audit);
        }

        if ($request->classification != '0' && $request->has('classification')) {
            $internals = $internals->where('classification', $request->classification);
        }

        if ($request->audit_stat != '0' && $request->has('audit_stat')) {
            $internals = $internals->where('audit_stat', $request->audit_stat);
        }

        $internals = $internals->get();

        return DataTables::of($internals)
            ->addColumn('label1', function ($internal) {
                $result = "";

                if ($internal->sending_stat == 1) {
                    $result .= '<span class="tag tag-warning">Pending</span>';
                } else {
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function ($internal) {
                $result = "";

                if ($internal->audit_stat == 1) {
                    $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
                } else if ($internal->audit_stat == 2) {
                    $result .= '<span class="tag tag-success">For Closed-Out</span>';
                } else {
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function ($internal) {
                return Carbon::parse($internal->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($internal->audit_date_to)->toFormattedDateString();
            })
            ->addColumn('action1', function ($internal) use ($user_access, $is_approver) {
                // Old code
                // $result = '<div class="btn-group">
                //           <button type="button" class="btn btn-primary dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                //             <i class="icon-gear"></i>
                //           </button>
                //           <div class="dropdown-menu dropdown-menu-right">
                //             <button class="dropdown-item aViewIntAudit" internal-id="' . $internal->internal_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalViewIntAudit" data-keyboard="false">View</button>';

                $result = '<div class="btn-group">
                          <button type="button" class="btn btn-primary dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-gear"></i>
                          </button>
                          <div class="dropdown-menu dropdown-right">
                            <button class="dropdown-item aViewIntAudit" internal-id="' . $internal->internal_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalViewIntAudit" data-keyboard="false">View</button>';

                // if($internal->sending_stat != 2){
                //     $result .= '<button class="dropdown-item aEditInt" internal-id="' . $internal->internal_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditIntAudit" data-keyboard="false">Edit</button>';
                // }            

                // if($internal->audit_stat == 1 || $internal->audit_stat == 2){
                //     $result .= '<button class="dropdown-item aCloseIntAudit" type="button" internal-id="' . $internal->internal_audit_id . '" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalCloseIntAudit" data-keyboard="false">Close</button>';
                // }
                // else if($internal->audit_stat == 3 && $internal->sending_stat == 1){
                //     $result .= '<button class="dropdown-item aOpenIntAudit" type="button" style="padding: 1px 1px; text-align: center;" internal-id="' . $internal->internal_audit_id . '" data-toggle="modal" data-target="#modalOpenIntAudit" data-keyboard="false">Open</button>';
                // }

                // if($internal->sending_stat == 1){
                //     $result .= '<button class="dropdown-item aDoneIntAudit" type="button" internal-id="' . $internal->internal_audit_id . '" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalDoneIntAudit" data-keyboard="false">Done</button>';
                // }

                // if($internal->sending_stat == 2) {
                //     $result .= '<button class="dropdown-item aPendIntAudit" type="button" internal-id="' . $internal->internal_audit_id . '" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalPendIntAudit" data-keyboard="false">Pend</button>';
                // }

                if ($user_access == 4) {
                    if ($internal->sending_stat != 2) {
                        $result .= '<button class="dropdown-item aEditInt" internal-id="' . $internal->internal_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditIntAudit" data-keyboard="false">Edit</button>';
                    }
                } else {
                    if ($internal->sending_stat != 2) {
                        if (($internal->audit_stat == 2 && $internal->qad_approval == 1) && $is_approver == true) {
                            if ($internal->audit_stat == 2 && $internal->o_s_approval == 0 && $is_approver == true) {
                                $result .= '<button class="dropdown-item aEditInt" internal-id="' . $internal->internal_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditIntAudit" data-keyboard="false">Edit</button>';
                            } else if ($internal->audit_stat == 2 && $internal->o_s_approval == 0 && $is_approver == false) {
                                $result .= '<button class="dropdown-item aEditInt" internal-id="' . $internal->internal_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditIntAudit" data-keyboard="false">Edit</button>';
                            }
                        } else if ($internal->audit_stat == 1 && $internal->qad_approval == 1) {
                            $result .= '<button class="dropdown-item aEditInt" internal-id="' . $internal->internal_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditIntAudit" data-keyboard="false">Edit</button>';
                        }
                    }
                }

                $result .= '</div>
                        </div>';

                return $result;
            })
            ->addColumn('check_box_send', function ($internal) use ($is_approver, $user_access) {
                $result = "disabled";

                if ($user_access == 4) {
                    if ($internal->qad_approval == '0') {
                        $result = "";
                    }
                } else {
                    if ($internal->o_s_approval == '0') {
                        $result = "";
                    }
                }

                if ($is_approver == false) {
                    $result = "disabled";
                }

                return '<center><input type="checkbox" class="chkSendIntAudit" internal-id="' . $internal->internal_audit_id . '" ' . $result . '></center>';
            })
            ->addColumn('approval_stat', function ($internal) {
                $result = "";

                if ($internal->audit_stat == 1 && $internal->qad_approval == 1) {
                    $result .= '<span class="tag tag-success">Approved By QAD</span>';
                } else if ($internal->audit_stat == 1 && $internal->qad_approval == 0) {
                    $result .= '<span class="tag tag-warning">For QAD Approval</span>';
                } else if ($internal->audit_stat == 2 && $internal->o_s_approval == 1) {
                    $result .= '<span class="tag tag-success">Approved By Section Head</span>';
                } else if ($internal->audit_stat == 2 && $internal->o_s_approval == 0) {
                    $result .= '<span class="tag tag-warning">For Section Head Approval</span>';
                } else {
                    $result .= '<span class="tag tag-success">Approved By QAD</span>';
                }

                return $result;
            })
            ->rawColumns(['action1', 'label1', 'label2', 'formatted_audit_date', 'check_box_send', 'approval_stat'])
            ->make(true);
    }
    public function view_internal_audit_for_approval_v2(Request $request)
    {
        session_start();
        $internal_audits = Internal::with([
            'internal_departments' => function($query){
                $query->where('internal_department_stat', 1);
            },
            'internal_departments.departments'
        ])
        ->whereYear('created_at', '>=', '2026')
        ->where('internal_audit_stat', 2)
        ->where('audit_stat', 2)
        ->get();

        return DataTables::of($internal_audits)
        ->addColumn('action', function($internal_audit){
            $result = "";
            $result .= "<button class='btn btn-sm btn-info aViewIntAudit' style='margin-right: 2px;' internal-id='{$internal_audit->internal_audit_id}' type='button' data-toggle='modal' data-target='#modalViewIntAudit' data-keyboard='false' title='View Audit Result'><i class='icon-eye'></i></button>";
            $result .= "<button class='btn btn-sm btn-success btnApprove' style='margin-right: 2px;' data-id='{$internal_audit->internal_audit_id}' data-classification='internal' title='Approve Audit Result'><i class='icon-circle-check'></i></button>";
            $result .= "<button class='btn btn-sm btn-danger btnDisapprove' data-id='{$internal_audit->internal_audit_id}' data-classification='internal' title='Disapprove Audit Result'><i class='icon-times'></i></button>";
            return $result;
        })
        ->addColumn('formatted_audit_date', function ($internal) {
            return Carbon::parse($internal->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($internal->audit_date_to)->toFormattedDateString();
        })
        ->addColumn('raw_audit_type', function ($internal) {
            $result = "";

            if ($internal->audit_type == 1) {
                $result .= 'EMS System';
            } else if ($internal->audit_type == 2) {
                $result .= 'QMS System';
            } else if ($internal->audit_type == 3) {
                $result .= 'Process';
            } else if($internal->audit_type == 4) {
                $result .= 'Product';
            } else if($internal->audit_type == 5) {
                $result .= 'QMS/IATF System';
            }

            return $result;
        })
        ->rawColumns(['action', 'formatted_audit_date', 'raw_audit_type'])
        ->make(true);
    }

    public function view_batch_internal_audit_by_stat(Request $request)
    {
        session_start();
        $internal_audit_id = [];
        if (isset($request->internal_audit_id) || $request->internal_audit_id != null) {
            $internal_audit_id = $request->internal_audit_id;
        }
        $internals = Internal::with([
            'internal_departments',
            'internal_departments.departments'
        ])
            ->whereIn('internal_audit_id', $internal_audit_id)
            ->get();

        return DataTables::of($internals)
            ->addColumn('label1', function ($internal) {
                $result = "";

                if ($internal->sending_stat == 1) {
                    $result .= '<span class="tag tag-warning">Pending</span>';
                } else {
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function ($internal) {
                $result = "";

                if ($internal->audit_stat == 1) {
                    $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
                } else if ($internal->audit_stat == 2) {
                    $result .= '<span class="tag tag-success">For Closed-Out</span>';
                } else {
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function ($internal) {
                return Carbon::parse($internal->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($internal->audit_date_to)->toFormattedDateString();
            })
            ->addColumn('action1', function ($internal) {
                $result = '<div class="btn-group">
                          <button type="button" class="btn btn-primary dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-gear"></i>
                          </button>
                          <div class="dropdown-menu dropdown-menu-right">
                            <button class="dropdown-item aViewIntAudit" internal-id="' . $internal->internal_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalViewIntAudit" data-keyboard="false">View</button>';

                if ($internal->sending_stat != 2) {
                    $result .= '<button class="dropdown-item aEditInt" internal-id="' . $internal->internal_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalEditIntAudit" data-keyboard="false">Edit</button>';
                }

                $result .= '<button class="dropdown-item aRemoveIntInBatch" internal-id="' . $internal->internal_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalRemoveIntInBatch" data-keyboard="false">Remove</button>';

                $result .= '</div>
                        </div>';

                return $result;
            })
            ->rawColumns(['action1', 'label1', 'label2', 'formatted_audit_date'])
            ->make(true);
    }

    public function get_int_audit_by_rep_ctrl_no(Request $request)
    {
        session_start();
        $internal_audits = Internal::where('audit_report_control_no', $request->audit_report_control_no)
            ->orderBy('internal_audit_id', 'desc')->first();

        return response()->json(['internal_audits' => $internal_audits]);
    }

    public function view_single_internal_audit(Request $request)
    {
        session_start();
        $responsible_department = $request->responsible_department;

        $internals = Internal::with(
            ['internal_departments' => function ($query) use ($responsible_department) {
                $query->where('internal_department_stat', 1);
                if ($responsible_department != 0) {
                    $query->where('responsible_department_id', $responsible_department);
                }
            }, 'internal_departments.departments']
        )
            ->withCount(['internal_departments' => function ($query) use ($responsible_department) {
                $query->where('internal_department_stat', 1);
                if ($responsible_department != 0) {
                    $query->where('responsible_department_id', $responsible_department);
                }
            }])
            ->where('in_batch', 0)
            ->where('internal_audit_stat', 1)
            ->where('audit_stat', 1)
            ->where('sending_stat', 1)
            ->having('internal_departments_count', '>', 0);

        if ($request->date_from != '0' && $request->date_to != '0') {
            $internals = $internals->whereBetween('audit_date', [$request->date_from, $request->date_to]);
        }

        if ($request->audit_category != '0' && $request->has('audit_category')) {
            $internals = $internals->where('audit_category', $request->audit_category);
        }

        if ($request->audit_type != '0' && $request->has('audit_type')) {
            $internals = $internals->where('audit_type', $request->audit_type);
        }

        if ($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
            $internals = $internals->where('purpose_of_audit', $request->purpose_of_audit);
        }

        if ($request->classification != '0' && $request->has('classification')) {
            $internals = $internals->where('classification', $request->classification);
        }

        if ($request->audit_stat != '0' && $request->has('audit_stat')) {
            $internals = $internals->where('audit_stat', $request->audit_stat);
        }

        if ($request->audit_report_control_no != '0' && $request->has('audit_report_control_no')) {
            $internals = $internals->where('audit_report_control_no', $request->audit_report_control_no);
        }

        $internals = $internals->get();

        return DataTables::of($internals)
            ->addColumn('label1', function ($internal) {
                $result = "";

                if ($internal->sending_stat == 1) {
                    $result .= '<span class="tag tag-warning">Pending</span>';
                } else {
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function ($internal) {
                $result = "";

                if ($internal->audit_stat == 1) {
                    $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
                } else if ($internal->audit_stat == 2) {
                    $result .= '<span class="tag tag-success">For Closed-Out</span>';
                } else {
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function ($internal) {
                return Carbon::parse($internal->audit_date)->toFormattedDateString();
            })
            ->addColumn('action1', function ($internal) {
                $result = '<div class="btn-group">
                          <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-cog"></i>
                          </button>
                          <div class="dropdown-menu dropdown-menu-right">
                            <button class="dropdown-item aViewIntAudit" internal-id="' . $internal->internal_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalViewIntAudit" data-keyboard="false">View</button>';

                if (($internal->sending_stat == 1 || $internal->audit_stat == 1 || $internal->audit_stat == 2) && $internal->in_batch == 0) {
                    $result .= '<button class="dropdown-item aAddToBatchIntAudit" type="button" internal-id="' . $internal->internal_audit_id . '" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalAddToBatchIntAudit" data-keyboard="false">Add to Batch</button>';
                }


                $result .= '</div>
                        </div>';

                return $result;
            })
            ->rawColumns(['action1', 'label1', 'label2', 'formatted_audit_date'])
            ->make(true);
    }

    public function add_internal_audit(Request $request)
    {
        date_default_timezone_set('Asia/Manila');
        session_start();

        $email_stat = 0;
        $request_approval = 1;


        if (isset($request->request_approval)) {
            $request_approval = 0;
        }

        if (isset($request->send_email)) {
            $email_stat = 1;
        }

        if (isset($request->send_email)) {
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
            if (isset($request->img_corrective_action)) {
                $ext_corr_act = $request->file('img_corrective_action')->getClientOriginalExtension();
            }

            $ext_illustration = "";
            if (isset($request->img_illustration)) {
                $ext_illustration = $request->file('img_illustration')->getClientOriginalExtension();
            }

            $ext_pdf_corr_act = "";
            if (isset($request->pdf_corrective_action)) {
                $ext_pdf_corr_act = $request->file('pdf_corrective_action')->getClientOriginalExtension();
            }

            $ext_pdf_illustration = "";
            if (isset($request->pdf_illustration)) {
                $ext_pdf_illustration = $request->file('pdf_illustration')->getClientOriginalExtension();
            }

            DB::beginTransaction();

            try {
                $img_corr_act = "";
                if (isset($request->img_corrective_action)) {
                    $img_corr_act = date('YmdHis') . 'corract' . Str::random(5) . '.' . $ext_corr_act;
                }

                $img_illustration = "";
                if (isset($request->img_illustration)) {
                    $img_illustration = date('YmdHis') . 'illu' . Str::random(5) . '.' . $ext_illustration;
                }

                $pdf_corr_act = "";
                if (isset($request->pdf_corrective_action)) {
                    $pdf_corr_act = date('YmdHis') . 'corract' . Str::random(5) . '.' . $ext_pdf_corr_act;
                }

                $pdf_illustration = "";
                if (isset($request->pdf_illustration)) {
                    $pdf_illustration = date('YmdHis') . 'illu' . Str::random(5) . '.' . $ext_pdf_illustration;
                }

                $internal_audit_id = Internal::on('mysql')->insertGetId([
                    'audit_type' => $request->audit_type,
                    'audit_date_from' => $request->audit_date_from,
                    'audit_date_to' => $request->audit_date_to,
                    'business_process' => $request->business_process,
                    'auditors' => $request->auditors,
                    'auditees' => $request->auditees,
                    'audit_report_control_no' => $request->audit_report_control_no,
                    'classification' => $request->classification,
                    'nc_control_no' => $request->nc_control_no,
                    'item_no' => $request->item_no,
                    // 'category_of_findings' => $request->category_of_findings,
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
                    'correction' => $request->correction,
                    'containment' => $request->containment,
                    'systematic' => $request->systematic,
                    'correction_commitment_date' => $request->correction_commitment_date,
                    'correction_person_in_charge' => $request->txt_correction_person_in_charge,
                    'containment_commitment_date' => $request->containment_commitment_date,
                    'containment_person_in_charge' => $request->txt_containment_person_in_charge,
                    'systematic_commitment_date' => $request->systematic_commitment_date,
                    'systematic_person_in_charge' => $request->txt_systematic_person_in_charge,
                    'improvement_action' => $request->improvement_plan,
                    'commitment_date' => $request->commitment_date,
                    // 'responsible_department' => $request->responsible_department,
                    // 'person_in_charge' => $request->person_in_charge,
                    'corrective_action' => $request->corrective_action,
                    'img_corrective_action' => $img_corr_act,
                    'pdf_corrective_action' => $pdf_corr_act,
                    'qad_approval' => $request_approval,
                    'o_s_approval' => 0,
                    'qad_approved_by' => 0,
                    'o_s_approved_by' => 0,
                    'audit_stat' => $request->audit_stat,
                    'internal_audit_stat' => 1,
                    'created_by' => $_SESSION["rapidx_user_id"],
                    'last_updated_by' => $_SESSION["rapidx_user_id"],
                    'update_version' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                for ($index = 0; $index < count($request->responsible_department); $index++) {
                    InternalDepartments::insert([
                        'internal_audit_id' => $internal_audit_id,
                        'responsible_department_id' => $request->responsible_department[$index],
                        'internal_department_stat' => 1,
                        'created_by' => $_SESSION['rapidx_user_id'],
                        'last_updated_by' => $_SESSION['rapidx_user_id'],
                        'update_version' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }

                // if(isset($request->person_in_charge)){
                //     for($index = 0; $index < count($request->person_in_charge); $index++) {
                //         InternalPersonInCharges::insert([
                //             'internal_audit_id' => $internal_audit_id,
                //             'person_in_charge_id' => $request->person_in_charge[$index],
                //             'created_by' => $_SESSION['rapidx_user_id'],
                //             'created_at' => date('Y-m-d H:i:s'),
                //             'updated_at' => date('Y-m-d H:i:s'),
                //         ]);
                //     }
                // }

                if (isset($request->correction_person_in_charge)) {
                    for ($index = 0; $index < count($request->correction_person_in_charge); $index++) {
                        InternalCorrPersonInCharges::insert([
                            'internal_audit_id' => $internal_audit_id,
                            'person_in_charge_id' => $request->correction_person_in_charge[$index],
                            'created_by' => $_SESSION['rapidx_user_id'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }

                if (isset($request->containment_person_in_charge)) {
                    for ($index = 0; $index < count($request->containment_person_in_charge); $index++) {
                        InternalConPersonInCharges::insert([
                            'internal_audit_id' => $internal_audit_id,
                            'person_in_charge_id' => $request->containment_person_in_charge[$index],
                            'created_by' => $_SESSION['rapidx_user_id'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }

                if (isset($request->systematic_person_in_charge)) {
                    for ($index = 0; $index < count($request->systematic_person_in_charge); $index++) {
                        InternalSysPersonInCharges::insert([
                            'internal_audit_id' => $internal_audit_id,
                            'person_in_charge_id' => $request->systematic_person_in_charge[$index],
                            'created_by' => $_SESSION['rapidx_user_id'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }

                DB::commit();

                if (isset($request->send_email)) {
                    dispatch(new SendNewInternalMailJob($internal_audit_id));
                }

                if (isset($request->img_corrective_action)) {
                    if (!Storage::putFileAs('public/audit_result_images/internal', $request->file('img_corrective_action'), $img_corr_act)) {
                        return response()->json(['upload_result' => 0]);
                    }
                }
                if (isset($request->img_illustration)) {
                    if (!Storage::putFileAs('public/audit_result_images/internal', $request->file('img_illustration'), $img_illustration)) {
                        return response()->json(['upload_result' => 0]);
                    }
                }

                if (isset($request->pdf_corrective_action)) {
                    if (!Storage::putFileAs('public/audit_result_pdf/internal', $request->file('pdf_corrective_action'), $pdf_corr_act)) {
                        return response()->json(['upload_result' => 0]);
                    }
                }
                if (isset($request->pdf_illustration)) {
                    if (!Storage::putFileAs('public/audit_result_pdf/internal', $request->file('pdf_illustration'), $pdf_illustration)) {
                        return response()->json(['upload_result' => 0]);
                    }
                }
                return response()->json(['result' => 1]);
            } catch (\Exeption $e) {
                DB::rollback();
                return response()->json(['result' => 0]);
            }
        } else {
            return response()->json(['result' => '0', 'error' => $validator->messages()]);
        }
    }
    public function add_internal_audit_v2(Request $request)
    {
        date_default_timezone_set('Asia/Manila');
        session_start();

        $email_stat = 0;
        $request_approval = 1;

        $data = $request->all();

        DB::beginTransaction();
        try {
            if(isset($request->internal_id)){ // Update
                $extension = "";
                $pdf_extension = "";
                $image_name = "";
                $pdf_name = "";

                if(isset($request->img_corrective_action)) {
                    $extension = $request->file('img_corrective_action')->getClientOriginalExtension();
                    $image_name = date('YmdHis') . Str::random(5) . '.' . $extension;
                    if(!Storage::putFileAs('public/audit_result_images/internal', $request->file('img_corrective_action'), $image_name)){
                        return response()->json(['upload_result' => 0]);
                    }
                }

                if(isset($request->pdf_corrective_action)) {
                    $pdf_extension = $request->file('pdf_corrective_action')->getClientOriginalExtension();
                    $pdf_name = date('YmdHis') . Str::random(5) . '.' . $pdf_extension;
                    if(!Storage::putFileAs('public/audit_result_pdf/internal', $request->file('pdf_corrective_action'), $pdf_name)){
                        return response()->json(['upload_result' => 0]);
                    }
                }

                Internal::on('mysql')->where('internal_audit_id', $request->internal_id)
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
                        $attachment_id = Internal::on('mysql')->insertGetId([
                            'internal_audit_id' => $request->internal_id,
                            'filename' => $original_filename,
                            'path' => 'audit_result_attachments/internal/',
                            'remarks' => $data['remarks'],
                            // 'uploaded_by' => $_SESSION['rapidx_user_id'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        // Path where file will be saved
                        Storage::putFileAs('public/audit_result_attachments/internal', $data['file'], "$attachment_id.$ext");
                    }
                }

            }
            else{ // Create
                $validator = Validator::make($data, [
                    'audit_type' => ['required', 'string', 'max:255'],
                    'audit_date_from' => ['required', 'date', 'max:255'],
                    'audit_date_to' => ['required', 'date', 'max:255'],
                    'auditors' => ['required', 'string', 'max:255'],
                    'auditees' => ['required', 'string', 'max:255'],
                    'audit_report_control_no' => ['required', 'string', 'max:255'],
                    'classification' => ['required', 'string', 'max:255'],
                    'audit_findings_issued_date' => ['required', 'string', 'max:255'],
                    'responsible_department' => ['required'],
                    'audit_title' => ['required'],
                ]);

                if ($validator->passes()) {

                    $data = [
                        'audit_type' => $request->audit_type,
                        'audit_date_from' => $request->audit_date_from,
                        'audit_date_to' => $request->audit_date_to,
                        'business_process' => $request->business_process,
                        'auditors' => $request->auditors,
                        'auditees' => $request->auditees,
                        'audit_report_control_no' => $request->audit_report_control_no,
                        'classification' => $request->classification,
                        'audit_findings_issued_date' => $request->audit_findings_issued_date,
                        'deadline_of_submission' => $request->deadline_of_submission,
                        'actual_date_of_submission' => $request->actual_date_of_submission,
                        'in_batch' => 0,
                        'qad_approval' => $request_approval,
                        'o_s_approval' => 0,
                        'qad_approved_by' => 0,
                        'o_s_approved_by' => 0,
                        'audit_stat' => 1,
                        'internal_audit_stat' => 1,
                        'created_by' => $_SESSION["rapidx_user_id"],
                        'last_updated_by' => $_SESSION["rapidx_user_id"],
                        'update_version' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'audit_title' => $request->audit_title,

                    ];

                     if ($request->classification === 'CPAR') {
                        $data['number_OFI'] = $request->number_OFI;
                        $data['number_major_nc'] = $request->number_major_nc;
                        $data['number_minor_nc'] = $request->number_minor_nc;
                        $data['number_possitive_feedback'] = $request->number_possitive_feedback;
                    }

                    $internal_audit_id = Internal::on('mysql')->insertGetId($data);

                    for ($index = 0; $index < count($request->responsible_department); $index++) {
                        InternalDepartments::insert([
                            'internal_audit_id' => $internal_audit_id,
                            'responsible_department_id' => $request->responsible_department[$index],
                            'internal_department_stat' => 1,
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
                            $attachment_id = InternalAuditAttachment::on('mysql')->insertGetId([
                                'internal_audit_id' => $internal_audit_id,
                                'filename' => $original_filename,
                                'path' => 'audit_result_attachments/internal/',
                                'remarks' => $data['remarks'],
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                            // Path where file will be saved
                            Storage::putFileAs('public/audit_result_attachments/internal', $data['file'], "$attachment_id.$ext");
                        }
                    }

                    dispatch(new SendAuditResultEmailJob($internal_audit_id, 'internal'));

                } else {
                    return response()->json(['result' => '0', 'error' => $validator->messages()]);
                }
            }
            
            DB::commit();
            return response()->json(['result' => 1]);
        } catch (\Exeption $e) {
            DB::rollback();
            return response()->json(['result' => 0]);
        }
        
    }

    public function send_internal_batch(Request $request)
    {
        session_start();
        // try{
        //     dispatch(new SendInternalBatchMailJob($request->internal_audit_id));
        //     return response()->json(['result' => 1]);
        // }
        // catch(\Exception $e){
        //     return response()->json(['result' => $e]);
        // }

        // $today = Carbon::now()->format('Y-m-d');

        $data = Internal::whereIn('internal_audit_id', $request->internal_audit_id)
            ->with([
                'internal_departments',
                'internal_departments.department_info',
                // 'internal_departments.email_recipients_list',

            ])
            ->get();

        // return $data;

        $col_data = collect($data);


        $department_ids = $col_data->pluck('internal_departments')->flatten(1)->where('internal_department_stat', 1)->flatten(1)->pluck('responsible_department_id')->unique()->flatten(1);

        // $department_names = $col_data->pluck('internal_departments')->flatten(1)->where('internal_department_stat', 1)->flatten(1)->pluck('responsible_department_id')->unique()->flatten(1);
        $department_names = [];

        if ($col_data->pluck('internal_departments')->flatten(1)->where('internal_department_stat', 1)->flatten(1)->pluck('department_info') != null) {
            $department_names = $col_data->pluck('internal_departments')->flatten(1)->where('internal_department_stat', 1)->flatten(1)->pluck('department_info')->flatten(1)->unique('department_name')->flatten(1)->pluck('department_name');
        }

        // return $department_names;

        $final_data = [];

        for ($index = 0; $index < count($department_ids); $index++) {
            $email_recipients = EmailRecipient::where('email_recipient_stat', 1)
                ->where('department_id', $department_ids[$index])
                ->with([
                    'receiver_info' => function ($query) {
                        $query->where('user_stat', 1);
                    },
                ])
                ->get();

            $col_email_recipients = collect($email_recipients);

            $attention_to_email = $col_email_recipients->where('address_type', 1)->flatten(1)->where('receiver_info', '!=', null)->flatten(1);

            // return $attention_to_email;


            if ($attention_to_email != null) {
                $attention_to_email = $col_email_recipients->where('address_type', 1)->flatten(1)->where('receiver_info', '!=', null)->flatten(1)->pluck('receiver_info.email');
            }

            $cc_to_email = $col_email_recipients->where('address_type', 2)->flatten(1)->where('receiver_info', '!=', null)->flatten(1);

            if ($cc_to_email != null) {
                $cc_to_email = $col_email_recipients->where('address_type', 2)->flatten(1)->where('receiver_info', '!=', null)->flatten(1)->pluck('receiver_info.email');
            }

            $internal_departments = $col_data->pluck('internal_departments')->flatten(1)->where('internal_department_stat', 1)->flatten(1)->where('responsible_department_id', $department_ids[$index])->flatten(1);

            $audit_ids = [];
            if (count($internal_departments) > 0) {
                $audit_ids = $internal_departments->pluck('internal_audit_id');
            }

            $audit_data = $col_data->whereIn('internal_audit_id', $audit_ids)->flatten(1);

            $final_data[] = [
                'department_id' => $department_ids[$index],
                'department_name' => $department_names[$index],
                'attention_to' => $attention_to_email,
                'cc' => $cc_to_email,
                'data' => $audit_data,
            ];
        }

        // return view('mail.internal.mailer')->with(['data' => $final_data[0]]);


        for ($index = 0; $index < count($final_data); $index++) {
            try {
                $to_email = $final_data[$index]['attention_to'];
                $to_cc = $final_data[$index]['cc'];
                $data = $final_data[$index];

                // return $to_email;

                $email = new InternalMailer($data, 'QUADS - Internal Audit Alert Notification');
                Mail::to($to_email)->cc($to_cc)->bcc('group-iss-software@pricon.ph')->send($email);
            } catch (\Exception $e) {
                return response()->json(['result' => $e]);
            }
        }


        // $email = new InternalMailer($data, 'QUADS - Internal Audit Alert Notification');
        // Mail::to($to_email)->cc($to_cc)->send($email);

        return response()->json(['result' => 1, 'test' => $col_data]);
    }

    public function approve_internal_batch(Request $request)
    {
        date_default_timezone_set('Asia/Manila');
        session_start();
        DB::beginTransaction();

        try {
            if ($request->user_access == 4) {
                Internal::on('mysql')->whereIn('internal_audit_id', $request->internal_audit_id)
                    ->increment(
                        'update_version',
                        1,
                        [
                            'qad_approval' => 1,
                            'qad_approved_by' => $_SESSION["rapidx_user_id"],
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]
                    );
            } else {
                Internal::on('mysql')->whereIn('internal_audit_id', $request->internal_audit_id)
                    ->increment(
                        'update_version',
                        1,
                        [
                            'o_s_approval' => 1,
                            'o_s_approved_by' => $_SESSION["rapidx_user_id"],
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]
                    );
            }
            DB::commit();
            return response()->json(['result' => "1"]);
        } catch (\Exception $e) {
            DB::rollback();
            // throw $e;
            return response()->json(['result' => "0"]);
        }
    }

    public function edit_internal_audit(Request $request)
    {
        session_start();

        $data = $request->all();

        $validator = Validator::make($data, [
            'audit_type' => ['required', 'string', 'max:255'],
            'audit_date_from' => ['required', 'string', 'max:255'],
            'audit_date_to' => ['required', 'string', 'max:255'],
            'business_process' => ['required', 'string', 'max:255'],
            'auditors' => ['required', 'string', 'max:255'],
            'auditees' => ['required', 'string', 'max:255'],
            'audit_report_control_no' => ['required', 'string', 'max:255'],
            'classification' => ['required', 'string', 'max:255'],
            // 'category_of_findings' => ['required', 'string', 'max:255'],
            'audit_findings_issued_date' => ['required', 'string', 'max:255'],
            // 'deadline_of_submission' => ['required', 'string', 'max:255'], //Deadline for submission of CA Report
            // 'deadline_of_submission_days' => ['required', 'string', 'max:255'],
            // 'actual_date_of_submission' => ['required', 'string', 'max:255'],
            'iso_iatf_clause' => ['required', 'string', 'max:255'], // ISO / IATF Clause
            'statement_of_findings' => ['required', 'string'],
            'responsible_department' => ['required'],
            'audit_stat' => ['required'],
            'sending_stat' => ['required'],
        ]);

        $request_approval = 1;

        if (isset($request->request_approval)) {
            $request_approval = 0;
        }

        if ($request->audit_stat == 2 || $request->audit_stat == 3) {
            $request_approval = 1;
        }

        if ($validator->passes()) {

            DB::beginTransaction();

            try {
                $ext_illustration = "";
                $img_illustration = "";
                if (!isset($request->remove_img_illustration)) {

                    $ext_illustration = "";
                    $img_illustration = "";

                    if (isset($request->img_illustration)) {
                        $ext_illustration = $request->file('img_illustration')->getClientOriginalExtension();
                        $img_illustration = date('YmdHis') . 'illu' . Str::random(5) . '.' . $ext_illustration;
                    } else {
                        if ($request->current_img_illustration != "" && isset($request->current_img_illustration)) {
                            $img_illustration = $request->current_img_illustration;
                        } else {
                            $img_illustration = "";
                        }
                    }
                } else {
                    $img_illustration = "";
                }

                $ext_corr_act = "";
                $img_corr_act = "";
                if (!isset($request->remove_img_corrective_action)) {
                    $ext_corr_act = "";
                    if (isset($request->img_corrective_action)) {
                        $ext_corr_act = $request->file('img_corrective_action')->getClientOriginalExtension();
                    }
                    $img_corr_act = "";
                    if (isset($request->img_corrective_action)) {
                        $img_corr_act = date('YmdHis') . 'corract' . Str::random(5) . '.' . $ext_corr_act;
                    } else {
                        if ($request->current_img_corrective_action != "" && isset($request->current_img_corrective_action)) {
                            $img_corr_act = $request->current_img_corrective_action;
                        } else {
                            $img_corr_act = "";
                        }
                    }
                } else {
                    $img_corr_act = "";
                }

                // PDF Files
                $ext_pdf_illustration = "";
                $pdf_illustration = "";
                if (!isset($request->remove_pdf_illustration)) {

                    $ext_pdf_illustration = "";
                    $pdf_illustration = "";

                    if (isset($request->pdf_illustration)) {
                        $ext_pdf_illustration = $request->file('pdf_illustration')->getClientOriginalExtension();
                        $pdf_illustration = date('YmdHis') . 'illu' . Str::random(5) . '.' . $ext_pdf_illustration;
                    } else {
                        if ($request->current_pdf_illustration != "" && isset($request->current_pdf_illustration)) {
                            $pdf_illustration = $request->current_pdf_illustration;
                        } else {
                            $pdf_illustration = "";
                        }
                    }
                } else {
                    $pdf_illustration = "";
                }

                $ext_pdf_corr_act = "";
                $pdf_corr_act = "";
                if (!isset($request->remove_pdf_corrective_action)) {
                    $ext_pdf_corr_act = "";
                    if (isset($request->pdf_corrective_action)) {
                        $ext_pdf_corr_act = $request->file('pdf_corrective_action')->getClientOriginalExtension();
                    }
                    $pdf_corr_act = "";
                    if (isset($request->pdf_corrective_action)) {
                        $pdf_corr_act = date('YmdHis') . 'corract' . Str::random(5) . '.' . $ext_pdf_corr_act;
                    } else {
                        if ($request->current_pdf_corrective_action != "" && isset($request->current_pdf_corrective_action)) {
                            $pdf_corr_act = $request->current_pdf_corrective_action;
                        } else {
                            $pdf_corr_act = "";
                        }
                    }
                } else {
                    $pdf_corr_act = "";
                }


                Internal::on('mysql')->where('internal_audit_id', $request->internal_audit_id)
                    ->increment(
                        'update_version',
                        1,
                        [
                            'audit_type' => $request->audit_type,
                            'audit_date_from' => $request->audit_date_from,
                            'audit_date_to' => $request->audit_date_to,
                            'business_process' => $request->business_process,
                            'auditors' => $request->auditors,
                            'auditees' => $request->auditees,
                            'audit_report_control_no' => $request->audit_report_control_no,
                            'classification' => $request->classification,
                            'nc_control_no' => $request->nc_control_no,
                            'item_no' => $request->item_no,
                            // 'category_of_findings' => $request->category_of_findings,
                            'evaluation_item_id' => $request->evaluation_item_id,
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
                            'correction' => $request->correction,
                            'containment' => $request->containment,
                            'systematic' => $request->systematic,
                            'correction_commitment_date' => $request->correction_commitment_date,
                            'correction_person_in_charge' => $request->txt_correction_person_in_charge,
                            'containment_commitment_date' => $request->containment_commitment_date,
                            'containment_person_in_charge' => $request->txt_containment_person_in_charge,
                            'systematic_commitment_date' => $request->systematic_commitment_date,
                            'systematic_person_in_charge' => $request->txt_systematic_person_in_charge,
                            'improvement_action' => $request->improvement_plan,
                            'commitment_date' => $request->commitment_date,
                            'qad_approval' => $request_approval,
                            // 'request_approval' => $request_approval,
                            // 'responsible_department' => $request->responsible_department,
                            // 'person_in_charge' => $request->person_in_charge,
                            'corrective_action' => $request->corrective_action,
                            'img_corrective_action' => $img_corr_act,
                            'pdf_corrective_action' => $pdf_corr_act,
                            'audit_stat' => $request->audit_stat,
                            'sending_stat' => $request->sending_stat,
                            'last_updated_by' => $_SESSION["rapidx_user_id"],
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]
                    );

                $internals = Internal::on('mysql')
                    ->with(['internal_departments'])
                    ->get();

                InternalDepartments::on('mysql')->where('internal_audit_id', $request->internal_audit_id)
                    ->whereNotIn('responsible_department_id', $request->responsible_department)
                    ->increment(
                        'update_version',
                        1,
                        [
                            'internal_department_stat' => 2,
                            'updated_at' => date('Y-m-d H:i:s'),
                            'last_updated_by' => $_SESSION['rapidx_user_id'],
                        ]
                    );

                InternalDepartments::on('mysql')->where('internal_audit_id', $request->internal_audit_id)
                    ->whereIn('responsible_department_id', $request->responsible_department)
                    ->increment(
                        'update_version',
                        1,
                        [
                            'internal_department_stat' => 1,
                            'updated_at' => date('Y-m-d H:i:s'),
                            'last_updated_by' => $_SESSION['rapidx_user_id'],
                        ]
                    );

                for ($index = 0; $index < count($request->responsible_department); $index++) {
                    $internal_depts = InternalDepartments::on('mysql')
                        ->where('internal_audit_id', $request->internal_audit_id)
                        ->where('responsible_department_id', $request->responsible_department[$index])
                        ->get();

                    if (count($internal_depts) <= 0) {
                        InternalDepartments::insert([
                            'internal_audit_id' => $request->internal_audit_id,
                            'responsible_department_id' => $request->responsible_department[$index],
                            'internal_department_stat' => 1,
                            'created_by' => $_SESSION['rapidx_user_id'],
                            'last_updated_by' => $_SESSION['rapidx_user_id'],
                            'update_version' => 1,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }

                if (isset($request->person_in_charge)) {
                    InternalPersonInCharges::whereNotIn('person_in_charge_id', $request->person_in_charge)
                        ->where('internal_audit_id', $request->internal_audit_id)
                        ->delete();

                    for ($index = 0; $index < count($request->person_in_charge); $index++) {
                        $internal_per_in_charges = InternalPersonInCharges::where('internal_audit_id', $request->internal_audit_id)
                            ->where('person_in_charge_id', $request->person_in_charge[$index])
                            ->get();

                        if (count($internal_per_in_charges) <= 0) {
                            InternalPersonInCharges::insert([
                                'internal_audit_id' => $request->internal_audit_id,
                                'person_in_charge_id' => $request->person_in_charge[$index],
                                'created_by' => $_SESSION['rapidx_user_id'],
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                        }
                    }
                } else {
                    InternalPersonInCharges::where('internal_audit_id', $request->internal_audit_id)
                        ->delete();
                }

                if (isset($request->correction_person_in_charge)) {
                    InternalCorrPersonInCharges::whereNotIn('person_in_charge_id', $request->correction_person_in_charge)
                        ->where('internal_audit_id', $request->internal_audit_id)
                        ->delete();

                    for ($index = 0; $index < count($request->correction_person_in_charge); $index++) {
                        $internal_per_in_charges = InternalCorrPersonInCharges::where('internal_audit_id', $request->internal_audit_id)
                            ->where('person_in_charge_id', $request->correction_person_in_charge[$index])
                            ->get();

                        if (count($internal_per_in_charges) <= 0) {
                            InternalCorrPersonInCharges::insert([
                                'internal_audit_id' => $request->internal_audit_id,
                                'person_in_charge_id' => $request->correction_person_in_charge[$index],
                                'created_by' => $_SESSION['rapidx_user_id'],
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                        }
                    }
                } else {
                    InternalCorrPersonInCharges::where('internal_audit_id', $request->internal_audit_id)
                        ->delete();
                }

                if (isset($request->containment_person_in_charge)) {
                    InternalConPersonInCharges::whereNotIn('person_in_charge_id', $request->containment_person_in_charge)
                        ->where('internal_audit_id', $request->internal_audit_id)
                        ->delete();

                    for ($index = 0; $index < count($request->containment_person_in_charge); $index++) {
                        $internal_per_in_charges = InternalConPersonInCharges::where('internal_audit_id', $request->internal_audit_id)
                            ->where('person_in_charge_id', $request->containment_person_in_charge[$index])
                            ->get();

                        if (count($internal_per_in_charges) <= 0) {
                            InternalConPersonInCharges::insert([
                                'internal_audit_id' => $request->internal_audit_id,
                                'person_in_charge_id' => $request->containment_person_in_charge[$index],
                                'created_by' => $_SESSION['rapidx_user_id'],
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                        }
                    }
                } else {
                    InternalConPersonInCharges::where('internal_audit_id', $request->internal_audit_id)
                        ->delete();
                }

                if (isset($request->systematic_person_in_charge)) {
                    InternalSysPersonInCharges::whereNotIn('person_in_charge_id', $request->systematic_person_in_charge)
                        ->where('internal_audit_id', $request->internal_audit_id)
                        ->delete();

                    for ($index = 0; $index < count($request->systematic_person_in_charge); $index++) {
                        $internal_per_in_charges = InternalSysPersonInCharges::where('internal_audit_id', $request->internal_audit_id)
                            ->where('person_in_charge_id', $request->systematic_person_in_charge[$index])
                            ->get();

                        if (count($internal_per_in_charges) <= 0) {
                            InternalSysPersonInCharges::insert([
                                'internal_audit_id' => $request->internal_audit_id,
                                'person_in_charge_id' => $request->systematic_person_in_charge[$index],
                                'created_by' => $_SESSION['rapidx_user_id'],
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                        }
                    }
                } else {
                    InternalSysPersonInCharges::where('internal_audit_id', $request->tuv_audit_id)
                        ->delete();
                }

                DB::commit();

                if (!isset($request->remove_img_corrective_action)) {
                    if (isset($request->img_corrective_action)) {
                        if (!Storage::putFileAs('public/audit_result_images/internal', $request->file('img_corrective_action'), $img_corr_act)) {
                            return response()->json(['upload_result' => 0]);
                        }
                    }
                }
                if (!isset($request->remove_img_illustration)) {
                    if (isset($request->img_illustration)) {
                        if (!Storage::putFileAs('public/audit_result_images/internal', $request->file('img_illustration'), $img_illustration)) {
                            return response()->json(['upload_result' => 0]);
                        }
                    }
                }

                // PDF File
                if (!isset($request->remove_pdf_corrective_action)) {
                    if (isset($request->pdf_corrective_action)) {
                        if (!Storage::putFileAs('public/audit_result_pdf/internal', $request->file('pdf_corrective_action'), $pdf_corr_act)) {
                            return response()->json(['upload_result' => 0]);
                        }
                    }
                }
                if (!isset($request->remove_pdf_illustration)) {
                    if (isset($request->pdf_illustration)) {
                        if (!Storage::putFileAs('public/audit_result_pdf/internal', $request->file('pdf_illustration'), $pdf_illustration)) {
                            return response()->json(['upload_result' => 0]);
                        }
                    }
                }

                return response()->json(['result' => 1]);
            } catch (\Exeption $e) {
                DB::rollback();
                return response()->json(['result' => 0]);
            }
        } else {
            return response()->json(['result' => '0', 'error' => $validator->messages()]);
        }
    }

    public function close_internal_audit(Request $request)
    {
        session_start();
        date_default_timezone_set('Asia/Manila');

        DB::beginTransaction();

        try {
            Internal::on('mysql')->where('internal_audit_id', $request->internal_audit_id)
                ->increment(
                    'update_version',
                    1,
                    [
                        'audit_stat' => 3,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );
            DB::commit();
            return response()->json(['result' => "1"]);
        } catch (\Exception $e) {
            DB::rollback();
            // throw $e;
            return response()->json(['result' => "0"]);
        }
    }

    public function open_internal_audit(Request $request)
    {
        session_start();
        date_default_timezone_set('Asia/Manila');

        DB::beginTransaction();

        try {
            Internal::on('mysql')->where('internal_audit_id', $request->internal_audit_id)
                ->increment(
                    'update_version',
                    1,
                    [
                        'audit_stat' => 1,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );
            DB::commit();
            return response()->json(['result' => "1"]);
        } catch (\Exception $e) {
            DB::rollback();
            // throw $e;
            return response()->json(['result' => "0"]);
        }
    }

    public function done_internal_audit(Request $request)
    {
        session_start();
        date_default_timezone_set('Asia/Manila');

        DB::beginTransaction();

        try {
            Internal::on('mysql')->where('internal_audit_id', $request->internal_audit_id)
                ->increment(
                    'update_version',
                    1,
                    [
                        'sending_stat' => 2,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );
            // sending mail codes here...
            DB::commit();
            return response()->json(['result' => "1"]);
        } catch (\Exception $e) {
            DB::rollback();
            // throw $e;
            return response()->json(['result' => "0"]);
        }
    }

    public function pend_internal_audit(Request $request)
    {
        session_start();
        date_default_timezone_set('Asia/Manila');

        DB::beginTransaction();

        try {
            Internal::on('mysql')->where('internal_audit_id', $request->internal_audit_id)
                ->increment(
                    'update_version',
                    1,
                    [
                        'sending_stat' => 1,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );
            // sending mail codes here...
            DB::commit();
            return response()->json(['result' => "1"]);
        } catch (\Exception $e) {
            DB::rollback();
            // throw $e;
            return response()->json(['result' => "0"]);
        }
    }

    public function get_internal_audit_by_id(Request $request)
    {
        session_start();
        $internal_audits = Internal::on('mysql')
            ->with(
                [
                    'internal_departments' => function ($query) {
                        $query->where('internal_department_stat', 1);
                    }, 'internal_departments.departments',
                    'user_created_by',
                    'user_last_updated_by',
                    'person_in_charges',
                    'evaluation_item_info',
                    'person_in_charges.person_in_charge_record',
                    'internal_corr_per_in_charges',
                    'internal_con_per_in_charges',
                    'internal_sys_per_in_charges',
                    'internal_corr_per_in_charges.internal_corr_per_in_charge_record',
                    'internal_con_per_in_charges.internal_con_per_in_charge_record',
                    'internal_sys_per_in_charges.internal_sys_per_in_charge_record'
                ]
            )
            ->where('internal_audit_id', $request->internal_audit_id)
            ->get();

        return response()->json(['internal_audits' => $internal_audits]);
    }

    public function view_open_internal_audit_by_stat(Request $request)
    {
        session_start();
        $user_access = $request->user_access;

        $is_approver = false;
        $responsible_department[] = $_SESSION['rapidx_department_id'];
        $user_id = $_SESSION['rapidx_user_id'];

        $approvers = Approver::where('approver', $_SESSION["rapidx_user_id"])
            ->where('approver_stat', '1')
            ->get();

        if (count($approvers) > 0) {
            $is_approver = true;
            // for($index = 0; $index < count($approvers); $index++){
            //     $responsible_department[] = $approvers[$index]->department_id;
            // }

            $email_recipients = EmailRecipient::where('receiver', $_SESSION["rapidx_user_id"])->get();
            // return $email_recipients;
            if (count($email_recipients) > 0) {
                $responsible_department = collect($email_recipients)->flatten(1)->pluck('department_id')->flatten(1);
            }
        } else {
            $email_recipients = EmailRecipient::where('receiver', $_SESSION["rapidx_user_id"])->get();
            // return $email_recipients;
            if (count($email_recipients) > 0) {
                $responsible_department = collect($email_recipients)->flatten(1)->pluck('department_id')->flatten(1);
            }
        }

        $internals = Internal::on('mysql')
            ->with(
                ['internal_departments' => function ($query) use ($responsible_department) {
                    $query->where('internal_department_stat', 1);
                    // if($responsible_department != 0) {
                    $query->whereIn('responsible_department_id', $responsible_department);
                    // }
                }, 'internal_departments.departments']
            )
            ->withCount(['internal_departments' => function ($query) use ($responsible_department) {
                $query->where('internal_department_stat', 1);
                // if($responsible_department != 0) {
                $query->whereIn('responsible_department_id', $responsible_department);
                // }
            }])
            ->where('qad_approval', 1)
            ->having('internal_departments_count', '>', 0);

        if ($request->date_from != '0') {
            $internals = $internals->where('audit_date_from', '>=', $request->date_from);
        }

        if ($request->date_to != '0') {
            $internals = $internals->where('audit_date_to', '<=', $request->date_to);
        }

        if ($request->audit_category != '0' && $request->has('audit_category')) {
            $internals = $internals->where('audit_category', $request->audit_category);
        }

        if ($request->audit_type != '0' && $request->has('audit_type')) {
            $internals = $internals->where('audit_type', $request->audit_type);
        }

        // if($request->audit_type_pref_rep_ctrl_no != '0' && $request->has('audit_type_pref_rep_ctrl_no')) {
        //     $internals = $internals->where('audit_report_control_no', 'LIKE', $request->audit_type_pref_rep_ctrl_no . "%");
        // }

        if ($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
            $internals = $internals->where('purpose_of_audit', $request->purpose_of_audit);
        }

        if ($request->classification != '0' && $request->has('classification')) {
            $internals = $internals->where('classification', $request->classification);
        }

        if ($request->audit_stat != '0' && $request->has('audit_stat')) {
            $internals = $internals->where('audit_stat', $request->audit_stat);
        }

        if ($request->audit_report_control_no != '0' && $request->has('audit_report_control_no')) {
            $internals = $internals->where('audit_report_control_no', $request->audit_report_control_no);
        }

        if ($request->item_no != '0' && $request->has('item_no')) {
            $internals = $internals->where('item_no', $request->item_no);
        }

        if ($request->nc_control_no != '0' && $request->has('nc_control_no')) {
            $internals = $internals->where('nc_control_no', $request->nc_control_no);
        }

        if ($request->evaluation_item != null && $request->has('evaluation_item')) {
            $internals = $internals->where('evaluation_item_id', $request->evaluation_item);
        }

        if ($request->approval_stat != '0' && $request->has('approval_stat')) {
            if ($request->approval_stat == 1) {
                $internals = $internals->where(function ($query) {
                    $query->where('audit_stat', 2);
                    $query->where('o_s_approval', 0);
                });
            }
        }

        // if($request->evaluation_item != null && $request->has('evaluation_item')) {
        //     $internals = $internals->where('category_of_findings', $request->evaluation_item);
        // }

        $internals = $internals->where('internal_audit_stat', 1);

        $internals = $internals->get();

        return DataTables::of($internals)
            ->addColumn('label1', function ($internal) {
                $result = "";

                if ($internal->sending_stat == 1) {
                    $result .= '<span class="tag tag-warning">Pending</span>';
                } else {
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function ($internal) {
                $result = "";

                if ($internal->audit_stat == 1) {
                    $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
                } else if ($internal->audit_stat == 2) {
                    $result .= '<span class="tag tag-success">For Closed-Out</span>';
                } else {
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function ($internal) {
                $result = '';
                if ($internal->audit_date_from != $internal->audit_date_to) {
                    $result = Carbon::parse($internal->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($internal->audit_date_to)->toFormattedDateString();
                } else {
                    $result = Carbon::parse($internal->audit_date_from)->toFormattedDateString();
                }
                return $result;
            })
            ->addColumn('action1', function ($internal) use ($is_approver, $user_access) {
                $final = '';
                $final = '<button class="btn-actions btn btn-sm btn-info aViewIntAudit" internal-id="' . $internal->internal_audit_id . '" type="button" data-toggle="modal" data-target="#modalViewIntAudit" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="View" title="View"><i class="icon-file-text-o"></i></button> ';

                if ($internal->audit_stat == 1) {
                    $final .= '<button class="btn-actions btn btn-sm btn-success aEditInt" internal-id="' . $internal->internal_audit_id . '" type="button" data-toggle="modal" data-target="#modalEditIntAudit" data-keyboard="false" title="Edit"><i class="icon-edit2"></i></button> ';
                } else {
                    if ($is_approver) {
                        if ($internal->audit_stat == 2 && $internal->o_s_approval == 0) {
                            $final .= '<button class="btn-actions btn btn-sm btn-success aEditInt" internal-id="' . $internal->internal_audit_id . '" type="button" data-toggle="modal" data-target="#modalEditIntAudit" data-keyboard="false" title="Edit"><i class="icon-edit2"></i></button> ';
                        }
                    }
                }

                // Working, uncomment if will use
                if ($is_approver && $user_access == 5) {
                    if ($internal->o_s_approval == '0' && $internal->audit_stat == 2) {

                        $final .= '<button class="btn-actions btn btn-sm btn-primary aOSApprovalIntAudit" type="button" internal-id="' . $internal->internal_audit_id . '" o-s-approval="1" data-toggle="modal" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="Approve" title="Approve"><i class="icon-like"></i></button> ';

                        $final .= '<button class="btn-actions btn btn-sm btn-danger aOSApprovalIntAudit" type="button" internal-id="' . $internal->internal_audit_id . '" o-s-approval="0" data-toggle="modal" data-keyboard="false" data-toggle="tooltip" data-trigger="hover" data-placement="top" data-title="Disapprove" title="Approve"><i class="icon-thumbs-down"></i></button> ';
                    }
                }
                return $final;
            })
            ->addColumn('check_box_send', function ($internal) {
                $result = "";

                if ($internal->qad_approval == '0') {
                    $result = "disabled";
                }

                if ($internal->deadline_of_submission == null) {
                    $result = "disabled";
                }

                return '<center><input type="checkbox" class="chkSendIntAudit" internal-id="' . $internal->internal_audit_id . '" ' . $result . '></center>';
            })
            ->addColumn('approval_stat', function ($internal) {
                $result = "";

                if ($internal->audit_stat == 1 && $internal->qad_approval == 1) {
                    $result .= '<span class="tag tag-success">Approved By QAD</span>';
                } else if ($internal->audit_stat == 1 && $internal->qad_approval == 0) {
                    $result .= '<span class="tag tag-warning">For QAD Approval</span>';
                } else if ($internal->audit_stat == 2 && $internal->o_s_approval == 1) {
                    $result .= '<span class="tag tag-success">Approved By Section Head</span>';
                } else if ($internal->audit_stat == 2 && $internal->o_s_approval == 0) {
                    $result .= '<span class="tag tag-warning">For Section Head Approval</span>';
                } else {
                    $result .= '<span class="tag tag-success">Approved By QAD</span>';
                }

                return $result;
            })
            ->addColumn('raw_audit_type', function ($internal) {
                $result = "";

                if ($internal->audit_type == 1) {
                    $result .= 'EMS System';
                } else if ($internal->audit_type == 2) {
                    $result .= 'QMS System';
                } else if ($internal->audit_type == 3) {
                    $result .= 'Process';
                } else {
                    $result .= 'Product';
                }

                return $result;
            })
            ->rawColumns(['action1', 'label1', 'label2', 'formatted_audit_date', 'check_box_send', 'approval_stat', 'raw_audit_type'])
            ->make(true);
    }

    public function edit_open_internal_audit(Request $request)
    {
        date_default_timezone_set('Asia/Manila');
        session_start();

        $data = $request->all();

        $rules = [];

        if ($request->audit_stat == 2) {
            $rules = [
                'root_cause' => ['required'],
                'correction' => ['required'],
                'containment' => ['required'],
                'systematic' => ['required'],
                'correction_commitment_date' => ['required'],
                'txt_correction_person_in_charge' => ['required'],
                'containment_commitment_date' => ['required'],
                'txt_containment_person_in_charge' => ['required'],
                'systematic_commitment_date' => ['required'],
                'txt_systematic_person_in_charge' => ['required'],
                // 'improvement_plan' => ['required'],
                // 'commitment_date' => ['required'],
                // 'corrective_action' => ['required'], 
            ];
        }

        $validator = Validator::make($data, $rules);

        if ($validator->passes()) {
            DB::beginTransaction();
            try {

                Internal::where('internal_audit_id', $request->internal_audit_id)
                    ->increment(
                        'update_version',
                        1,
                        [
                            'root_cause' => $request->root_cause,
                            'correction' => $request->correction,
                            'containment' => $request->containment,
                            'systematic' => $request->systematic,
                            'correction_commitment_date' => $request->correction_commitment_date,
                            'correction_person_in_charge' => $request->txt_correction_person_in_charge,
                            'containment_commitment_date' => $request->containment_commitment_date,
                            'containment_person_in_charge' => $request->txt_containment_person_in_charge,
                            'systematic_commitment_date' => $request->systematic_commitment_date,
                            'systematic_person_in_charge' => $request->txt_systematic_person_in_charge,
                            'improvement_action' => $request->improvement_plan,
                            'commitment_date' => $request->commitment_date,
                            'corrective_action' => $request->corrective_action,
                            'audit_stat' => $request->audit_stat,
                            'last_updated_by' => $_SESSION["rapidx_user_id"],
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]
                    );

                DB::commit();
                return response()->json(['result' => 1]);
            } catch (\Exeption $e) {
                DB::rollback();
                return response()->json(['result' => 0]);
            }
        } else {
            return response()->json(['result' => '0', 'error' => $validator->messages()]);
        }
    }

    public function get_chart_internal_audit_by_stat(Request $request)
    {
        session_start();
        date_default_timezone_set('Asia/Manila');

        $year = $request->year;
        $month_from = 04;
        $month_to = 03;

        $date_from = date($year . '-' . $month_from . '-' . '01');
        $date_to = date(($year + 1) . '-' . $month_to . '-' . 't');

        $date_create_from = date_create($date_from);
        $date_create_to = date_create($date_to);

        $internals = Internal::with(
            ['internal_departments' => function ($query) {
                $query->where('internal_department_stat', 1);
            }, 'internal_departments.departments']
        )
            ->withCount(['internal_departments' => function ($query) {
                $query->where('internal_department_stat', 1);
            }])
            ->having('internal_departments_count', '>', 0)
            ->where('audit_date_from', '>=', $date_create_from)
            ->where('audit_date_to', '<=', $date_create_to)
            ->get();

        return response()->json(['internals' => $internals]);
    }

    public function view_internal_audit_history(Request $request)
    {
        session_start();

        $internals = Internal::on('mysql')->where('responsible_department', $_SESSION["rapidx_department_id"])
            ->where('audit_stat', 2)
            ->where('sending_stat', 2)
            ->get();

        return DataTables::of($internals)
            ->addColumn('label1', function ($internal) {
                $result = "";

                if ($internal->sending_stat == 1) {
                    $result .= '<span class="tag tag-warning">Pending</span>';
                } else {
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function ($internal) {
                $result = "";

                if ($internal->audit_stat == 1) {
                    $result .= '<span class="tag tag-warning">Open</span>';
                } else {
                    $result .= '<span class="tag tag-success">Closed</span>';
                }

                return $result;
            })
            ->addColumn('action1', function ($internal) {
                // $result = '<div class="btn-group">
                //           <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                //             Action
                //           </button>
                //           <div class="dropdown-menu dropdown-menu-right">
                //             <button class="dropdown-item aViewIntAudit" internal-id="' . $internal->internal_audit_id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalViewIntAudit" data-keyboard="false">View</button>';

                // $result .= '</div>
                //         </div>';



                return '<button class="btn btn-primary aViewIntAudit" internal-id="' . $internal->internal_audit_id . '" type="button" data-toggle="modal" data-target="#modalViewIntAudit" data-keyboard="false">View</button>';
            })
            ->rawColumns(['action1', 'label1', 'label2'])
            ->make(true);
    }

    public function view_report_internal_audit_by_stat(Request $request)
    {
        session_start();
        $responsible_department = $request->responsible_department;

        $internals = Internal::on('mysql')
            ->with(
                ['internal_departments' => function ($query) use ($responsible_department) {
                    $query->where('internal_department_stat', 1);
                    if ($responsible_department != 0) {
                        $query->whereIn('responsible_department_id', $responsible_department);
                    }
                }, 'internal_departments.departments', 'person_in_charges', 'person_in_charges.person_in_charge_record']
            )
            ->withCount(['internal_departments' => function ($query) use ($responsible_department) {
                $query->where('internal_department_stat', 1);
                if ($responsible_department != 0) {
                    $query->whereIn('responsible_department_id', $responsible_department);
                }
            }])
            ->having('internal_departments_count', '>', 0)->where('internal_audit_stat', 1);

        if ($request->date_from != '0') {
            $internals = $internals->where('audit_date_from', '>=', $request->date_from);
        }

        if ($request->date_to != '0') {
            $internals = $internals->where('audit_date_to', '<=', $request->date_to);
        }

        if ($request->audit_category != '0' && $request->has('audit_category')) {
            $internals = $internals->where('audit_category', $request->audit_category);
        }

        if ($request->audit_type != '0' && $request->has('audit_type')) {
            $internals = $internals->where('audit_type', $request->audit_type);
        }

        // if($request->audit_type_pref_rep_ctrl_no != '0' && $request->has('audit_type_pref_rep_ctrl_no')) {
        //     $internals = $internals->where('audit_report_control_no', 'LIKE', $request->audit_type_pref_rep_ctrl_no . "%");
        // }

        if ($request->purpose_of_audit != '0' && $request->has('purpose_of_audit')) {
            $internals = $internals->where('purpose_of_audit', $request->purpose_of_audit);
        }

        if ($request->classification != '0' && $request->has('classification')) {
            $internals = $internals->where('classification', $request->classification);
        }

        if ($request->audit_stat != '0' && $request->has('audit_stat')) {
            $internals = $internals->where('audit_stat', $request->audit_stat);
        }

        if ($request->audit_report_control_no != '0' && $request->has('audit_report_control_no')) {
            $internals = $internals->where('audit_report_control_no', $request->audit_report_control_no);
        }

        if ($request->item_no != '0' && $request->has('item_no')) {
            $internals = $internals->where('item_no', $request->item_no);
        }

        if ($request->nc_control_no != '0' && $request->has('nc_control_no')) {
            $internals = $internals->where('nc_control_no', $request->nc_control_no);
        }

        // if($request->evaluation_item != null && $request->has('evaluation_item')) {
        //     $internals = $internals->where('category_of_findings', $request->evaluation_item);
        // }

        $internals = $internals->get();

        return DataTables::of($internals)
            ->addColumn('label1', function ($internal) {
                $result = "";

                if ($internal->sending_stat == 1) {
                    $result .= '<span class="tag tag-warning">Pending</span>';
                } else {
                    $result .= '<span class="tag tag-success">Done</span>';
                }

                return $result;
            })
            ->addColumn('label2', function ($internal) {
                $result = "";

                if ($internal->audit_stat == 1) {
                    $result .= '<span class="tag tag-warning">For Improvement Plan</span>';
                } else if ($internal->audit_stat == 2) {
                    $result .= '<span class="tag tag-success">For Closed-Out</span>';
                } else {
                    $result .= '<span class="tag tag-primary">Closed</span>';
                }

                return $result;
            })
            ->addColumn('formatted_audit_date', function ($internal) {
                return Carbon::parse($internal->audit_date_from)->toFormattedDateString() . ' - ' . Carbon::parse($internal->audit_date_to)->toFormattedDateString();
            })
            ->rawColumns(['label1', 'label2', 'formatted_audit_date'])
            ->make(true);
    }

    public function change_internal_audit_stat(Request $request)
    {
        date_default_timezone_set('Asia/Manila');
        session_start();
        DB::beginTransaction();

        try {
            Internal::where('internal_audit_id', $request->internal_audit_id)
                ->increment(
                    'update_version',
                    1,
                    [
                        'internal_audit_stat' => $request->internal_audit_stat,
                        'last_updated_by' => $_SESSION["rapidx_user_id"],
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );
            DB::commit();
            return response()->json(['result' => "1"]);
        } catch (\Exception $e) {
            DB::rollback();
            // throw $e;
            return response()->json(['result' => "0"]);
        }
    }

    public function internal_qad_approval(Request $request)
    {
        date_default_timezone_set('Asia/Manila');
        session_start();
        DB::beginTransaction();

        $qad_approved_by = 0;
        if ($request->qad_approval == 1) {
            $qad_approved_by = $_SESSION["rapidx_user_id"];
        }

        try {
            Internal::where('internal_audit_id', $request->internal_audit_id)
                ->increment(
                    'update_version',
                    1,
                    [
                        'qad_approval' => $request->qad_approval,
                        'qad_approved_by' => $qad_approved_by,
                        'last_updated_by' => $_SESSION["rapidx_user_id"],
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );
            DB::commit();
            return response()->json(['result' => "1"]);
        } catch (\Exception $e) {
            DB::rollback();
            // throw $e;
            return response()->json(['result' => "0"]);
        }
    }

    public function internal_os_approval(Request $request)
    {
        date_default_timezone_set('Asia/Manila');
        session_start();
        DB::beginTransaction();

        $o_s_approved_by = 0;
        if ($request->o_s_approval == 1) {
            $o_s_approved_by = $_SESSION["rapidx_user_id"];
        }

        try {
            if ($request->o_s_approval == 1) {
                Internal::where('internal_audit_id', $request->internal_audit_id)
                    ->increment(
                        'update_version',
                        1,
                        [
                            'o_s_approval' => $request->o_s_approval,
                            'o_s_approved_by' => $o_s_approved_by,
                            'last_updated_by' => $_SESSION["rapidx_user_id"],
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]
                    );
            } else if ($request->o_s_approval == 0) {
                Internal::where('internal_audit_id', $request->internal_audit_id)
                    ->increment(
                        'update_version',
                        1,
                        [
                            'o_s_approval' => 0,
                            'o_s_approved_by' => 0,
                            'audit_stat' => 1,
                            'last_updated_by' => $_SESSION["rapidx_user_id"],
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]
                    );
            }
            DB::commit();
            return response()->json(['result' => "1"]);
        } catch (\Exception $e) {
            DB::rollback();
            // throw $e;
            return response()->json(['result' => "0"]);
        }
    }

    public function get_internal_unique_evaluation_items(Request $request)
    {
        session_start();
        $internal_audits = Internal::select('category_of_findings')->distinct()->get();

        return response()->json(['internal_audits' => $internal_audits]);
    }
}
