<?php

namespace App\Http\Controllers;

use App\Model\StandardClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use DataTables;

class StandardClauseController extends Controller
{
    //
    public function view_standard_clause_by_stat(Request $request){
        session_start();
        $standard_clauses;
                    
        if($request->standard_clause_stat == 0){
        	$standard_clauses = StandardClause::all();
        }
        else {
        	$standard_clauses = StandardClause::where('standard_clause_stat', $request->standard_clause_stat)->get();
        }

        return DataTables::of($standard_clauses)
            ->addColumn('action1', function($standard_clause){
                $result = "";

                if($standard_clause->standard_clause_stat == 1){
                    $result .= '<span class="tag tag-success">Active</span>';
                }
                else{
                    $result .= '<span class="tag tag-danger">Inactive</span>';
                }

                return $result;
            })
            ->addColumn('label1', function($standard_clause){
                $result = '<button class="btn btn-primary aEditStandardClause" type="button" standard-clause-id="' . $standard_clause->standard_clause_id . '" data-toggle="modal" data-target="#modalEditStandardClause" data-keyboard="false">Edit</button> ';
                if($standard_clause->standard_clause_stat == 1){
                    $result .= '<button class="btn btn-danger aArchiveStandardClause" type="button" standard-clause-id="' . $standard_clause->standard_clause_id . '" data-toggle="modal" data-target="#modalArchiveStandardClause" data-keyboard="false">Archive</button>';
                }
                else{
                    $result .= '<button class="btn btn-success aRestoreStandardClause" type="button" standard-clause-id="' . $standard_clause->standard_clause_id . '" data-toggle="modal" data-target="#modalRestoreStandardClause" data-keyboard="false">Restore</button>';
                }

                return $result;
            })
            ->rawColumns(['action1', 'label1'])
            ->make(true);
    }

    public function add_standard_clause(Request $request){
        date_default_timezone_set('Asia/Manila');
    	session_start();

        $data = $request->all();

        // return response()->json(['result' => $data]);

        $validator = Validator::make($data, [
            'standard_clause_name' => ['required', 'string', 'max:255', 'unique:standard_clauses'],
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => '0', 'error' => $validator->messages()]);
        }
        else{
            DB::beginTransaction();

            try{
                StandardClause::insert([
                    'standard_clause_name' => $request->standard_clause_name,
                    'standard_clause_stat' => 1,
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
                return response()->json(['result' => "0"]);
            }
        }
    }

    public function archive_standard_clause(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        DB::beginTransaction();

        try{
	        StandardClause::where('standard_clause_id', $request->standard_clause_id)
	            ->increment('update_version', 1, 
	                [
	                    'standard_clause_stat' => 2,
	                    'updated_at' => date('Y-m-d H:i:s'),
	                    'last_updated_by' => $_SESSION['rapidx_user_id']
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

    public function restore_standard_clause(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        DB::beginTransaction();

        try{
	        StandardClause::where('standard_clause_id', $request->standard_clause_id)
	            ->increment('update_version', 1, 
	                [
	                    'standard_clause_stat' => 1,
	                    'updated_at' => date('Y-m-d H:i:s'),
	                    'last_updated_by' => $_SESSION['rapidx_user_id']
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

    public function get_standard_clause_by_id(Request $request){
        session_start();
        $standard_clause = StandardClause::where('standard_clause_id', $request->standard_clause_id)->get();
        return response()->json(['standard_clause' => $standard_clause]);
    }

    public function edit_standard_clause(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        $data = $request->all();

        $validator = Validator::make($data, [
            'standard_clause_name' => ['required', 'string', 'max:255', 'unique:standard_clauses'],
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => '0', 'error' => $validator->messages()]);
        }
        else{
            DB::beginTransaction();

            try{
                StandardClause::where('standard_clause_id', $request->standard_clause_id)
                    ->increment('update_version', 1, 
                        [
                            'standard_clause_name' => $request->standard_clause_name,
                            'updated_at' => date('Y-m-d H:i:s'),
                            'last_updated_by' => $_SESSION['rapidx_user_id']
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

    public function get_standard_clause_by_stat(Request $request){
        session_start();
		$standard_clauses;
		if($request->standard_clause_stat == 0){
			$standard_clauses = StandardClause::all();
		}
		else{
			$standard_clauses = StandardClause::where('standard_clause_stat', $request->standard_clause_stat)->get();
		}
		return response()->json(['standard_clauses' => $standard_clauses]);
	}
}
