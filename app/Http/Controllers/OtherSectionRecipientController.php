<?php

namespace App\Http\Controllers;

use App\Model\OtherSectionRecipient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use DataTables;

class OtherSectionRecipientController extends Controller
{
    //
    public function view_other_section_recipient_by_stat(Request $request){
    	$other_section_recipients = OtherSectionRecipient::with([
                                        'department',
                                        'receiver_info',
                                    ])
                                    ->get();

        return DataTables::of($other_section_recipients)
            ->addColumn('label1', function($other_section_recipient){
                $result = "";

                if($other_section_recipient->other_section_recipient_stat == 1){
                    $result .= '<span class="tag tag-success">Active</span>';
                }
                else{
                    $result .= '<span class="tag tag-danger">Inactive</span>';
                }

                return $result;
            })
            ->addColumn('action1', function($other_section_recipient){
                $result = '';
                // $result = '<button class="btn btn-primary aEditOtherSectionRecipient" type="button" other-section-recipient-id="' . $other_section_recipient->other_section_recipient_id . '" data-toggle="modal" data-target="#modalEditOtherSectionRecipient" data-keyboard="false">Edit</button> ';
                if($other_section_recipient->other_section_recipient_stat == 1){
                    $result .= '<button class="btn btn-danger aRemoveOtherSectionRecipient" type="button" other-section-recipient-id="' . $other_section_recipient->other_section_recipient_id . '" data-toggle="modal" data-target="#modalRemoveOtherSectionRecipient" data-keyboard="false">Remove</button>';
                }

                return $result;
            })
            ->addColumn('address_type_label', function($other_section_recipient){
                $result = "";

                if($other_section_recipient->address_type == 1){
                    $result .= 'Attention To';
                }
                else{
                    $result .= 'Carbon Copy';
                }

                return $result;
            })
            ->rawColumns(['action1', 'label1', 'address_type_label'])
            ->make(true);
    }

    public function add_other_section_recipient(Request $request){
        date_default_timezone_set('Asia/Manila');
    	session_start();

        $data = $request->all();

        $validator = Validator::make($data, [
            'department_id' => ['required', 'integer'],
            'receiver' => ['required', 'integer'],
            'address_type' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => '0', 'error' => $validator->messages()]);
        }
        else{
            DB::beginTransaction();

            try{
                OtherSectionRecipient::insert([
                    'department_id' => $request->department_id,
                    'receiver' => $request->receiver,
                    'address_type' => $request->address_type,
                    'other_section_recipient_stat' => 1,
                    'created_by' => $_SESSION['rapidx_user_id'],
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

    public function remove_other_section_recipient(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        DB::beginTransaction();

        try{
	        OtherSectionRecipient::where('other_section_recipient_id', $request->other_section_recipient_id)
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

    // public function edit_other_section_recipient(Request $request){
    //     date_default_timezone_set('Asia/Manila');
    //     session_start();

    //     $data = $request->all();

    //     $validator = Validator::make($data, [
    //         'other_section_recipient_name' => ['required', 'string', 'max:255', 'unique:other_section_recipients'],
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['result' => '0', 'error' => $validator->messages()]);
    //     }
    //     else{
    //         DB::beginTransaction();

    //         try{
    //             OtherSectionRecipient::where('other_section_recipient_id', $request->other_section_recipient_id)
    //                 ->increment('update_version', 1, 
    //                     [
    //                         'other_section_recipient_name' => $request->other_section_recipient_name,
    //                         'updated_at' => date('Y-m-d H:i:s'),
    //                         'last_updated_by' => $_SESSION['rapidx_user_id']
    //                     ]
    //                 );
    //             DB::commit();
    //             return response()->json(['result' => "1"]);
    //         }
    //         catch(\Exception $e) {
    //             DB::rollback();
    //             // throw $e;
    //             return response()->json(['result' => "0"]);
    //         }  
    //     }
    // }
}
