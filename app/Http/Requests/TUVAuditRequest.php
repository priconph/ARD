<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TUVAuditRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if(!isset($this->hidden_tuv_audit_id)){
            // if($this->classification == 'CPAR'){
                 return [
                    'actual_date_of_submission' => 'required',
                    'audit_title' => 'required',
                    'attachments' => 'nullable',
                    'audit_control_number' => 'required',
                    'audit_category' => 'required',
                    'audit_date_from' => 'required',
                    'audit_date_to' => 'required',
                    'auditors' => 'required',
                    'classification' => 'required',
                    'deadline_for_submission' => 'required',
                    // 'deadline_for_submission_days' => 'required',
                    // 'evaluation_item_id' => 'required',
                    // 'item_no' => 'required',
                    'purpose_of_audit' => 'required',
                    'responsible_department' => 'required',
                    // 'standard_clause' => 'required',
                    'hidden_tuv_audit_id' => 'nullable',
                    'img_corrective_action' => 'nullable',
                    'pdf_corrective_action' => 'nullable',
                    'number_OFI' => 'nullable',
                    'number_major_nc' => 'nullable',
                    'number_minor_nc' => 'nullable',
                    'number_possitive_feedback' => 'nullable'
                ];
            // }
            // else{
            //     return [
            //         'actual_date_of_submission' => 'required',
            //         'audit_title' => 'required',
            //         'audit_control_number' => 'required',
            //         'attachments' => 'nullable',
            //         'audit_category' => 'required',
            //         'audit_date_from' => 'required',
            //         'audit_date_to' => 'required',
            //         'auditors' => 'required',
            //         'classification' => 'required',
            //         'deadline_for_submission' => 'required',
            //         // 'deadline_for_submission_days' => 'required',
            //         // 'evaluation_item_id' => 'required',
            //         // 'item_no' => 'required',
            //         'purpose_of_audit' => 'required',
            //         'responsible_department' => 'required',
            //         // 'standard_clause' => 'required',
            //         'hidden_tuv_audit_id' => 'nullable',
            //         'img_corrective_action' => 'nullable',
            //         'pdf_corrective_action' => 'nullable'
            //     ];
            // }
            
        }
        else{
            return [];
        }
        
    }
}
