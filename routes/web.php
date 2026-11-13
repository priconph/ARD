<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Auth::routes();

// Main Page
Route::get('/', function(){
	return view('index');
})->name('home');

// Audit Result Pages
Route::get('/audit_result', function(){
	return view('audit_result');
})->name('audit_result');

Route::get('/audit_result_v2', function(){
	return view('audit_result_v2');
})->name('audit_result_v2');

Route::get('/archived_audit_result', function(){
	return view('archived_audit_result');
})->name('archived_audit_result');
Route::get('/archived', function(){
	return view('archived_audit_result');
})->name('archived');
Route::get('/archived_v2', function(){
	return view('archived_audit_result_v2');
})->name('archived_v2');

Route::get('/for_approval', function(){
	return view('for_approval');
})->name('for_approval');

Route::get('/for_approval_v2', function(){
	return view('for_approval_v2');
})->name('for_approval_v2');

Route::get('/audit_result_batch', function(){
	return view('audit_result_batch');
})->name('audit_result_batch');
Route::get('/audit_result_batch2', function(){
	return view('audit_result_batch2');
})->name('audit_result_batch2');
Route::get('/open_audit_result', function(){
	return view('open_audit_result_new');
})->name('open_audit_result');
Route::get('/open_audit_result_new', function(){
	return view('open_audit_result_new');
})->name('open_audit_result_new');
Route::get('/audit_result_history', function(){
	return view('audit_result_history');
})->name('audit_result_history');
Route::get('/auto_mailer', function(){
	return view('auto_mailer');
})->name('auto_mailer');

// Reports Pages
Route::get('/reports', function(){
	return view('reports');
})->name('reports');

// Charts Pages
Route::get('/charts', function(){
	return view('charts3');
})->name('charts');

Route::get('/charts2', function(){
	return view('charts2');
})->name('charts2');

Route::get('/charts3', function(){
	return view('charts');
})->name('charts3');

// Standard Clause
Route::get('/standard_clause', function(){
	return view('standard_clause');
})->name('standard_clause');

// Email Recipient
Route::get('/email_recipient', function(){
	return view('email_recepient');
})->name('email_recipient');

// Approver
Route::get('/approver', function(){
	return view('approver');
})->name('approver');

// Audit Schedule
Route::get('/audit_schedule', function(){
	return view('audit_schedule');
})->name('audit_schedule');

Route::get('/audit_schedulev2', function(){
	return view('audit_schedulev2');
})->name('audit_schedulev2');

// Audit Plan
Route::get('/audit_plan', function(){
	return view('audit_plan');
})->name('audit_plan');

// Evaluation Item
Route::get('/evaluation_item', function(){
	return view('evaluation_item');
})->name('evaluation_item');

// USER CONTROLLER
Route::get('/user_logout', 'UserController@user_logout');
Route::get('/get_users_by_stat', 'UserController@get_users_by_stat');

// TUV Controller
Route::get('/view_tuv_audit_by_stat', 'TUVController@view_tuv_audit_by_stat');
Route::get('/view_tuv_audit_by_stat_v2', 'TUVController@view_tuv_audit_by_stat_v2');
Route::get('/view_archived_tuv_audit_by_stat', 'TUVController@view_archived_tuv_audit_by_stat');
Route::get('/get_tuv_audit_by_id', 'TUVController@get_tuv_audit_by_id');
Route::post('/add_tuv_audit', 'TUVController@add_tuv_audit');
Route::post('/add_tuv_audit_v2', 'TUVController@add_tuv_audit_v2'); // chris => QUADS Modification
Route::post('/close_tuv_audit', 'TUVController@close_tuv_audit');
Route::post('/open_tuv_audit', 'TUVController@open_tuv_audit');
Route::post('/done_tuv_audit', 'TUVController@done_tuv_audit');
Route::post('/pend_tuv_audit', 'TUVController@pend_tuv_audit');
Route::post('/edit_tuv_audit', 'TUVController@edit_tuv_audit');
Route::post('/edit_open_tuv_audit', 'TUVController@edit_open_tuv_audit');
Route::get('/view_open_tuv_audit_by_stat', 'TUVController@view_open_tuv_audit_by_stat');
Route::get('/view_batch_tuv_audit_by_stat', 'TUVController@view_batch_tuv_audit_by_stat');
Route::get('/view_report_tuv_audit_by_stat', 'TUVController@view_report_tuv_audit_by_stat');
Route::get('/get_chart_tuv_audit_by_stat', 'TUVController@get_chart_tuv_audit_by_stat');
Route::get('/view_tuv_audit_history', 'TUVController@view_tuv_audit_history');
Route::get('/view_single_tuv_audit', 'TUVController@view_single_tuv_audit');
Route::get('/send_tuv_batch', 'TUVController@send_tuv_batch');
Route::post('/approve_tuv_batch', 'TUVController@approve_tuv_batch');
Route::get('/view_tuv_audit_for_approval', 'TUVController@view_tuv_audit_for_approval');
Route::get('/view_tuv_audit_for_approval_v2', 'TUVController@view_tuv_audit_for_approval_v2');
Route::post('/change_tuv_audit_stat', 'TUVController@change_tuv_audit_stat');
Route::get('/get_tuv_chart_by_audit_category', 'TUVController@get_tuv_chart_by_audit_category');
Route::get('/get_tuv_chart_by_classification', 'TUVController@get_tuv_chart_by_classification');
Route::get('/get_tuv_chart_by_areas_for_improvement', 'TUVController@get_tuv_chart_by_areas_for_improvement');
Route::get('/get_tuv_unique_evaluation_items', 'TUVController@get_tuv_unique_evaluation_items');
Route::get('/get_tuv_chart_by_classification2', 'TUVController@get_tuv_chart_by_classification2');
Route::get('/get_tuv_chart_by_areas_for_improvement2', 'TUVController@get_tuv_chart_by_areas_for_improvement2');
Route::get('/get_tuv_due_date', 'TUVController@get_tuv_due_date');
Route::get('/get_tuv_over_due_date', 'TUVController@get_tuv_over_due_date');
Route::get('/get_tuv_before_due_date', 'TUVController@get_tuv_before_due_date');

Route::get('/dt_get_tuv_audit_attachments', 'TUVController@dt_get_tuv_audit_attachments')->name('dt_get_tuv_audit_attachments');
// Route::get('/dt_get_attachments', 'TUVController@dt_get_attachments')->name('dt_get_attachments');

// For testing
// Route::get('/auto_email', 'TUVController@auto_email');

// Customer Controller
Route::get('/view_customer_audit_by_stat', 'CustomerController@view_customer_audit_by_stat');
Route::get('/view_customer_audit_by_stat_v2', 'CustomerController@view_customer_audit_by_stat_v2');
Route::get('/view_archived_customer_audit_by_stat', 'CustomerController@view_archived_customer_audit_by_stat');
Route::post('/add_customer_audit', 'CustomerController@add_customer_audit');
Route::post('/add_customer_audit_v2', 'CustomerController@add_customer_audit_v2');
Route::post('/close_customer_audit', 'CustomerController@close_customer_audit');
Route::post('/open_customer_audit', 'CustomerController@open_customer_audit');
Route::post('/done_customer_audit', 'CustomerController@done_customer_audit');
Route::post('/pend_customer_audit', 'CustomerController@pend_customer_audit');
Route::post('/edit_customer_audit', 'CustomerController@edit_customer_audit');
Route::post('/edit_open_customer_audit', 'CustomerController@edit_open_customer_audit');
Route::get('/get_customer_audit_by_id', 'CustomerController@get_customer_audit_by_id');
Route::get('/view_open_customer_audit_by_stat', 'CustomerController@view_open_customer_audit_by_stat');
Route::get('/get_chart_customer_audit_by_stat', 'CustomerController@get_chart_customer_audit_by_stat');
Route::get('/view_customer_audit_history', 'CustomerController@view_customer_audit_history');
Route::get('/view_report_customer_audit_by_stat', 'CustomerController@view_report_customer_audit_by_stat');
Route::get('/get_unique_customer_names', 'CustomerController@get_unique_customer_names');
Route::get('/view_single_customer_audit', 'CustomerController@view_single_customer_audit');
Route::post('/send_customer_batch', 'CustomerController@send_customer_batch');
Route::get('/view_batch_customer_audit_by_stat', 'CustomerController@view_batch_customer_audit_by_stat');
Route::post('/change_customer_audit_stat', 'CustomerController@change_customer_audit_stat');
Route::get('/test_customer_auto_email', 'CustomerController@test_customer_auto_email');
Route::post('/approve_customer_batch', 'CustomerController@approve_customer_batch');
Route::get('/view_customer_audit_for_approval', 'CustomerController@view_customer_audit_for_approval');
Route::get('/view_customer_audit_for_approval_v2', 'CustomerController@view_customer_audit_for_approval_v2');
Route::post('/change_customer_cus_audit_stat', 'CustomerController@change_customer_cus_audit_stat');
Route::get('/get_customer_chart_by_areas_for_improvement', 'CustomerController@get_customer_chart_by_areas_for_improvement');
Route::get('/get_customer_chart_by_customer_name', 'CustomerController@get_customer_chart_by_customer_name');
Route::get('/get_customer_unique_evaluation_items', 'CustomerController@get_customer_unique_evaluation_items');
Route::get('/update_all_person_in_charge', 'CustomerController@update_all_person_in_charge');
Route::get('/get_customers', 'CustomerController@get_customers')->name('get_customers');


// Internal Controller
Route::get('/view_internal_audit_by_stat', 'InternalController@view_internal_audit_by_stat');
Route::get('/view_internal_audit_by_stat_v2', 'InternalController@view_internal_audit_by_stat_v2');
Route::get('/view_archived_internal_audit_by_stat', 'InternalController@view_archived_internal_audit_by_stat');
Route::get('/view_internal_audit_for_approval', 'InternalController@view_internal_audit_for_approval');
Route::get('/view_internal_audit_for_approval_v2', 'InternalController@view_internal_audit_for_approval_v2');
Route::post('/add_internal_audit', 'InternalController@add_internal_audit');
Route::post('/add_internal_audit_v2', 'InternalController@add_internal_audit_v2');
Route::post('/open_internal_audit', 'InternalController@open_internal_audit');
Route::post('/done_internal_audit', 'InternalController@done_internal_audit');
Route::post('/close_internal_audit', 'InternalController@close_internal_audit');
Route::post('/edit_internal_audit', 'InternalController@edit_internal_audit');
Route::post('/pend_internal_audit', 'InternalController@pend_internal_audit');
Route::post('/internal_qad_approval', 'InternalController@internal_qad_approval');
Route::post('/internal_os_approval', 'InternalController@internal_os_approval');
Route::post('/edit_open_internal_audit', 'InternalController@edit_open_internal_audit');
Route::get('/get_internal_audit_by_id', 'InternalController@get_internal_audit_by_id');
Route::get('/view_open_internal_audit_by_stat', 'InternalController@view_open_internal_audit_by_stat');
Route::get('/get_chart_internal_audit_by_stat', 'InternalController@get_chart_internal_audit_by_stat');
Route::get('/view_internal_audit_history', 'InternalController@view_internal_audit_history');
Route::get('/view_report_internal_audit_by_stat', 'InternalController@view_report_internal_audit_by_stat');
Route::get('/view_single_internal_audit', 'InternalController@view_single_internal_audit');
Route::get('/get_int_audit_by_rep_ctrl_no', 'InternalController@get_int_audit_by_rep_ctrl_no');
Route::post('/send_internal_batch', 'InternalController@send_internal_batch');
Route::get('/view_batch_internal_audit_by_stat', 'InternalController@view_batch_internal_audit_by_stat');
Route::post('/approve_internal_batch', 'InternalController@approve_internal_batch');
Route::post('/change_internal_audit_stat', 'InternalController@change_internal_audit_stat');
Route::get('/get_internal_unique_evaluation_items', 'InternalController@get_internal_unique_evaluation_items');
Route::get('/get_internal_due_date', 'InternalController@get_internal_due_date');
Route::get('/get_internal_over_due_date', 'InternalController@get_internal_over_due_date');
Route::get('/get_internal_before_due_date', 'InternalController@get_internal_before_due_date');
Route::get('/internal_update_all_person_in_charge', 'InternalController@internal_update_all_person_in_charge');
Route::get('/update_qad_approval', 'InternalController@update_qad_approval');

// Supplier Controller
Route::get('/view_supplier_audit_by_stat', 'SupplierController@view_supplier_audit_by_stat');
Route::get('/view_supplier_audit_by_stat_v2', 'SupplierController@view_supplier_audit_by_stat_v2');
Route::get('/view_archived_supplier_audit_by_stat', 'SupplierController@view_archived_supplier_audit_by_stat');
Route::post('/add_supplier_audit', 'SupplierController@add_supplier_audit');
Route::post('/add_supplier_audit_v2', 'SupplierController@add_supplier_audit_v2');
Route::post('/open_supplier_audit', 'SupplierController@open_supplier_audit');
Route::post('/done_supplier_audit', 'SupplierController@done_supplier_audit');
Route::post('/close_supplier_audit', 'SupplierController@close_supplier_audit');
Route::post('/edit_supplier_audit', 'SupplierController@edit_supplier_audit');
Route::post('/pend_supplier_audit', 'SupplierController@pend_supplier_audit');
Route::post('/edit_open_supplier_audit', 'SupplierController@edit_open_supplier_audit');
Route::get('/get_supplier_audit_by_id', 'SupplierController@get_supplier_audit_by_id');
Route::get('/view_open_supplier_audit_by_stat', 'SupplierController@view_open_supplier_audit_by_stat');
Route::get('/get_chart_supplier_audit_by_stat', 'SupplierController@get_chart_supplier_audit_by_stat');
Route::get('/view_supplier_audit_history', 'SupplierController@view_supplier_audit_history');
Route::get('/view_report_supplier_audit_by_stat', 'SupplierController@view_report_supplier_audit_by_stat');
Route::get('/view_single_supplier_audit', 'SupplierController@view_single_supplier_audit');
Route::get('/get_supp_audit_by_rep_ctrl_no', 'SupplierController@get_supp_audit_by_rep_ctrl_no');
Route::post('/send_supplier_batch', 'SupplierController@send_supplier_batch');
Route::post('/approve_supplier_batch', 'SupplierController@approve_supplier_batch');

Route::get('/view_batch_supplier_audit_by_stat', 'SupplierController@view_batch_supplier_audit_by_stat');
Route::get('/view_supplier_audit_for_approval', 'SupplierController@view_supplier_audit_for_approval');
Route::get('/view_supplier_audit_for_approval_v2', 'SupplierController@view_supplier_audit_for_approval_v2');
Route::post('/change_supplier_audit_stat', 'SupplierController@change_supplier_audit_stat');
Route::get('/get_supplier_unique_evaluation_items', 'SupplierController@get_supplier_unique_evaluation_items');

// Department
Route::get('/get_department_by_stat', 'DepartmentController@get_department_by_stat');

// Reports
Route::get('/tuv_export', 'TUVController@tuv_export');
Route::get('/customer_export', 'CustomerController@customer_export');
Route::get('/internal_export', 'InternalController@internal_export');
Route::get('/supplier_export', 'SupplierController@supplier_export');

// Standard Clause
Route::get('/view_standard_clause_by_stat', 'StandardClauseController@view_standard_clause_by_stat');
Route::post('/add_standard_clause', 'StandardClauseController@add_standard_clause');
Route::post('/archive_standard_clause', 'StandardClauseController@archive_standard_clause');
Route::post('/restore_standard_clause', 'StandardClauseController@restore_standard_clause');
Route::get('/get_standard_clause_by_id', 'StandardClauseController@get_standard_clause_by_id');
Route::post('/edit_standard_clause', 'StandardClauseController@edit_standard_clause');
Route::get('/get_standard_clause_by_stat', 'StandardClauseController@get_standard_clause_by_stat');

// Standard Clause Content
Route::get('/view_standard_clause_content_by_stat', 'StandardClauseContentController@view_standard_clause_content_by_stat');
Route::post('/add_standard_clause_content', 'StandardClauseContentController@add_standard_clause_content');
Route::post('/archive_standard_clause_content', 'StandardClauseContentController@archive_standard_clause_content');
Route::post('/restore_standard_clause_content', 'StandardClauseContentController@restore_standard_clause_content');
Route::get('/get_standard_clause_content_by_id', 'StandardClauseContentController@get_standard_clause_content_by_id');
Route::post('/edit_standard_clause_content', 'StandardClauseContentController@edit_standard_clause_content');

// Evaluation Item
Route::get('/view_evaluation_item_by_stat', 'EvaluationItemController@view_evaluation_item_by_stat');
Route::post('/add_evaluation_item', 'EvaluationItemController@add_evaluation_item');
Route::post('/archive_evaluation_item', 'EvaluationItemController@archive_evaluation_item');
Route::post('/restore_evaluation_item', 'EvaluationItemController@restore_evaluation_item');
Route::get('/get_evaluation_item_by_id', 'EvaluationItemController@get_evaluation_item_by_id');
Route::post('/edit_evaluation_item', 'EvaluationItemController@edit_evaluation_item');
Route::get('/get_evaluation_item_by_stat', 'EvaluationItemController@get_evaluation_item_by_stat');

// AQMS Audit Schedule
// Route::get('/view_aqms_sched', 'AQMSAuditScheduleController@view_aqms_sched')->name('view_aqms_sched');
// Route::get('/get_aqms_sched_between_year', 'AQMSAuditScheduleController@get_aqms_sched_between_year');
// Route::post('/add_aqms_sched', 'AQMSAuditScheduleController@add_aqms_sched');
// Route::post('/edit_aqms_sched', 'AQMSAuditScheduleController@edit_aqms_sched');
// Route::get('/get_aqms_sched_by_id', 'AQMSAuditScheduleController@get_aqms_sched_by_id');
// Route::post('/archive_aqms_sched', 'AQMSAuditScheduleController@archive_aqms_sched');
// Route::post('/restore_aqms_sched', 'AQMSAuditScheduleController@restore_aqms_sched');

// AQMS Audit Schedule - 2020-09-05
Route::post('/aqms_save_process', 'AQMSAuditScheduleController@aqms_save_process')->name('aqms_save_process');
Route::get('/get_aqms_sched_by_audit_schedule_id', 'AQMSAuditScheduleController@get_aqms_sched_by_audit_schedule_id')->name('get_aqms_sched_by_audit_schedule_id');
Route::get('/get_aqms_process_by_id', 'AQMSAuditScheduleController@get_aqms_process_by_id')->name('get_aqms_process_by_id');
Route::get('/aqms_save_process_details', 'AQMSAuditScheduleController@aqms_save_process_details')->name('aqms_save_process_details');
Route::post('/aqms_save_process_details', 'AQMSAuditScheduleController@aqms_save_process_details')->name('aqms_save_process_details');
Route::get('/get_aqms_process_details_by_id', 'AQMSAuditScheduleController@get_aqms_process_details_by_id')->name('get_aqms_process_details_by_id');
Route::get('/view_aqms_years_by_schedule_id', 'AQMSAuditScheduleController@view_aqms_years_by_schedule_id')->name('view_aqms_years_by_schedule_id');
Route::post('/aqms_save_year', 'AQMSAuditScheduleController@aqms_save_year')->name('aqms_save_year');
Route::get('/get_aqms_year_by_id', 'AQMSAuditScheduleController@get_aqms_year_by_id')->name('get_aqms_year_by_id');

// EMS Audit Schedule
// Route::get('/view_ems_sched', 'EMSAuditScheduleController@view_ems_sched');
// Route::post('/add_ems_sched', 'EMSAuditScheduleController@add_ems_sched');
// Route::post('/edit_ems_sched', 'EMSAuditScheduleController@edit_ems_sched');
// Route::get('/get_ems_sched_by_id', 'EMSAuditScheduleController@get_ems_sched_by_id');
// Route::post('/archive_ems_sched', 'EMSAuditScheduleController@archive_ems_sched');
// Route::post('/restore_ems_sched', 'EMSAuditScheduleController@restore_ems_sched');

// EMS Audit Schedule - 2020-14-05
Route::post('/ems_save_process', 'EMSAuditScheduleController@ems_save_process')->name('ems_save_process');
Route::get('/get_ems_sched_by_audit_schedule_id', 'EMSAuditScheduleController@get_ems_sched_by_audit_schedule_id')->name('get_ems_sched_by_audit_schedule_id');
Route::get('/get_ems_process_by_id', 'EMSAuditScheduleController@get_ems_process_by_id')->name('get_ems_process_by_id');
Route::get('/ems_save_process_details', 'EMSAuditScheduleController@ems_save_process_details')->name('ems_save_process_details');
Route::post('/ems_save_process_details', 'EMSAuditScheduleController@ems_save_process_details')->name('ems_save_process_details');
Route::get('/get_ems_process_details_by_id', 'EMSAuditScheduleController@get_ems_process_details_by_id')->name('get_ems_process_details_by_id');
Route::get('/view_ems_years_by_schedule_id', 'EMSAuditScheduleController@view_ems_years_by_schedule_id')->name('view_ems_years_by_schedule_id');
Route::post('/ems_save_year', 'EMSAuditScheduleController@ems_save_year')->name('ems_save_year');
Route::get('/get_ems_year_by_id', 'EMSAuditScheduleController@get_ems_year_by_id')->name('get_ems_year_by_id');

// Email Recipient
Route::get('/view_email_recipient_by_stat', 'EmailRecipientController@view_email_recipient_by_stat');
Route::post('/add_email_recipient', 'EmailRecipientController@add_email_recipient');
Route::post('/remove_email_recipient', 'EmailRecipientController@remove_email_recipient');
// Route::post('/archive_other_section_recepient', 'OtherSectionRecipientController@archive_other_section_recepient');
// Route::post('/restore_other_section_recepient', 'OtherSectionRecipientController@restore_other_section_recepient');
// Route::post('/edit_other_section_recepient', 'OtherSectionRecipientController@edit_other_section_recepient');

// Approver
Route::get('/view_approver_by_stat', 'ApproverController@view_approver_by_stat');
Route::post('/add_approver', 'ApproverController@add_approver');
Route::post('/remove_approver', 'ApproverController@remove_approver');

// AUDIT SCHEDULE DETAILS
Route::get('/view_schedule_details', 'AuditScheduleDetailController@view_schedule_details');
Route::post('/add_audit_schedule_details', 'AuditScheduleDetailController@add_audit_schedule_details');

Route::get('/audit_schedule_details/{id}', 'AuditScheduleDetailController@audit_schedule_details');
Route::get('/get_sched_details_by_id', 'AuditScheduleDetailController@get_sched_details_by_id');
Route::post('/audit_sched_details_action', 'AuditScheduleDetailController@audit_sched_details_action');

// QC PATROL
Route::get('/factors', function(){
	return view('qc_patrol.factors');
})->name('factors');

Route::get('/qc_patrol', function(){
	return view('qc_patrol.qc_patrol');
})->name('qc_patrol');

Route::get('/factor_item_lists', function(){
	return view('qc_patrol.factor_item_lists');
})->name('factor_item_lists');

Route::get('/customer', function(){
	return view('customer_management');
})->name('customer');

// FACTOR CONTROLLER
Route::get('/view_factors', 'FactorController@view_factors')->name('view_factors');
Route::post('/save_factor', 'FactorController@save_factor')->name('save_factor');
Route::get('/get_factor_by_id', 'FactorController@get_factor_by_id')->name('get_factor_by_id');
Route::post('/action_factor', 'FactorController@action_factor')->name('action_factor');

// FACTOR ITEM LIST CONTROLLER
Route::get('/view_factor_item_lists', 'FactorItemListController@view_factor_item_lists')->name('view_factor_item_lists');
Route::post('/save_factor_item_list', 'FactorItemListController@save_factor_item_list')->name('save_factor_item_list');
Route::get('/get_factor_item_list_by_id', 'FactorItemListController@get_factor_item_list_by_id')->name('get_factor_item_list_by_id');
Route::post('/action_factor_item_list', 'FactorItemListController@action_factor_item_list')->name('action_factor_item_list');


Route::post('/proceed_approval_audit', 'CommonController@proceed_approval_audit')->name('proceed_approval_audit');
Route::post('/approve_audit_result', 'CommonController@approve_audit_result')->name('approve_audit_result');
Route::post('/disapprove_audit_result', 'CommonController@disapprove_audit_result')->name('disapprove_audit_result');
Route::post('/delete_attachment', 'CommonController@delete_attachment')->name('delete_attachment');
Route::get('/dt_get_attachments', 'CommonController@dt_get_attachments')->name('dt_get_attachments');

Route::post('save_customer', 'CustomerController@save_customer')->name('save_customer');
Route::get('dt_get_customer_list', 'CustomerController@dt_get_customer_list')->name('dt_get_customer_list');
Route::get('get_customer_list', 'CustomerController@get_customer_list')->name('get_customer_list');
