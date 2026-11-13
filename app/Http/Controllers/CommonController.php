<?php

namespace App\Http\Controllers;

use App\Jobs\SendAuditResultApprovalEmailJob;
use App\Model\TUV;
use App\Model\Customer;
use App\Model\Internal;
use App\Model\Supplier;
use Illuminate\Http\Request;
use App\Model\TuvAuditAttachment;
use Illuminate\Support\Facades\DB;
use App\Model\CustomerAuditAttachment;
use App\Model\InternalAuditAttachment;
use App\Model\SupplierAuditAttachment;
use DataTables;
date_default_timezone_set('Asia/Manila');
class CommonController extends Controller
{
    public function proceed_approval_audit(Request $request){
        session_start();
        DB::beginTransaction();
        try{
            switch ($request->audit_type) {
                case 'tuv':
                    $model = TUV::class;
                    $whereId = 'tuv_audit_id';
                    $auditStatusToUpdate = 'tuv_audit_stat';
                    break;
                case 'customer':
                    $model = Customer::class;
                    $whereId = 'customer_audit_id';
                    $auditStatusToUpdate = 'customer_audit_stat';
                    break;
                case 'internal':
                    $model = Internal::class;
                    $whereId = 'internal_audit_id';
                    $auditStatusToUpdate = 'internal_audit_stat';
                    break;
                case 'supplier':
                    $model = Supplier::class;
                    $whereId = 'supplier_audit_id';
                    $auditStatusToUpdate = 'supplier_audit_stat';
                    break;
                default:
                    return response()->json([
                        'result' => false,
                        'message' => 'wrong audit type'
                    ], 500);
                    break;
            }



            $audits = $model::where($whereId, $request->audit_id)
            ->update([
                $auditStatusToUpdate => 2,
                'audit_stat'  => 2,
                'last_updated_by' =>  $_SESSION['rapidx_user_id'],
            ]);

            
            DB::commit();
            dispatch(new SendAuditResultApprovalEmailJob($request->audit_id, $request->audit_type, 1));

            return response()->json([
                'result' => $audits
            ]);

        }
        catch(Exception $e){
            DB::rollback();
            return response()->json([
                'result' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function approve_audit_result(Request $request){
        session_start();
        DB::beginTransaction();
        try{
            switch ($request->audit_classification) {
                case 'tuv':
                    $model = TUV::class;
                    $whereId = 'tuv_audit_id';
                    $toUpdate = 'tuv_audit_stat';
                    break;
                case 'customer':
                    $model = Customer::class;
                    $whereId = 'customer_audit_id';
                    $toUpdate = 'customer_audit_stat';

                    break;
                case 'internal':
                    $model = Internal::class;
                    $whereId = 'internal_audit_id';
                    $toUpdate = 'internal_audit_stat';

                    break;
                case 'supplier':
                    $model = Supplier::class;
                    $whereId = 'supplier_audit_id';
                    $toUpdate = 'supplier_audit_stat';

                    break;
                default:
                    return response()->json([
                        'result' => false,
                        'message' => 'wrong audit classification'
                    ], 500);
                    break;
            }

            $audits = $model::where($whereId, $request->audit_id)
            ->update([
                'audit_stat'      => 3,
                $toUpdate         => 3,
                'qad_approval'    => $_SESSION['rapidx_user_id'],
                'qad_approval_at'    => NOW(),
                'last_updated_by' => $_SESSION['rapidx_user_id'],
            ]);

            
            DB::commit();
            dispatch(new SendAuditResultApprovalEmailJob($request->audit_id, $request->audit_classification, 2));
            return response()->json([
                'result' => $audits
            ]);

        }
        catch(Throwable $e){
            DB::rollback();
            return response()->json([
                'result' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function disapprove_audit_result(Request $request){
        session_start();
        DB::beginTransaction();
        try{
            switch ($request->audit_classification) {
                case 'tuv':
                    $model    = TUV::class;
                    $whereId  = 'tuv_audit_id';
                    $toUpdate = 'tuv_audit_stat';
                    break;
                case 'customer':
                    $model    = Customer::class;
                    $whereId  = 'customer_audit_id';
                    $toUpdate = 'customer_audit_stat';
                    break;
                case 'internal':
                    $model    = Internal::class;
                    $whereId  = 'internal_audit_id';
                    $toUpdate = 'internal_audit_stat';
                    break;
                case 'supplier':
                    $model    = Supplier::class;
                    $whereId  = 'supplier_audit_id';
                    $toUpdate = 'supplier_audit_stat';
                    break;
                default:
                    return response()->json([
                        'result'  => false,
                        'message' => 'wrong audit classification'
                    ], 500);
                    break;
            }

            $audits = $model::where($whereId, $request->audit_id)
            ->update([
                'audit_stat'      => 1,
                $toUpdate         => 4,
                'last_updated_by' => $_SESSION['rapidx_user_id'],
                'qad_dis_remarks' => $request->remarks
            ]);
            DB::commit();

            // dispatch(new SendAuditResultApprovalEmailJob($request->audit_id, $request->audit_type));

            return response()->json([
                'result' => $audits
            ]);
        }catch(Throwable $e){
            DB::rollback();
            return $e->getMessage();
        }
    }
    public function dt_get_attachments(Request $request){
        $model = TuvAuditAttachment::class;
        $foreignKey = 'tuv_audit_id';
        if ($request->type === 'customer') {
            $model = CustomerAuditAttachment::class;
            $foreignKey = 'customer_audit_id';
        }
        else if($request->type == 'internal'){
            $model = InternalAuditAttachment::class;
            $foreignKey = 'internal_audit_id';
        }
        else if($request->type == 'supplier'){
            $model = SupplierAuditAttachment::class;
            $foreignKey = 'supplier_audit_id';
        }
        else{
            $request->type = 'tuv';
        }

        $tuv_attachments = $model::where($foreignKey, $request->auditId)
        ->whereNull('deleted_at')
        ->get();
        return DataTables::of($tuv_attachments)
        ->addColumn('attachment_title', function($data){
            return $result = '<a href="' . asset('public/storage/' . $data->path .$data->id . '.' . pathinfo($data->filename, PATHINFO_EXTENSION)) . '" target="_blank">' . $data->filename . '</a>';
        })
        ->addColumn('attachment_remarks', function($data){
            return $data->remarks;
        })
        ->addColumn('action', function($data) use ($request, $foreignKey){
            return '<button class="btn btn-danger btn-sm aDeleteAttachment" type="button" attachment-id="' . $data->id . '" data-type="'.$request->type.'" attachment-audit-id="' .  $data->auditId .'"><i class="icon-trash"></i></button>';
        })
        ->rawColumns(['attachment_title', 'attachment_remarks', 'action'])
        ->make(true);
    }

    public function delete_attachment(Request $request){
        DB::beginTransaction();
        // return $request->all();
        try{
            $model = TuvAuditAttachment::class;
            $foreignKey = 'tuv_audit_id';
            if ($request->type === 'customer') {
                $model = CustomerAuditAttachment::class;
                $foreignKey = 'customer_audit_id';
            }
            else if($request->type == 'internal'){
                $model = InternalAuditAttachment::class;
                $foreignKey = 'internal_audit_id';
            }
            else if($request->type == 'supplier'){
                $model = SupplierAuditAttachment::class;
                $foreignKey = 'supplier_audit_id';
            }
            $attachment = $model::where('id', $request->attachment_id)
            ->update([
                'deleted_at' => NOW(),
            ]);
            DB::commit();

            return response()->json([
                'result' => $attachment
            ]);
        }catch(\Throwable $e){
            DB::rollback();
            return $e->getMessage();
        }
    }
}
