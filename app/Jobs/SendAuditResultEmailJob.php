<?php

namespace App\Jobs;

use Mail;
use App\Model\TUV;
use App\Model\Customer;
use App\Model\Internal;
use App\Model\Supplier;
use App\Model\EmailRecipient;
use Illuminate\Bus\Queueable;
use App\Mail\SendAuditResultMail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendAuditResultEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    protected $audit_result_id;
    protected $audit_result_type;
    public function __construct($auditResultId, $ardType)
    {
        $this->audit_result_id = $auditResultId;
        $this->audit_result_type = $ardType;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = '';
        $subject = '';
        $arrAddressTo = array();
        $arrCC = array();
        $arr_responsible_department_id = array();
        $arr_responsible_department_name = array();
        if($this->audit_result_type == 'tuv'){
            $data = TUV::with([
                'tuv_departments' => function($query){
                    $query->where('tuv_department_stat', 1);
                },
                'tuv_departments.departments',
                'tuv_attachments' => function($query){
                    $query->whereNull('deleted_at');
                }
            ])
            ->where('tuv_audit_id', $this->audit_result_id)
            ->first();

            for($index2 = 0; $index2 < count($data->tuv_departments); $index2++){
                $arr_responsible_department_id[] = $data->tuv_departments[$index2]->departments[0]->department_id;
                $arr_responsible_department_name[] = $data->tuv_departments[$index2]->departments[0]->department_name;
            }

            $subject = "Submission of Improvement Actions for the TUV Audit Findings - {$data->ctrl_no}{$data->tuv_audit_id}";
            $attachment = $data->tuv_attachments;
            
        }
        else if ($this->audit_result_type == 'customer'){
            $data = Customer::with([
                'customer_departments' => function($query){
                    $query->where('customer_department_stat', 1);
                },
                'customer_departments.departments',
                'customer_attachments' => function($query){
                    $query->whereNull('deleted_at');
                }
            ])
            ->where('customer_audit_id', $this->audit_result_id)
            ->first();

            for($index2 = 0; $index2 < count($data->customer_departments); $index2++){
                $arr_responsible_department_id[] = $data->customer_departments[$index2]->departments[0]->department_id;
                $arr_responsible_department_name[] = $data->customer_departments[$index2]->departments[0]->department_name;
            }

            $subject = "Submission of Improvement Actions for the Customer Audit Findings - {$data->ctrl_no}{$data->customer_audit_id}";
            $attachment = $data->customer_attachments;
        }
        else if($this->audit_result_type == 'internal'){
            $data = Internal::with([
                'internal_departments' => function($query){
                    $query->where('internal_department_stat', 1);
                },
                'internal_departments.departments',
                'internal_attachments' => function($query){
                    $query->whereNull('deleted_at');
                }
            ])
            ->where('internal_audit_id', $this->audit_result_id)
            ->first();

            for($index2 = 0; $index2 < count($data->internal_departments); $index2++){
                $arr_responsible_department_id[] = $data->internal_departments[$index2]->departments[0]->department_id;
                $arr_responsible_department_name[] = $data->internal_departments[$index2]->departments[0]->department_name;
            }
            
            $subject = "Submission of Improvement Actions for the Internal Audit Findings - {$data->audit_report_control_no}";
            $attachment = $data->internal_attachments;
        }
        else if($this->audit_result_type == 'supplier'){
            $data = Supplier::with([
                'supplier_departments' => function($query){
                    $query->where('supplier_department_stat', 1);
                },
                'supplier_departments.departments', 
                'supplier_attachments' => function($query){
                    $query->whereNull('deleted_at');
                }
            ])
            ->where('supplier_audit_id', $this->audit_result_id)
            ->first();

             for($index2 = 0; $index2 < count($data->supplier_departments); $index2++){
                $arr_responsible_department_id[] = $data->supplier_departments[$index2]->departments[0]->department_id;
                $arr_responsible_department_name[] = $data->supplier_departments[$index2]->departments[0]->department_name;
            }
            
            $subject = "Submission of Improvement Actions for the Supplier Audit Findings - {$data->audit_report_control_no}";
            $attachment = $data->supplier_attachments;
        }

       
            
        $email_recipients = EmailRecipient::with([
            'department',
            'receiver_info',
        ])
        ->whereIn('department_id', $arr_responsible_department_id)
        ->get();
        // $arrAddressTo = collect($email_recipients)->where('address_type', 1)->whereIn('department_id', $arr_responsible_department_id)->flatten(1)->pluck('receiver_info.email');
        // $arrCC = collect($email_recipients)->where('address_type', 2)->whereIn('department_id', $arr_responsible_department_id)->flatten(1)->pluck('receiver_info.email');

        $arrAddressTo = ['jggabuat@pricon.ph', 'cpagtalunan@pricon.ph'];
        $arrCC = ['jggabuat@pricon.ph', 'cpagtalunan@pricon.ph'];
        // $arrCC = collect($email_recipients)->where('address_type', 2)->whereIn('department_id', $arr_responsible_department_id)->flatten(1)->pluck('receiver_info.email');

        $audit_result_email = new SendAuditResultMail($data, $attachment, $subject, $this->audit_result_type);
        Mail::to($arrAddressTo)->cc($arrCC)->send($audit_result_email);
        // $mail = Mail::to(['cpagtalunan@pricon.ph'])->send($audit_result_email);
    }
}
