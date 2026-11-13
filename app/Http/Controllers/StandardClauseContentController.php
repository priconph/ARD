<?php

namespace App\Http\Controllers;

use App\Model\StandardClauseContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use DataTables;

class StandardClauseContentController extends Controller
{
    //
    public function view_standard_clause_content_by_stat(Request $request){
        session_start();
        $standard_clause_contents;
                    
        if($request->standard_clause_content_stat == 0){
        	$standard_clause_contents = StandardClauseContent::join('standard_clauses', 'standard_clauses.standard_clause_id', '=', 'standard_clause_contents.standard_clause_id')
                        ->orderBy('standard_clause_content_name', 'asc')
                        ->get();
        }
        else {
        	$standard_clause_contents = StandardClauseContent::where('standard_clause_content_stat', $request->standard_clause_content_stat)
                        ->join('standard_clauses', 'standard_clauses.standard_clause_id', '=', 'standard_clause_contents.standard_clause_id')
                        ->orderBy('standard_clause_content_name', 'asc')
                        ->get();
        }

        return DataTables::of($standard_clause_contents)
            ->addColumn('action1', function($standard_clause_content){
                $result = "";

                if($standard_clause_content->standard_clause_content_stat == 1){
                    $result .= '<span class="tag tag-success">Active</span>';
                }
                else{
                    $result .= '<span class="tag tag-danger">Inactive</span>';
                }

                return $result;
            })
            ->addColumn('label1', function($standard_clause_content){
                $result = '<button class="btn btn-primary aEditStandardClauseContent" type="button" standard-clause-content-id="' . $standard_clause_content->standard_clause_content_id . '" data-toggle="modal" data-target="#modalEditStandardClauseContent" data-keyboard="false">Edit</button> ';
                if($standard_clause_content->standard_clause_content_stat == 1){
                    $result .= '<button class="btn btn-danger aArchiveStandardClauseContent" type="button" standard-clause-content-id="' . $standard_clause_content->standard_clause_content_id . '" data-toggle="modal" data-target="#modalArchiveStandardClauseContent" data-keyboard="false">Archive</button>';
                }
                else{
                    $result .= '<button class="btn btn-success aRestoreStandardClauseContent" type="button" standard-clause-content-id="' . $standard_clause_content->standard_clause_content_id . '" data-toggle="modal" data-target="#modalRestoreStandardClauseContent" data-keyboard="false">Restore</button>';
                }

                return $result;
            })
            ->addColumn('label2', function($standard_clause_content){
                if($standard_clause_content->is_header == 1) {
                    return '<b>' . $standard_clause_content->standard_clause_content_name . '<b>';
                }
                else{
                    return $standard_clause_content->standard_clause_content_name;
                }
            })
            ->rawColumns(['action1', 'label1', 'label2'])
            ->make(true);
    }

    public function add_standard_clause_content(Request $request){
        session_start();
        date_default_timezone_set('Asia/Manila');
        session_start();

        $data = $request->all();

        $validator = Validator::make($data, [
            'standard_clause_content_name' => ['required', 'string', 'max:255', 'unique:standard_clause_contents'],
            'standard_clause_id' => ['required', 'numeric', 'max:255'],
            // 'is_header' => ['required', 'numeric', 'max:255'],
        ]);

        $is_header = 0;

        if(isset($request->is_header)) {
            $is_header = 1;
        }

        if ($validator->fails()) {
            return response()->json(['result' => '0', 'error' => $validator->messages()]);
        }
        else{
            DB::beginTransaction();

            try{
                StandardClauseContent::insert([
                    'standard_clause_content_name' => $request->standard_clause_content_name,
                    'standard_clause_id' => $request->standard_clause_id,
                    'standard_clause_content_stat' => 1,
                    'is_header' => $is_header,
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

    public function archive_standard_clause_content(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        DB::beginTransaction();

        try{
            StandardClauseContent::where('standard_clause_content_id', $request->standard_clause_content_id)
                ->increment('update_version', 1, 
                    [
                        'standard_clause_content_stat' => 2,
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

    public function restore_standard_clause_content(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        DB::beginTransaction();

        try{
            StandardClauseContent::where('standard_clause_content_id', $request->standard_clause_content_id)
                ->increment('update_version', 1, 
                    [
                        'standard_clause_content_stat' => 1,
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

    public function get_standard_clause_content_by_id(Request $request){
        session_start();
        $standard_clause_content = StandardClauseContent::where('standard_clause_content_id', $request->standard_clause_content_id)->get();
        return response()->json(['standard_clause_content' => $standard_clause_content]);
    }

    public function edit_standard_clause_content(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        $data = $request->all();

        $validator = Validator::make($data, [
            'standard_clause_content_name' => ['required', 'string', 'max:255', 'unique:standard_clause_contents'],
            'standard_clause_id' => ['required', 'numeric', 'max:255'],
            // 'is_header' => ['required', 'numeric', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => '0', 'error' => $validator->messages()]);
        }
        else{
            $is_header = 0;

            if(isset($request->is_header)) {
                $is_header = 1;
            }

            DB::beginTransaction();

            try{
                StandardClauseContent::where('standard_clause_content_id', $request->standard_clause_content_id)
                    ->increment('update_version', 1, 
                        [
                            'standard_clause_content_name' => $request->standard_clause_content_name,
                            'standard_clause_id' => $request->standard_clause_id,
                            'is_header' => $is_header,
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
}
