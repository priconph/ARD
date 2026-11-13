<?php

namespace App\Http\Controllers;

use App\User;
use App\Model\Department;
// use App\Model\EMSAuditSchedule;
// use App\Model\EMSAuditScheduleYear;
// use App\Model\EMSSchedInternalAuditors;
// use App\Model\EMSSchedProcessOwners;
// use App\Model\EMSSchedAreas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use DataTables;
use Carbon\Carbon;

use App\Model\EMSProcess;
use App\Model\EMSSchedule;
use App\Model\EMSProcessOwner;
use App\Model\EMSInternalAuditor;
use App\Model\EMSArea;
use App\Model\EMSYear;

class EMSAuditScheduleController extends Controller
{
    //
    public function view_ems_sched(Request $request){
        session_start();
        $ems_schedule_year = EMSAuditScheduleYear::with([
                                'ems_audit_schedule',
                                'ems_audit_schedule.ems_sched_process_owners',
                                'ems_audit_schedule.ems_sched_internal_auditors',
                                'ems_audit_schedule.ems_sched_areas',
                                'ems_audit_schedule.ems_sched_internal_auditors.internal_auditor_info',
                                'ems_audit_schedule.ems_sched_process_owners.process_owner_info',
                                'ems_audit_schedule.ems_sched_areas.area_info',
                            ])
                            ->get();

        return DataTables::of($ems_schedule_year)
            ->addColumn('label1', function($ems_schedule_year){
                $result = "";
                $plan_from = Carbon::parse($ems_schedule_year->plan_from);
                $plan_to = Carbon::parse($ems_schedule_year->plan_to);
                $actual_from = Carbon::parse($ems_schedule_year->actual_from);
                $actual_to = Carbon::parse($ems_schedule_year->actual_to);
                $reschedule_from = Carbon::parse($ems_schedule_year->reschedule_from);
                $reschedule_to = Carbon::parse($ems_schedule_year->reschedule_to);

                if($plan_from->isoFormat('M') >= 4 && $plan_from->isoFormat('M') <= 6 && $plan_from->isoFormat('YYYY') == $ems_schedule_year->year){
                    $result .= '<span class="tag" style="background-color: #007bff;" title="Plan">' . $plan_from->toFormattedDateString() . ' - ' . $plan_to->toFormattedDateString() . '</span><br>';
                }

                if($ems_schedule_year->actual_from != "" && $ems_schedule_year->actual_to != "") {
                    if($actual_from->isoFormat('M') >= 4 && $actual_from->isoFormat('M') <= 6 && $actual_from->isoFormat('YYYY') == $ems_schedule_year->year){
                        $result .= '<span class="tag tag-warning" title="Actual">' . $actual_from->toFormattedDateString() . ' - ' . $actual_to->toFormattedDateString() . '</span><br>';
                    }
                }

                if($ems_schedule_year->reschedule_from != "" && $ems_schedule_year->reschedule_to != "") {
                    if($reschedule_from->isoFormat('M') >= 4 && $reschedule_from->isoFormat('M') <= 6 && $reschedule_from->isoFormat('YYYY') == $ems_schedule_year->year){
                        $result .= '<span class="tag tag-success" title="Reschedule">' . $reschedule_from->toFormattedDateString() . ' - ' . $reschedule_to->toFormattedDateString() . '</span><br>';
                    }
                }

                return $result;
            })
            ->addColumn('label2', function($ems_schedule_year){
                $result = "";
                $plan_from = Carbon::parse($ems_schedule_year->plan_from);
                $plan_to = Carbon::parse($ems_schedule_year->plan_to);
                $actual_from = Carbon::parse($ems_schedule_year->actual_from);
                $actual_to = Carbon::parse($ems_schedule_year->actual_to);
                $reschedule_from = Carbon::parse($ems_schedule_year->reschedule_from);
                $reschedule_to = Carbon::parse($ems_schedule_year->reschedule_to);

                if($plan_from->isoFormat('M') >= 7 && $plan_from->isoFormat('M') <= 9 && $plan_from->isoFormat('YYYY') == $ems_schedule_year->year){
                    $result .= '<span class="tag" style="background-color: #007bff;" title="Plan">' . $plan_from->toFormattedDateString() . ' - ' . $plan_to->toFormattedDateString() . '</span><br>';
                }

                if($ems_schedule_year->actual_from != "" && $ems_schedule_year->actual_to != "") {
                    if($actual_from->isoFormat('M') >= 7 && $actual_from->isoFormat('M') <= 9 && $actual_from->isoFormat('YYYY') == $ems_schedule_year->year){
                        $result .= '<span class="tag tag-warning" title="Actual">' . $actual_from->toFormattedDateString() . ' - ' . $actual_to->toFormattedDateString() . '</span><br>';
                    }
                }

                if($ems_schedule_year->reschedule_from != "" && $ems_schedule_year->reschedule_to != "") {
                    if($reschedule_from->isoFormat('M') >= 7 && $reschedule_from->isoFormat('M') <= 9 && $reschedule_from->isoFormat('YYYY') == $ems_schedule_year->year){
                        $result .= '<span class="tag tag-success" title="Reschedule">' . $reschedule_from->toFormattedDateString() . ' - ' . $reschedule_to->toFormattedDateString() . '</span><br>';
                    }
                }

                return $result;
            })
            ->addColumn('label3', function($ems_schedule_year){
                $result = "";
                $plan_from = Carbon::parse($ems_schedule_year->plan_from);
                $plan_to = Carbon::parse($ems_schedule_year->plan_to);
                $actual_from = Carbon::parse($ems_schedule_year->actual_from);
                $actual_to = Carbon::parse($ems_schedule_year->actual_to);
                $reschedule_from = Carbon::parse($ems_schedule_year->reschedule_from);
                $reschedule_to = Carbon::parse($ems_schedule_year->reschedule_to);

                if($plan_from->isoFormat('M') >= 10 && $plan_from->isoFormat('M') <= 12 && $plan_from->isoFormat('YYYY') == $ems_schedule_year->year){
                    $result .= '<span class="tag" style="background-color: #007bff;" title="Plan">' . $plan_from->toFormattedDateString() . ' - ' . $plan_to->toFormattedDateString() . '</span><br>';
                }

                if($ems_schedule_year->actual_from != "" && $ems_schedule_year->actual_to != "") {
                    if($actual_from->isoFormat('M') >= 10 && $actual_from->isoFormat('M') <= 12 && $actual_from->isoFormat('YYYY') == $ems_schedule_year->year){
                        $result .= '<span class="tag tag-warning" title="Actual">' . $actual_from->toFormattedDateString() . ' - ' . $actual_to->toFormattedDateString() . '</span><br>';
                    }
                }

                if($ems_schedule_year->reschedule_from != "" && $ems_schedule_year->reschedule_to != "") {
                    if($reschedule_from->isoFormat('M') >= 10 && $reschedule_from->isoFormat('M') <= 12 && $reschedule_from->isoFormat('YYYY') == $ems_schedule_year->year){
                        $result .= '<span class="tag tag-success" title="Reschedule">' . $reschedule_from->toFormattedDateString() . ' - ' . $reschedule_to->toFormattedDateString() . '</span><br>';
                    }
                }

                return $result;
            })
            ->addColumn('label4', function($ems_schedule_year){
                $result = "";
                $plan_from = Carbon::parse($ems_schedule_year->plan_from);
                $plan_to = Carbon::parse($ems_schedule_year->plan_to);
                $actual_from = Carbon::parse($ems_schedule_year->actual_from);
                $actual_to = Carbon::parse($ems_schedule_year->actual_to);
                $reschedule_from = Carbon::parse($ems_schedule_year->reschedule_from);
                $reschedule_to = Carbon::parse($ems_schedule_year->reschedule_to);


                if($plan_from->isoFormat('M') >= 1 && $plan_from->isoFormat('M') <= 3 && $plan_from->isoFormat('YYYY') == ($ems_schedule_year->year + 1)){
                    $result .= '<span class="tag" style="background-color: #007bff;" title="Plan">' . $plan_from->toFormattedDateString() . ' - ' . $plan_to->toFormattedDateString() . '</span><br>';
                }

                if($ems_schedule_year->actual_from != "" && $ems_schedule_year->actual_to != "") {
                    if($actual_from->isoFormat('M') >= 1 && $actual_from->isoFormat('M') <= 3 && $actual_from->isoFormat('YYYY') == ($ems_schedule_year->year + 1)){
                        $result .= '<span class="tag tag-warning" title="Actual">' . $actual_from->toFormattedDateString() . ' - ' . $actual_to->toFormattedDateString() . '</span><br>';
                    }
                }

                if($ems_schedule_year->reschedule_from != "" && $ems_schedule_year->reschedule_to != "") {
                    if($reschedule_from->isoFormat('M') >= 1 && $reschedule_from->isoFormat('M') <= 3 && $reschedule_from->isoFormat('YYYY') == ($ems_schedule_year->year + 1)){
                        $result .= '<span class="tag tag-success" title="Reschedule">' . $reschedule_from->toFormattedDateString() . ' - ' . $reschedule_to->toFormattedDateString() . '</span><br>';
                    }
                }

                return $result;
            })
            ->addColumn('blank', function($ems_schedule_year){
                return '';
            })
            ->rawColumns(['label1', 'label2', 'label3', 'label4', 'blank'])
            ->make(true);
    }

    public function add_ems_sched(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        $data = $request->all();

        $validator = Validator::make($data, [
            'process' => ['required', 'string'],
            'areas' => ['required'],
            'process_owners' => ['required'],
            'internal_auditors' => ['required'],
            'year' => ['required'],
            // 'quarter' => ['required'],
            'plan_from' => ['required'],
            'plan_to' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => '0', 'error' => $validator->messages()]);
        }
        else{
            DB::beginTransaction();

            try{
                $ems_sched_id = EMSAuditSchedule::insertGetId([
                    'process' => $request->process,
                    'ems_audit_schedule_stat' => 1,
                    'update_version' => 1,
                    'created_by' => $_SESSION['rapidx_user_id'],
                    'last_updated_by' => $_SESSION['rapidx_user_id'],
                    'updated_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                for($index = 0; $index < count($request->year); $index++) {
                    EMSAuditScheduleYear::insert([
                        'ems_audit_schedule_id' => $ems_sched_id,
                        'year' => $request->year[$index],
                        // 'quarter' => $request->quarter[$index],
                        'plan_from' => $request->plan_from[$index],
                        'plan_to' => $request->plan_to[$index],
                        'actual_from' => $request->actual_from[$index],
                        'actual_to' => $request->actual_to[$index],
                        'reschedule_from' => $request->reschedule_from[$index],
                        'reschedule_to' => $request->reschedule_to[$index],
                        'ems_audit_schedule_year_stat' => 1,
                        'update_version' => 1,
                        'created_by' => $_SESSION['rapidx_user_id'],
                        'last_updated_by' => $_SESSION['rapidx_user_id'],
                        'updated_at' => date('Y-m-d H:i:s'),
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }

                for($index = 0; $index < count($request->process_owners); $index++) {
                    EMSSchedProcessOwners::insert([
                        'ems_audit_schedule_id' => $ems_sched_id,
                        'process_owner_id' => $request->process_owners[$index],
                        'created_by' => $_SESSION['rapidx_user_id'],
                        'updated_at' => date('Y-m-d H:i:s'),
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }

                for($index = 0; $index < count($request->internal_auditors); $index++) {
                    EMSSchedInternalAuditors::insert([
                        'ems_audit_schedule_id' => $ems_sched_id,
                        'internal_auditor_id' => $request->internal_auditors[$index],
                        'created_by' => $_SESSION['rapidx_user_id'],
                        'updated_at' => date('Y-m-d H:i:s'),
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }

                for($index = 0; $index < count($request->areas); $index++) {
                    EMSSchedAreas::insert([
                        'ems_audit_schedule_id' => $ems_sched_id,
                        'area_id' => $request->areas[$index],
                        'created_by' => $_SESSION['rapidx_user_id'],
                        'updated_at' => date('Y-m-d H:i:s'),
                        'created_at' => date('Y-m-d H:i:s')
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

    public function edit_ems_sched(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        $data = $request->all();

        // return $data;

        $validator = Validator::make($data, [
            'process' => ['required', 'string'],
            'areas' => ['required'],
            'process_owners' => ['required'],
            'internal_auditors' => ['required'],
            'year' => ['required', 'numeric'],
            'quarter' => ['required', 'numeric'],
            'plan_from' => ['required', 'date'],
            'plan_to' => ['required', 'date'],
        ]);


        if ($validator->fails()) {
            return response()->json(['result' => '0', 'error' => $validator->messages()]);
        }
        else{
            $areas = '';
            for($index = 0; $index < count($request->areas); $index++) {
                $areas .= $request->areas[$index] . ',';
            }
            $areas = substr_replace($areas, "", -1);

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
                EMSAuditSchedule::where('ems_audit_schedule_id', $request->ems_audit_schedule_id)
                    ->increment('update_version', 1,
                    [
                        'process' => $request->process,
                        'areas' => $areas,
                        'process_owners' => $process_owners,
                        'internal_auditors' => $internal_auditors,
                        'year' => $request->year,
                        'quarter' => $request->quarter,
                        'plan_from' => $request->plan_from,
                        'plan_to' => $request->plan_to,
                        'actual_from' => $request->actual_from,
                        'actual_to' => $request->actual_to,
                        'reschedule_from' => $request->reschedule_from,
                        'reschedule_to' => $request->reschedule_to,
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
    }

    public function get_ems_sched_by_id(Request $request) {
        session_start();
        $ems_schedules = EMSAuditSchedule::where('ems_audit_schedule_id', $request->ems_audit_schedule_id)->get();
        return response()->json(['ems_schedules' => $ems_schedules]);
    }

    public function archive_ems_sched(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        DB::beginTransaction();

        try{
            EMSAuditSchedule::where('ems_audit_schedule_id', $request->ems_audit_schedule_id)
                ->increment('update_version', 1,
                [
                    'ems_audit_schedule_stat' => 2,
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

    public function restore_ems_sched(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        DB::beginTransaction();

        try{
            EMSAuditSchedule::where('ems_audit_schedule_id', $request->ems_audit_schedule_id)
                ->increment('update_version', 1,
                [
                    'ems_audit_schedule_stat' => 1,
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

    // Start of new Codes 2020-05-14
    public function ems_save_process(Request $request){
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
                    EMSProcess::insert([
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
                    EMSProcess::where('id', $request->process_id)
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

    public function get_ems_sched_by_audit_schedule_id(Request $request) {
        session_start();

        $ems_schedules = EMSProcess::where('audit_schedule_id', $request->audit_schedule_id);
        $ems_schedules = $ems_schedules->orderBy('no', 'asc');
        if(isset($request->status)){
            $ems_schedules = $ems_schedules->where('status', $request->status);
        }
        $ems_schedules = $ems_schedules->with([
                                'ems_schedule_details',
                                'ems_schedule_details.ems_year_details' => function($query){
                                    $query->where('status', 1);
                                },
                                'ems_schedule_details.process_owner_details',
                                'ems_schedule_details.area_details',
                                'ems_schedule_details.area_details.area_info',
                                'ems_schedule_details.process_owner_details.process_owner_info',
                                'ems_schedule_details.internal_auditor_details.internal_auditor_info',
                            ])
                            ->get();

        $ems_years = [];
        for($index = 0; $index < count($ems_schedules); $index++){
            if(count($ems_schedules) > 0){
                for($index2 = 0; $index2 < count($ems_schedules[$index]->ems_schedule_details); $index2++){
                    if(count($ems_schedules[$index]->ems_schedule_details) > 0){
                        for($index3 = 0; $index3 < count($ems_schedules[$index]->ems_schedule_details[$index2]->ems_year_details); $index3++){
                            $ems_years[] = $ems_schedules[$index]->ems_schedule_details[$index2]->ems_year_details;
                        }
                    }
                }
            }
        }

        // return collect($ems_years)->flatten(1)->pluck('year')->max();
        $ems_min_year = 0;
        $ems_max_year = 0;

        $ems_min_year = collect($ems_years)->flatten(1)->pluck('year')->min();
        $ems_max_year = collect($ems_years)->flatten(1)->pluck('year')->max();

        return response()->json(['ems_schedules' => $ems_schedules, 'ems_min_year' => $ems_min_year, 'ems_max_year' => $ems_max_year]);
    }

    public function get_ems_process_by_id(Request $request) {
        session_start();

        $ems_process = EMSProcess::where('id', $request->ems_process_id)->first();

        return response()->json(['ems_process' => $ems_process]);
    }

    public function ems_save_process_details(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        $data = $request->all();
        // Add
        if(!isset($request->ems_schedule_id)){

            $rules = [
                'process_id' => 'required',
                'process_owners' => 'required',
                'internal_auditors' => 'required',
                'areas' => 'required',
            ];

            $validator = Validator::make($data, $rules);

            if ($validator->fails()) {
                return response()->json(['result' => '0', 'error' => $validator->messages()]);
            }
            else{
                DB::beginTransaction();
                try{
                    $ems_schedule_id = EMSSchedule::insertGetId([
                        'process_id' => $request->process_id,
                        'status' => 1,
                        'created_by' => $_SESSION['rapidx_user_id'],
                        'last_updated_by' => $_SESSION['rapidx_user_id'],
                        'updated_at' => date('Y-m-d H:i:s'),
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);

                    for($index = 0; $index < count($request->areas); $index++) {
                        EMSArea::insert([
                            'ems_schedule_id' => $ems_schedule_id,
                            'area_id' => $request->areas[$index],
                        ]);
                    }

                    for($index = 0; $index < count($request->process_owners); $index++) {
                        EMSProcessOwner::insert([
                            'ems_schedule_id' => $ems_schedule_id,
                            'process_owner_id' => $request->process_owners[$index],
                        ]);
                    }

                    for($index = 0; $index < count($request->internal_auditors); $index++) {
                        EMSInternalAuditor::insert([
                            'ems_schedule_id' => $ems_schedule_id,
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
                'ems_schedule_id' => 'required',
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
                    EMSProcessOwner::where('ems_schedule_id', $request->ems_schedule_id)
                            ->whereNotIn('process_owner_id', $request->process_owners)
                            ->delete();

                    for($index = 0; $index < count($request->process_owners); $index++) {
                        $process_owners = EMSProcessOwner::where('ems_schedule_id', $request->ems_schedule_id)
                            ->where('process_owner_id', $request->process_owners[$index])
                            ->get();

                        if(count($process_owners) <= 0) {
                            EMSProcessOwner::insert([
                                'ems_schedule_id' => $request->ems_schedule_id,
                                'process_owner_id' => $request->process_owners[$index],
                            ]);
                        }

                    }

                    EMSInternalAuditor::where('ems_schedule_id', $request->ems_schedule_id)
                            ->whereNotIn('internal_auditor_id', $request->internal_auditors)
                            ->delete();

                    for($index = 0; $index < count($request->internal_auditors); $index++) {
                        $internal_auditors = EMSInternalAuditor::where('ems_schedule_id', $request->ems_schedule_id)
                            ->where('internal_auditor_id', $request->internal_auditors[$index])
                            ->get();

                        if(count($internal_auditors) <= 0) {
                            EMSInternalAuditor::insert([
                                'ems_schedule_id' => $request->ems_schedule_id,
                                'internal_auditor_id' => $request->internal_auditors[$index],
                            ]);
                        }

                    }

                    EMSArea::where('ems_schedule_id', $request->ems_schedule_id)
                            ->whereNotIn('area_id', $request->areas)
                            ->delete();

                    for($index = 0; $index < count($request->areas); $index++) {
                        $areas = EMSArea::where('ems_schedule_id', $request->ems_schedule_id)
                            ->where('area_id', $request->areas[$index])
                            ->get();

                        if(count($areas) <= 0) {
                            EMSArea::insert([
                                'ems_schedule_id' => $request->ems_schedule_id,
                                'area_id' => $request->areas[$index],
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

    public function get_ems_process_details_by_id(Request $request) {
        session_start();

        $ems_process_details = EMSSchedule::where('id', $request->audit_schedule_id)
                                ->with([
                                    'ems_process_info',
                                    'process_owner_details',
                                    'internal_auditor_details',
                                    'area_details',
                                    'ems_year_details' => function($query){
                                        $query->where('status', 1);
                                        $query->orderBy('year', 'asc');
                                    },
                                ])
                                ->first();

        return response()->json(['ems_process_details' => $ems_process_details]);
    }

    public function view_ems_years_by_schedule_id(Request $request) {
        session_start();

        $ems_years = EMSYear::where('ems_schedule_id', $request->ems_schedule_id)
                                ->orderBy('year', 'asc')
                                ->get();

        return DataTables::of($ems_years)
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

    public function ems_save_year(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        $data = $request->all();

        // Add
        if(!isset($request->year_id)){

            $rules = [
                'ems_schedule_id' => 'required',
                'year' => 'required',
            ];

            $validator = Validator::make($data, $rules);

            if ($validator->fails()) {
                return response()->json(['result' => '0', 'error' => $validator->messages()]);
            }
            else{

                try{
                    EMSYear::insert([
                        'ems_schedule_id' => $request->ems_schedule_id,
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
                'ems_schedule_id' => 'required',
                'year' => 'required',
            ];

            $validator = Validator::make($data, $rules);

            if ($validator->fails()) {
                return response()->json(['result' => '0', 'error' => $validator->messages()]);
            }
            else{
                try{
                    EMSYear::where('id', $request->year_id)
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

    public function get_ems_year_by_id(Request $request) {
        session_start();

        $ems_year = EMSYear::where('id', $request->ems_year_id)
                                ->first();

        return response()->json(['ems_year' => $ems_year]);
    }
}
