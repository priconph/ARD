<?php

namespace App\Http\Controllers;

use App\Model\Approver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use DataTables;

class ApproverController extends Controller
{
    public function view_approver_by_stat(Request $request){
        session_start();
        $approvers = Approver::with([
                                        'department',
                                        'approver_info',
                                    ])
                                    ->get();

        return DataTables::of($approvers)
            ->addColumn('label1', function($approver){
                $result = "";

                if($approver->approver_stat == 1){
                    $result .= '<span class="tag tag-success">Active</span>';
                }
                else{
                    $result .= '<span class="tag tag-danger">Inactive</span>';
                }

                return $result;
            })
            ->addColumn('action1', function($approver){
                $result = '';
                if($approver->approver_stat == 1){
                    $result .= '<button class="btn btn-danger aRemoveApprover" type="button" approver-id="' . $approver->approver_id . '" data-toggle="modal" data-target="#modalRemoveApprover" data-keyboard="false">Remove</button>';
                }

                return $result;
            })
            ->rawColumns(['action1', 'label1'])
            ->make(true);
    }

    public function add_approver(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        $data = $request->all();

        $validator = Validator::make($data, [
            'department_id' => 'required|numeric',
            'approver' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => '0', 'error' => $validator->messages()]);
        }
        else{
            DB::beginTransaction();

            try{
                $approvers = Approver::where('department_id', $request->department_id)
                                    ->where('approver', $request->approver)
                                    ->get();

                if(count($approvers) > 0){
                    return response()->json(['result' => "0"]);
                }

                Approver::insert([
                    'department_id' => $request->department_id,
                    'approver' => $request->approver,
                    'approver_stat' => 1,
                    'update_version' => 1,
                    'created_by' => $_SESSION['rapidx_user_id'],
                    'last_updated_by' => $_SESSION['rapidx_user_id'],
                    'updated_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s')
                ]);
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

    // public function change_approver_stat(Request $request){
    //     date_default_timezone_set('Asia/Manila');
    //     session_start();

    //     DB::beginTransaction();

    //     try{
    //         Approver::where('approver_id', $request->approver_id)
    //             ->increment('update_version', 1, 
    //                 [
    //                     'last_updated_by' => $_SESSION['rapidx_user_id'],
    //                     'updated_at' => date('Y-m-d H:i:s'),
    //                 ]);
    //         DB::commit();
    //         return response()->json(['result' => "1"]);
    //     }
    //     catch(\Exception $e) {
    //         DB::rollback();
    //         // throw $e;
    //         return response()->json(['result' => "0"]);
    //     }  
    // }

    public function remove_approver(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        DB::beginTransaction();

        try{
            Approver::where('approver_id', $request->approver_id)
                ->delete();
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
