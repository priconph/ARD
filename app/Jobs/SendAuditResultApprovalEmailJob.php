<?php

namespace App\Jobs;

use App\Model\Customer;
use App\Model\EmailRecipient;
use App\Model\Internal;
use App\Model\Supplier;
use App\Model\TUV;
use Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAuditResultApprovalEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $audit_result_id;
    private $audit_result_type;
    private $type;
    public function __construct($audit_result_id, $audit_result_type, $type)
    {
        $this->audit_result_id   = $audit_result_id;
        $this->audit_result_type = $audit_result_type;
        $this->type              = $type;
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

            switch ($this->type) {
                case 1:
                    $subject = "TUV Audit Findings for QAS Head Approval - {$data->ctrl_no}{$data->tuv_audit_id}";
                    break;
                case 2:
                    $subject = "TUV Audit Findings Approved by QAS Head - {$data->ctrl_no}{$data->tuv_audit_id}";
                    break;
                default:
                    $subject = "TUV Audit Findings for QAS Head Approval - {$data->ctrl_no}{$data->tuv_audit_id}";
                    break;
            }
            // $subject = "TUV Audit Findings for QAS Head Approval - {$data->ctrl_no}{$data->tuv_audit_id}";
            $ctrl_no = "{$data->ctrl_no}{$data->tuv_audit_id}";
            // $attachment = $data->tuv_attachments;
            
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

            // $subject = "Customer Audit Findings for QAS Head Approval - {$data->ctrl_no}{$data->customer_audit_id}";
            switch ($this->type) {
                case 1:
                    $subject = "Customer Audit Findings for QAS Head Approval - {$data->ctrl_no}{$data->customer_audit_id}";
                    break;
                case 2:
                    $subject = "Customer Audit Findings Approved by QAS Head - {$data->ctrl_no}{$data->customer_audit_id}";
                    break;
                default:
                    $subject = "Customer Audit Findings for QAS Head Approval - {$data->ctrl_no}{$data->customer_audit_id}";
                    break;
            }
            $ctrl_no = "{$data->ctrl_no}{$data->customer_audit_id}";
            // $attachment = $data->customer_attachments;
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
            
            // $subject = "Internal Audit Findings for QAS Head Approval - {$data->audit_report_control_no}";
            switch ($this->type) {
                case 1:
                    $subject = "Internal Audit Findings for QAS Head Approval - {$data->audit_report_control_no}";
                    break;
                case 2:
                    $subject = "Internal Audit Findings Approved by QAS Head - {$data->audit_report_control_no}";
                    break;
                default:
                    $subject = "Internal Audit Findings for QAS Head Approval - {$data->audit_report_control_no}";
                    break;
            }
            $ctrl_no = $data->audit_report_control_no;
            // $attachment = $data->internal_attachments;
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
            
            // $subject = "Supplier Audit Findings for QAS Head Approval - {$data->audit_report_control_no}";
            switch ($this->type) {
                case 1:
                    $subject = "Supplier Audit Findings for QAS Head Approval - {$data->audit_report_control_no}";
                    break;
                case 2:
                    $subject = "Supplier Audit Findings Approved by QAS Head - {$data->audit_report_control_no}";
                    break;
                default:
                    $subject = "Supplier Audit Findings for QAS Head Approval - {$data->audit_report_control_no}";
                    break;
            }
            $ctrl_no = $data->audit_report_control_no;
            // $attachment = $data->supplier_attachments;
        }

       
            
        $email_recipients = EmailRecipient::with([
            'department',
            'receiver_info',
        ])
        ->whereIn('department_id', $arr_responsible_department_id)
        ->get();
        $arrAddressTo = ['jggabuat@pricon.ph', 'cpagtalunan@pricon.ph'];
        $arrCC = ['jggabuat@pricon.ph', 'cpagtalunan@pricon.ph'];

        // $arrAddressTo = ['group-qas@pricon.ph'];
        // $arrCC = collect($email_recipients)->whereIn('address_type', [2,1])->whereIn('department_id', $arr_responsible_department_id)->flatten(1)->pluck('receiver_info.email');

        $EmailDatadata =array(
            'data' => $data,
            'ctrl_no' => $ctrl_no,
            'type' => strtoupper($this->audit_result_type)
        );

        
        switch($this->type){
            case 1:
                $emailTemplate = 'mail.send_audit_result_approval';
                break;
            case 2:
                $emailTemplate = 'mail.send_audit_result_approved';
                break;
            default:
                $emailTemplate = 'mail.send_audit_result_approval';
                break;
        }
        
        Mail::send($emailTemplate, $EmailDatadata , function($message) use($arrAddressTo, $arrCC, $subject){
            $message->to($arrAddressTo)->cc($arrCC)->subject($subject);
        });
    }
}
