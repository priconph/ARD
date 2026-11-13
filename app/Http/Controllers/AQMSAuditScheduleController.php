<?php

namespace App\Http\Controllers;

// use App\Model\AQMSSchedule;
// use App\Model\AQMSScheduleYear;
// use App\Model\AQMSScheduleInternalAuditor;
// use App\Model\AQMSScheduleProcessOwner;

// use App\Model\AQMSSchedule;
// use App\Model\AQMSScheduleYear;
// use App\Model\AQMSScheduleInternalAuditor;
// use App\Model\AQMSScheduleProcessOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use DataTables;
use Carbon\Carbon;

use App\User;
use App\Model\AQMSProcess;
use App\Model\AQMSSchedule;
use App\Model\AQMSProcessOwner;
use App\Model\AQMSInternalAuditor;
use App\Model\AQMSYear;

class AQMSAuditScheduleController extends Controller
{
    //
    public function view_aqms_sched(Request $request){
        session_start();
        $aqms_schedule_year = AQMSScheduleYear::with([
                                'aqms_audit_schedule',
                                'aqms_audit_schedule.aqms_sched_process_owners',
                                'aqms_audit_schedule.aqms_sched_internal_auditors',
                                'aqms_audit_schedule.aqms_sched_internal_auditors.internal_auditor_info',
                                'aqms_audit_schedule.aqms_sched_process_owners.process_owner_info',
                            ])
                            ->get();

        return DataTables::of($aqms_schedule_year)
            ->addColumn('label1', function($aqms_schedule_year){
                $result = "";
                $plan_from = Carbon::parse($aqms_schedule_year->plan_from);
                $plan_to = Carbon::parse($aqms_schedule_year->plan_to);
                $actual_from = Carbon::parse($aqms_schedule_year->actual_from);
                $actual_to = Carbon::parse($aqms_schedule_year->actual_to);
                $reschedule_from = Carbon::parse($aqms_schedule_year->reschedule_from);
                $reschedule_to = Carbon::parse($aqms_schedule_year->reschedule_to);

                if($plan_from->isoFormat('M') >= 4 && $plan_from->isoFormat('M') <= 6 && $plan_from->isoFormat('YYYY') == $aqms_schedule_year->year){
                    $result .= '<span class="tag" style="background-color: #007bff;" title="Plan">' . $plan_from->toFormattedDateString() . ' - ' . $plan_to->toFormattedDateString() . '</span><br>';
                }

                if($aqms_schedule_year->actual_from != "" && $aqms_schedule_year->actual_to != "") {
                    if($actual_from->isoFormat('M') >= 4 && $actual_from->isoFormat('M') <= 6 && $actual_from->isoFormat('YYYY') == $aqms_schedule_year->year){
                        $result .= '<span class="tag tag-warning" title="Actual">' . $actual_from->toFormattedDateString() . ' - ' . $actual_to->toFormattedDateString() . '</span><br>';
                    }
                }

                if($aqms_schedule_year->reschedule_from != "" && $aqms_schedule_year->reschedule_to != "") {
                    if($reschedule_from->isoFormat('M') >= 4 && $reschedule_from->isoFormat('M') <= 6 && $reschedule_from->isoFormat('YYYY') == $aqms_schedule_year->year){
                        $result .= '<span class="tag tag-success" title="Reschedule">' . $reschedule_from->toFormattedDateString() . ' - ' . $reschedule_to->toFormattedDateString() . '</span><br>';
                    }
                }

                return $result;
            })
            ->addColumn('label2', function($aqms_schedule_year){
                $result = "";
                $plan_from = Carbon::parse($aqms_schedule_year->plan_from);
                $plan_to = Carbon::parse($aqms_schedule_year->plan_to);
                $actual_from = Carbon::parse($aqms_schedule_year->actual_from);
                $actual_to = Carbon::parse($aqms_schedule_year->actual_to);
                $reschedule_from = Carbon::parse($aqms_schedule_year->reschedule_from);
                $reschedule_to = Carbon::parse($aqms_schedule_year->reschedule_to);

                if($plan_from->isoFormat('M') >= 7 && $plan_from->isoFormat('M') <= 9 && $plan_from->isoFormat('YYYY') == $aqms_schedule_year->year){
                    $result .= '<span class="tag" style="background-color: #007bff;" title="Plan">' . $plan_from->toFormattedDateString() . ' - ' . $plan_to->toFormattedDateString() . '</span><br>';
                }

                if($aqms_schedule_year->actual_from != "" && $aqms_schedule_year->actual_to != "") {
                    if($actual_from->isoFormat('M') >= 7 && $actual_from->isoFormat('M') <= 9 && $actual_from->isoFormat('YYYY') == $aqms_schedule_year->year){
                        $result .= '<span class="tag tag-warning" title="Actual">' . $actual_from->toFormattedDateString() . ' - ' . $actual_to->toFormattedDateString() . '</span><br>';
                    }
                }

                if($aqms_schedule_year->reschedule_from != "" && $aqms_schedule_year->reschedule_to != "") {
                    if($reschedule_from->isoFormat('M') >= 7 && $reschedule_from->isoFormat('M') <= 9 && $reschedule_from->isoFormat('YYYY') == $aqms_schedule_year->year){
                        $result .= '<span class="tag tag-success" title="Reschedule">' . $reschedule_from->toFormattedDateString() . ' - ' . $reschedule_to->toFormattedDateString() . '</span><br>';
                    }
                }

                return $result;
            })
            ->addColumn('label3', function($aqms_schedule_year){
                $result = "";
                $plan_from = Carbon::parse($aqms_schedule_year->plan_from);
                $plan_to = Carbon::parse($aqms_schedule_year->plan_to);
                $actual_from = Carbon::parse($aqms_schedule_year->actual_from);
                $actual_to = Carbon::parse($aqms_schedule_year->actual_to);
                $reschedule_from = Carbon::parse($aqms_schedule_year->reschedule_from);
                $reschedule_to = Carbon::parse($aqms_schedule_year->reschedule_to);

                if($plan_from->isoFormat('M') >= 10 && $plan_from->isoFormat('M') <= 12 && $plan_from->isoFormat('YYYY') == $aqms_schedule_year->year){
                    $result .= '<span class="tag" style="background-color: #007bff;" title="Plan">' . $plan_from->toFormattedDateString() . ' - ' . $plan_to->toFormattedDateString() . '</span><br>';
                }

                if($aqms_schedule_year->actual_from != "" && $aqms_schedule_year->actual_to != "") {
                    if($actual_from->isoFormat('M') >= 10 && $actual_from->isoFormat('M') <= 12 && $actual_from->isoFormat('YYYY') == $aqms_schedule_year->year){
                        $result .= '<span class="tag tag-warning" title="Actual">' . $actual_from->toFormattedDateString() . ' - ' . $actual_to->toFormattedDateString() . '</span><br>';
                    }
                }

                if($aqms_schedule_year->reschedule_from != "" && $aqms_schedule_year->reschedule_to != "") {
                    if($reschedule_from->isoFormat('M') >= 10 && $reschedule_from->isoFormat('M') <= 12 && $reschedule_from->isoFormat('YYYY') == $aqms_schedule_year->year){
                        $result .= '<span class="tag tag-success" title="Reschedule">' . $reschedule_from->toFormattedDateString() . ' - ' . $reschedule_to->toFormattedDateString() . '</span><br>';
                    }
                }

                return $result;
            })
            ->addColumn('label4', function($aqms_schedule_year){
                $result = "";
                $plan_from = Carbon::parse($aqms_schedule_year->plan_from);
                $plan_to = Carbon::parse($aqms_schedule_year->plan_to);
                $actual_from = Carbon::parse($aqms_schedule_year->actual_from);
                $actual_to = Carbon::parse($aqms_schedule_year->actual_to);
                $reschedule_from = Carbon::parse($aqms_schedule_year->reschedule_from);
                $reschedule_to = Carbon::parse($aqms_schedule_year->reschedule_to);


                if($plan_from->isoFormat('M') >= 1 && $plan_from->isoFormat('M') <= 3 && $plan_from->isoFormat('YYYY') == ($aqms_schedule_year->year + 1)){
                    $result .= '<span class="tag" style="background-color: #007bff;" title="Plan">' . $plan_from->toFormattedDateString() . ' - ' . $plan_to->toFormattedDateString() . '</span><br>';
                }

                if($aqms_schedule_year->actual_from != "" && $aqms_schedule_year->actual_to != "") {
                    if($actual_from->isoFormat('M') >= 1 && $actual_from->isoFormat('M') <= 3 && $actual_from->isoFormat('YYYY') == ($aqms_schedule_year->year + 1)){
                        $result .= '<span class="tag tag-warning" title="Actual">' . $actual_from->toFormattedDateString() . ' - ' . $actual_to->toFormattedDateString() . '</span><br>';
                    }
                }

                if($aqms_schedule_year->reschedule_from != "" && $aqms_schedule_year->reschedule_to != "") {
                    if($reschedule_from->isoFormat('M') >= 1 && $reschedule_from->isoFormat('M') <= 3 && $reschedule_from->isoFormat('YYYY') == ($aqms_schedule_year->year + 1)){
                        $result .= '<span class="tag tag-success" title="Reschedule">' . $reschedule_from->toFormattedDateString() . ' - ' . $reschedule_to->toFormattedDateString() . '</span><br>';
                    }
                }

                return $result;
            })
            ->rawColumns(['label1', 'label2', 'label3', 'label4'])
            ->make(true);
    }

    function get_aqms_sched_between_year(Request $request){
        $aqms = AQMSScheduleYear::with([
                            'aqms_audit_schedule',
                            'aqms_audit_schedule.aqms_sched_process_owners',
                            'aqms_audit_schedule.aqms_sched_internal_auditors',
                            'aqms_audit_schedule.aqms_sched_internal_auditors.internal_auditor_info',
                            'aqms_audit_schedule.aqms_sched_process_owners.process_owner_info',
                        ])
                        ->whereBetween('year', [$request->year_from, $request->year_to])
                        ->get();

        return response()->json(['aqms' => $aqms]);
    }

    public function add_aqms_sched(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        $data = $request->all();

        // return $data;

        $validator = Validator::make($data, [
            'audit_schedule_detail_id' => ['required'],
            'organizational_unit' => ['required', 'string'],
            'process_owners' => ['required'],
            'internal_auditors' => ['required'],
            'year' => ['required'],
            'plan_from' => ['required'],
            'plan_to' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => '0', 'error' => $validator->messages()]);
        }
        else{
            DB::beginTransaction();

            try{
                $aqms_sched_id = AQMSSchedule::insertGetId([
                    'audit_schedule_detail_id' => $request->audit_schedule_detail_id,
                    'organizational_unit' => $request->organizational_unit,
                    'aqms_audit_schedule_stat' => 1,
                    'update_version' => 1,
                    'created_by' => $_SESSION['rapidx_user_id'],
                    'last_updated_by' => $_SESSION['rapidx_user_id'],
                    'updated_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                for($index = 0; $index < count($request->year); $index++) {
                    AQMSScheduleYear::insert([
                        'aqms_schedule_id' => $aqms_sched_id,
                        'year' => $request->year[$index],
                        // 'quarter' => $request->quarter[$index],
                        'plan_from' => $request->plan_from[$index],
                        'plan_to' => $request->plan_to[$index],
                        'actual_from' => $request->actual_from[$index],
                        'actual_to' => $request->actual_to[$index],
                        'reschedule_from' => $request->reschedule_from[$index],
                        'reschedule_to' => $request->reschedule_to[$index],
                        'status' => 1,
                        'update_version' => 1,
                        'created_by' => $_SESSION['rapidx_user_id'],
                        'last_updated_by' => $_SESSION['rapidx_user_id'],
                        'updated_at' => date('Y-m-d H:i:s'),
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }

                for($index = 0; $index < count($request->process_owners); $index++) {
                    AQMSScheduleProcessOwner::insert([
                        'aqms_schedule_id' => $aqms_sched_id,
                        'process_owner_id' => $request->process_owners[$index],
                    ]);
                }

                for($index = 0; $index < count($request->internal_auditors); $index++) {
                    AQMSScheduleInternalAuditor::insert([
                        'aqms_schedule_id' => $aqms_sched_id,
                        'internal_auditor_id' => $request->internal_auditors[$index],
                    ]);
                }

                DB::commit();
                return response()->json(['result' => "1"]);
            }
            catch(\Exception $e) {
                DB::rollback();
                // throw $e;
                return response()->json(['result' => $e]);
            }
        }
    }

    public function edit_aqms_sched(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        $data = $request->all();

        // return response()->json(['result' => $request->internal_auditors]);

        $validator = Validator::make($data, [
            'organizational_unit' => ['required', 'string'],
            'process_owners' => ['required'],
            'internal_auditors' => ['required'],
            'year' => ['required'],
            'quarter' => ['required'],
            'plan_from' => ['required'],
            'plan_to' => ['required'],
        ]);

        // return $request->all();

        if ($validator->fails()) {
            return response()->json(['result' => '0', 'error' => $validator->messages()]);
        }
        else{
            $process_owners = '';
            for($index = 0; $index < count($request->process_owners); $index++) {
                $process_owners .= $request->process_owners[$index] . ',';
            }

            $process_owners = substr_replace($process_owners, "", -1);

            $internal_auditors = '';
            for($index = 0; $index < count($request->internal_auditors); $index++) {
                $internal_auditors .= $request->internal_auditors[$index] . ',';
            }

            $internal_auditors = substr_replace($internal_auditors, "", -1);
            DB::beginTransaction();

            try{
                AQMSSchedule::where('aqms_audit_schedule_id', $request->aqms_audit_schedule_id)
                    ->increment('update_version', 1,
                    [
                        'organizational_unit' => $request->organizational_unit,
                        'process_owners' => $process_owners,
                        'internal_auditors' => $internal_auditors,
                        'last_updated_by' => $_SESSION['rapidx_user_id'],
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );


                if(isset($request->new_year)) {
                    for($index = 0; $index < count($request->new_year); $index++) {
                        AQMSScheduleYear::insert([
                            'aqms_audit_schedule_id' => $request->aqms_audit_schedule_id,
                            'year' => $request->new_year[$index],
                            'quarter' => $request->new_quarter[$index],
                            'plan_from' => $request->new_plan_from[$index],
                            'plan_to' => $request->new_plan_to[$index],
                            'actual_from' => $request->new_actual_from[$index],
                            'actual_to' => $request->new_actual_to[$index],
                            'reschedule_from' => $request->new_reschedule_from[$index],
                            'reschedule_to' => $request->new_reschedule_to[$index],
                            'aqms_audit_schedule_year_stat' => 1,
                            'update_version' => 1,
                            'created_by' => $_SESSION['rapidx_user_id'],
                            'last_updated_by' => $_SESSION['rapidx_user_id'],
                            'updated_at' => date('Y-m-d H:i:s'),
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                    }
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
    }

    public function get_aqms_sched_by_id(Request $request) {
        session_start();

        $aqms_schedules = AQMSSchedule::where('audit_schedule_detail_id', $request->id)
            ->with([
                'aqms_sched_years',
                'aqms_sched_internal_auditors',
                'aqms_sched_process_owners',
                'aqms_sched_internal_auditors.internal_auditor_info',
                'aqms_sched_process_owners.process_owner_info',
            ])
            ->get();

        $aqms_min_years = [];
        foreach ($aqms_schedules as $key => $value) {
            $aqms_min_years[] = collect($value->aqms_sched_years)->min('year');
        }
        $aqms_min_year = collect($aqms_min_years)->min();

        $aqms_max_years = [];
        foreach ($aqms_schedules as $key => $value) {
            $aqms_max_years[] = collect($value->aqms_sched_years)->max('year');
        }
        $aqms_max_year = collect($aqms_max_years)->max();

        return response()->json(['aqms_schedules' => $aqms_schedules, 'aqms_min_year' => $aqms_min_year, 'aqms_max_year' => $aqms_max_year]);
    }

    public function archive_aqms_sched(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        DB::beginTransaction();

        try{
            AQMSSchedule::where('aqms_audit_schedule_id', $request->aqms_audit_schedule_id)
                ->increment('update_version', 1,
                [
                    'aqms_audit_schedule_stat' => 2,
                    'last_updated_by' => $_SESSION['rapidx_user_id'],
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

    public function restore_aqms_sched(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        DB::beginTransaction();

        try{
            AQMSSchedule::where('aqms_audit_schedule_id', $request->aqms_audit_schedule_id)
                ->increment('update_version', 1,
                [
                    'aqms_audit_schedule_stat' => 1,
                    'last_updated_by' => $_SESSION['rapidx_user_id'],
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

    // Start of new Codes 2020-05-09
    public function aqms_save_process(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        $data = $request->all();

        // Add
        if(!isset($request->process_id)){

            $rules = [
                'audit_schedule_id' => 'required',
                'no' => 'required',
            ];

            $validator = Validator::make($data, $rules);

            if ($validator->fails()) {
                return response()->json(['result' => '0', 'error' => $validator->messages()]);
            }
            else{

                try{
                    AQMSProcess::insert([
                        'audit_schedule_id' => $request->audit_schedule_id,
                        'process' => $request->process,
                        'no' => $request->no,
                        'status' => 1,
                        'created_by' => $_SESSION['rapidx_user_id'],
                        'last_updated_by' => $_SESSION['rapidx_user_id'],
                        'updated_at' => date('Y-m-d H:i:s'),
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);

                    return response()->json(['result' => 1, 'error' => null]);
                }
                catch(\Exception $e) {
                    // throw $e;
                    return response()->json(['result' => 0, 'error' => $e]);
                }
            }
        }
        // Edit
        else{

            $rules = [
                'process_id' => 'required',
                'audit_schedule_id' => 'required',
                'no' => 'required',
            ];

            $validator = Validator::make($data, $rules);

            if ($validator->fails()) {
                return response()->json(['result' => '0', 'error' => $validator->messages()]);
            }
            else{
                try{
                    AQMSProcess::where('id', $request->process_id)
                    ->update([
                        'process' => $request->process,
                        'no' => $request->no,
                        'last_updated_by' => $_SESSION['rapidx_user_id'],
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                    return response()->json(['result' => 1, 'error' => null]);
                }
                catch(\Exception $e) {
                    // throw $e;
                    return response()->json(['result' => 0, 'error' => $e]);
                }
            }
        }
    }

    public function get_aqms_sched_by_audit_schedule_id(Request $request) {
        session_start();

        $aqms_schedules = AQMSProcess::where('audit_schedule_id', $request->audit_schedule_id);
        $aqms_schedules = $aqms_schedules->orderBy('no', 'asc');
        if(isset($request->status)){
            $aqms_schedules = $aqms_schedules->where('status', $request->status);
        }
        $aqms_schedules = $aqms_schedules->with([
                                'aqms_schedule_details',
                                'aqms_schedule_details.aqms_year_details' => function($query){
                                    $query->where('status', 1);
                                },
                                'aqms_schedule_details.process_owner_details',
                                'aqms_schedule_details.process_owner_details.process_owner_info',
                                'aqms_schedule_details.internal_auditor_details.internal_auditor_info',
                            ])
                            ->get();

        $aqms_years = [];
        for($index = 0; $index < count($aqms_schedules); $index++){
            if(count($aqms_schedules) > 0){
                for($index2 = 0; $index2 < count($aqms_schedules[$index]->aqms_schedule_details); $index2++){
                    if(count($aqms_schedules[$index]->aqms_schedule_details) > 0){
                        for($index3 = 0; $index3 < count($aqms_schedules[$index]->aqms_schedule_details[$index2]->aqms_year_details); $index3++){
                            $aqms_years[] = $aqms_schedules[$index]->aqms_schedule_details[$index2]->aqms_year_details;
                        }
                    }
                }
            }
        }

        // return collect($aqms_years)->flatten(1)->pluck('year')->max();
        $aqms_min_year = 0;
        $aqms_max_year = 0;

        $aqms_min_year = collect($aqms_years)->flatten(1)->pluck('year')->min();
        $aqms_max_year = collect($aqms_years)->flatten(1)->pluck('year')->max();

        return response()->json(['aqms_schedules' => $aqms_schedules, 'aqms_min_year' => $aqms_min_year, 'aqms_max_year' => $aqms_max_year]);
    }

    public function get_aqms_process_by_id(Request $request) {
        session_start();

        $aqms_process = AQMSProcess::where('id', $request->aqms_process_id)->first();

        return response()->json(['aqms_process' => $aqms_process]);
    }

    public function aqms_save_process_details(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        $data = $request->all();
        // Add
        if(!isset($request->aqms_schedule_id)){

            $rules = [
                'process_id' => 'required',
                'process_owners' => 'required',
                'internal_auditors' => 'required',
            ];

            $validator = Validator::make($data, $rules);

            if ($validator->fails()) {
                return response()->json(['result' => '0', 'error' => $validator->messages()]);
            }
            else{
                DB::beginTransaction();
                try{
                    $aqms_schedule_id = AQMSSchedule::insertGetId([
                        'process_id' => $request->process_id,
                        'status' => 1,
                        'created_by' => $_SESSION['rapidx_user_id'],
                        'last_updated_by' => $_SESSION['rapidx_user_id'],
                        'updated_at' => date('Y-m-d H:i:s'),
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);

                    for($index = 0; $index < count($request->process_owners); $index++) {
                        AQMSProcessOwner::insert([
                            'aqms_schedule_id' => $aqms_schedule_id,
                            'process_owner_id' => $request->process_owners[$index],
                        ]);
                    }

                    for($index = 0; $index < count($request->internal_auditors); $index++) {
                        AQMSInternalAuditor::insert([
                            'aqms_schedule_id' => $aqms_schedule_id,
                            'internal_auditor_id' => $request->internal_auditors[$index],
                        ]);
                    }

                    DB::commit();
                    return response()->json(['result' => 1, 'error' => null]);
                }
                catch(\Exception $e) {
                    // throw $e;
                    DB::rollback();
                    return response()->json(['result' => 0, 'error' => $e]);
                }
            }
        }
        // Edit
        else{

            $rules = [
                'aqms_schedule_id' => 'required',
                'process_owners' => 'required',
                'internal_auditors' => 'required',
            ];

            $validator = Validator::make($data, $rules);

            if ($validator->fails()) {
                return response()->json(['result' => '0', 'error' => $validator->messages()]);
            }
            else{
                DB::beginTransaction();
                try{
                    AQMSProcessOwner::where('aqms_schedule_id', $request->aqms_schedule_id)
                            ->whereNotIn('process_owner_id', $request->process_owners)
                            ->delete();

                    for($index = 0; $index < count($request->process_owners); $index++) {
                        $process_owners = AQMSProcessOwner::where('aqms_schedule_id', $request->aqms_schedule_id)
                            ->where('process_owner_id', $request->process_owners[$index])
                            ->get();

                        if(count($process_owners) <= 0) {
                            AQMSProcessOwner::insert([
                                'aqms_schedule_id' => $request->aqms_schedule_id,
                                'process_owner_id' => $request->process_owners[$index],
                            ]);
                        }

                    }

                    AQMSInternalAuditor::where('aqms_schedule_id', $request->aqms_schedule_id)
                            ->whereNotIn('internal_auditor_id', $request->internal_auditors)
                            ->delete();

                    for($index = 0; $index < count($request->internal_auditors); $index++) {
                        $internal_auditors = AQMSInternalAuditor::where('aqms_schedule_id', $request->aqms_schedule_id)
                            ->where('internal_auditor_id', $request->internal_auditors[$index])
                            ->get();

                        if(count($internal_auditors) <= 0) {
                            AQMSInternalAuditor::insert([
                                'aqms_schedule_id' => $request->aqms_schedule_id,
                                'internal_auditor_id' => $request->internal_auditors[$index],
                            ]);
                        }

                    }

                    DB::commit();
                    return response()->json(['result' => 1, 'error' => null]);
                }
                catch(\Exception $e) {
                    // throw $e;
                    DB::rollback();
                    return response()->json(['result' => 0, 'error' => $e]);
                }
            }
        }
    }

    public function get_aqms_process_details_by_id(Request $request) {
        session_start();

        $aqms_process_details = AQMSSchedule::where('id', $request->audit_schedule_id)
                                ->with([
                                    'aqms_process_info',
                                    'process_owner_details',
                                    'internal_auditor_details',
                                    'aqms_year_details' => function($query){
                                        $query->where('status', 1);
                                        $query->orderBy('year', 'asc');
                                    },
                                ])
                                ->first();

        return response()->json(['aqms_process_details' => $aqms_process_details]);
    }

    public function view_aqms_years_by_schedule_id(Request $request) {
        session_start();

        $aqms_years = AQMSYear::where('aqms_schedule_id', $request->aqms_schedule_id)
                                ->orderBy('year', 'asc')
                                ->get();

        return DataTables::of($aqms_years)
            ->addColumn('raw_action', function($row){
                
                $result = '<center><div class="btn-group">
                          <button type="button" class="btn btn-primary dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-gear"></i>
                          </button>
                          <div class="dropdown-menu dropdown-menu-left">';

                if($row->status == 1){
                $result .= '<button class="dropdown-item aEditYear" year-id="' . $row->id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#mdlSaveYear">Edit</button>';
                    
                    $result .= '<button class="dropdown-item aYearAction" year-id="' . $row->id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" status="2" data-target="#mdlYearAction">Archive</button>';
                }
                else{
                    $result .= '<button class="dropdown-item aYearAction" year-id="' . $row->id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" status="1" data-target="#mdlYearAction">Restore</button>';
                }

                $result .= '</div></div></center>';

                return $result;
            })
            ->addColumn('raw_status', function($row){
                $result = "";

                if($row->status == 1){
                    $result .= '<span class="tag tag-success">Active</span>';
                }
                else{
                    $result .= '<span class="tag tag-danger">Archived</span>';
                }

                return $result;
            })
            ->addColumn('raw_plan', function($row){
                $result = "";


                if($row->start_plan == $row->end_plan){
                    $result = $row->start_plan;
                }
                else{
                    $result = $row->start_plan . ' <br> ' . $row->end_plan;
                }

                return $result;
            })
            ->addColumn('raw_actual', function($row){
                $result = "";


                if($row->start_actual == $row->end_actual){
                    $result = $row->start_actual;
                }
                else{
                    $result = $row->start_actual . ' <br> ' . $row->end_actual;
                }

                return $result;
            })
            ->addColumn('raw_reschedule', function($row){
                $result = "";


                if($row->start_reschedule == $row->end_reschedule){
                    $result = $row->start_reschedule;
                }
                else{
                    $result = $row->start_reschedule . ' <br> ' . $row->end_reschedule;
                }

                return $result;
            })
            ->rawColumns(['raw_action', 'raw_status', 'raw_plan', 'raw_actual', 'raw_reschedule'])
            ->make(true);
    }

    public function aqms_save_year(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        $data = $request->all();

        // Add
        if(!isset($request->year_id)){

            $rules = [
                'aqms_schedule_id' => 'required',
                'year' => 'required',
            ];

            $validator = Validator::make($data, $rules);

            if ($validator->fails()) {
                return response()->json(['result' => '0', 'error' => $validator->messages()]);
            }
            else{

                try{
                    AQMSYear::insert([
                        'aqms_schedule_id' => $request->aqms_schedule_id,
                        'year' => $request->year,
                        'start_plan' => $request->start_plan,
                        'end_plan' => $request->end_plan,
                        'start_actual' => $request->start_actual,
                        'end_actual' => $request->end_actual,
                        'start_reschedule' => $request->start_reschedule,
                        'end_reschedule' => $request->end_reschedule,
                        'status' => 1,
                        'created_by' => $_SESSION['rapidx_user_id'],
                        'last_updated_by' => $_SESSION['rapidx_user_id'],
                        'updated_at' => date('Y-m-d H:i:s'),
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);

                    return response()->json(['result' => 1, 'error' => null]);
                }
                catch(\Exception $e) {
                    // throw $e;
                    return response()->json(['result' => 0, 'error' => $e]);
                }
            }
        }
        // Edit
        else{

            $rules = [
                'year_id' => 'required',
                'aqms_schedule_id' => 'required',
                'year' => 'required',
            ];

            $validator = Validator::make($data, $rules);

            if ($validator->fails()) {
                return response()->json(['result' => '0', 'error' => $validator->messages()]);
            }
            else{
                try{
                    AQMSYear::where('id', $request->year_id)
                    ->update([
                        'year' => $request->year,
                        'start_plan' => $request->start_plan,
                        'end_plan' => $request->end_plan,
                        'start_actual' => $request->start_actual,
                        'end_actual' => $request->end_actual,
                        'start_reschedule' => $request->start_reschedule,
                        'end_reschedule' => $request->end_reschedule,
                        'last_updated_by' => $_SESSION['rapidx_user_id'],
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                    return response()->json(['result' => 1, 'error' => null]);
                }
                catch(\Exception $e) {
                    // throw $e;
                    return response()->json(['result' => 0, 'error' => $e]);
                }
            }
        }
    }

    public function get_aqms_year_by_id(Request $request) {
        session_start();

        $aqms_year = AQMSYear::where('id', $request->aqms_year_id)
                                ->first();

        return response()->json(['aqms_year' => $aqms_year]);
    }
}
