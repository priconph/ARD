<?php

namespace App\Http\Controllers;

use App\Model\EvaluationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use DataTables;

class EvaluationItemController extends Controller
{
    //
    public function view_evaluation_item_by_stat(Request $request){
        session_start();
        $evaluation_items;
                    
        if($request->evaluation_item_stat == 0){
        	$evaluation_items = EvaluationItem::all();
        }
        else {
        	$evaluation_items = EvaluationItem::where('evaluation_item_stat', $request->evaluation_item_stat)->get();
        }

        return DataTables::of($evaluation_items)
            ->addColumn('action1', function($evaluation_item){
                $result = "";

                if($evaluation_item->evaluation_item_stat == 1){
                    $result .= '<span class="tag tag-success">Active</span>';
                }
                else{
                    $result .= '<span class="tag tag-danger">Inactive</span>';
                }

                return $result;
            })
            ->addColumn('label1', function($evaluation_item){
                $result = '<button class="btn btn-sm btn-primary aEditEvaluationItem" type="button" evaluation-item-id="' . $evaluation_item->evaluation_item_id . '" data-toggle="modal" data-target="#modalEditEvaluationItem" data-keyboard="false">Edit</button> ';
                if($evaluation_item->evaluation_item_stat == 1){
                    $result .= '<button class="btn btn-sm btn-danger aArchiveEvaluationItem" type="button" evaluation-item-id="' . $evaluation_item->evaluation_item_id . '" data-toggle="modal" data-target="#modalArchiveEvaluationItem" data-keyboard="false">Archive</button>';
                }
                else{
                    $result .= '<button class="btn btn-sm btn-success aRestoreEvaluationItem" type="button" evaluation-item-id="' . $evaluation_item->evaluation_item_id . '" data-toggle="modal" data-target="#modalRestoreEvaluationItem" data-keyboard="false">Restore</button>';
                }

                return $result;
            })
            ->rawColumns(['action1', 'label1'])
            ->make(true);
    }

    public function add_evaluation_item(Request $request){
        date_default_timezone_set('Asia/Manila');
    	session_start();

        $data = $request->all();

        // return response()->json(['result' => $data]);

        $validator = Validator::make($data, [
            'evaluation_item_name' => ['required', 'string', 'max:255', 'unique:evaluation_items'],
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => '0', 'error' => $validator->messages()]);
        }
        else{
            DB::beginTransaction();

            try{
                EvaluationItem::insert([
                    'evaluation_item_name' => $request->evaluation_item_name,
                    'evaluation_item_stat' => 1,
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

    public function archive_evaluation_item(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        DB::beginTransaction();

        try{
	        EvaluationItem::where('evaluation_item_id', $request->evaluation_item_id)
	            ->increment('update_version', 1, 
	                [
	                    'evaluation_item_stat' => 2,
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

    public function restore_evaluation_item(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        DB::beginTransaction();

        try{
	        EvaluationItem::where('evaluation_item_id', $request->evaluation_item_id)
	            ->increment('update_version', 1, 
	                [
	                    'evaluation_item_stat' => 1,
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

    public function get_evaluation_item_by_id(Request $request){
        session_start();
        $evaluation_item = EvaluationItem::where('evaluation_item_id', $request->evaluation_item_id)->get();
        return response()->json(['evaluation_item' => $evaluation_item]);
    }

    public function edit_evaluation_item(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        $data = $request->all();

        $validator = Validator::make($data, [
            'evaluation_item_name' => ['required', 'string', 'max:255', 'unique:evaluation_items'],
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => '0', 'error' => $validator->messages()]);
        }
        else{
            DB::beginTransaction();

            try{
                EvaluationItem::where('evaluation_item_id', $request->evaluation_item_id)
                    ->increment('update_version', 1, 
                        [
                            'evaluation_item_name' => $request->evaluation_item_name,
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

    public function get_evaluation_item_by_stat(Request $request){
        session_start();
		$evaluation_items = [];
		// if($request->evaluation_item_stat == 0){
		// 	$evaluation_items = EvaluationItem::all();
		// }
		// else{
		// 	$evaluation_items = EvaluationItem::where('evaluation_item_stat', $request->evaluation_item_stat)->get();
		// }
		$evaluation_items = EvaluationItem::where('evaluation_item_stat', 1)->get();

		return response()->json(['evaluation_items' => $evaluation_items]);
	}
}
