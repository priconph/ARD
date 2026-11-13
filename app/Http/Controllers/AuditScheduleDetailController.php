<?php

namespace App\Http\Controllers;

use App\User;
use App\Model\AuditScheduleDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use DataTables;
use Carbon\Carbon;

class AuditScheduleDetailController extends Controller
{
    //
    public function audit_schedule_details(Request $request){
        $audit_schedule_details = AuditScheduleDetail::where('id', $request->id)->first();

        if($audit_schedule_details != null){
            if($audit_schedule_details->schedule_type == 1){
                return view('aqms_schedule_details')->with(['aqms_schedule_details' => $audit_schedule_details]);
            }
            else if($audit_schedule_details->schedule_type == 2){
                return view('ems_schedule_details')->with(['ems_schedule_details' => $audit_schedule_details]);
            }
            else if($audit_schedule_details->schedule_type == 3){
                return view('product_schedule_details')->with(['product_schedule_details' => $audit_schedule_details]);
            }
            else if($audit_schedule_details->schedule_type == 3){
                return view('process_schedule_details')->with(['process_schedule_details' => $audit_schedule_details]);
            }
        }
        else{
            return "No record found.";
        }
    }

    public function get_sched_details_by_id(Request $request){
        $aqms_schedule_details = AuditScheduleDetail::where('id', $request->id)->first();

        return response()->json(['aqms_schedule_details' => $aqms_schedule_details]);
    }

    public function view_schedule_details(Request $request){
        session_start();
        $schedule_details = AuditScheduleDetail::where('schedule_type', $request->schedule_type);
        
        if(isset($request->status)){
            $schedule_details->where('status', $request->status);
        }                  

        $schedule_details->get();

        return DataTables::of($schedule_details)
            ->addColumn('action1', function($row){
                
                $result = '<center><div class="btn-group">
                          <button type="button" class="btn btn-primary dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-gear"></i>
                          </button>
                          <div class="dropdown-menu dropdown-menu-right">';

                if($row->status == 1){
                $result .= '<button class="dropdown-item aEditAuditSchedDet" sched-details-id="' . $row->id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" data-target="#modalSaveAuditScheduleDetails" data-keyboard="false">Edit</button>';

                    $result .= '<a href="audit_schedule_details/' . $row->id . '" class="dropdown-item aDetailsAuditSchedDet" sched-details-id="' . $row->id . '" type="button" style="padding: 1px 1px; text-align: center;" >Details</a>';
                    
                    $result .= '<button class="dropdown-item aAuditSchedAction" sched-details-id="' . $row->id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" status="2" data-target="#mdlAuditSchedAction">Archive</button>';
                }
                else{
                    $result .= '<button class="dropdown-item aAuditSchedAction" sched-details-id="' . $row->id . '" type="button" style="padding: 1px 1px; text-align: center;" data-toggle="modal" status="1" data-target="#mdlAuditSchedAction">Restore</button>';
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
            ->rawColumns(['action1', 'raw_status'])
            ->make(true);
    }

    public function add_audit_schedule_details(Request $request){
        session_start();
        $data = $request->all();

        $validator = Validator::make($data, [
            'year' => 'required|numeric',
            'scope' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'reference_stds' => 'required|string|max:255',
            'method' => 'required|string|max:255',
            'schedule_type' => 'required|numeric|max:255',
        ]);

        if($validator->passes()){
            DB::beginTransaction();

            try{
                if(isset($request->audit_schedule_detail_id)){
                    AuditScheduleDetail::where('id', $request->audit_schedule_detail_id)
                    ->increment('update_version', 1, 
                        [
                            'year' => $request->year,
                            'scope' => $request->scope,
                            'title' => $request->title,
                            'reference_stds' => $request->reference_stds,
                            'method' => $request->method,
                            'last_updated_by' => $_SESSION["rapidx_user_id"],
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                }
                else{
                    AuditScheduleDetail::insert([
                        'year' => $request->year,
                        'scope' => $request->scope,
                        'title' => $request->title,
                        'reference_stds' => $request->reference_stds,
                        'method' => $request->method,
                        'schedule_type' => $request->schedule_type,
                        'status' => 1,
                        'created_by' => $_SESSION["rapidx_user_id"],
                        'last_updated_by' => $_SESSION["rapidx_user_id"],
                        'update_version' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
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

    public function audit_sched_details_action(Request $request){
        session_start();
        $data = $request->all();

        $validator = Validator::make($data, [
            'audit_schedule_id' => 'required',
            'status' => 'required',
        ]);

        if($validator->passes()){
            try{
                AuditScheduleDetail::where('id', $request->audit_schedule_id)
                ->increment('update_version', 1, 
                    [
                        'status' => $request->status,
                        'last_updated_by' => $_SESSION["rapidx_user_id"],
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
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
}
