@extends('layouts.master_layout')
@section('title', 'Approver')

@section('content')
    <div class="app-content content container-fluid">
        <div class="content-wrapper">
            <div class="content-header row">
            <div class="content-header-left col-md-6 col-xs-12 mb-1">
                <h2 class="content-header-title">Customer</h2>
            </div>
            <div class="content-header-right breadcrumbs-right breadcrumbs-top col-md-6 col-xs-12">
                <div class="breadcrumb-wrapper col-xs-12">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a>
                    </li>
                    <li class="breadcrumb-item active">Customer
                    </li>
                </ol>
                </div>
            </div>
            </div>
            <div class="content-body"><!-- Feather icons section start -->
            <section id="feather-icons">
                <div class="row">
                    <div class="col-xs-12">
                        <div class="card">
                            <div class="card-body collapse in">
                                <div class="card-block">
                                    <div style="float: right;">
                                        <button class="btn btn-success" id="btnShowAddCustomerModal" data-keyboard="false"><i class=""></i> Add Customer</button>
                                    </div>
                                    <br><br>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover" id="tblCustomer" width="100%">
                                            <thead>
                                                <tr>
                                                    <th>Customer Name</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                        </table>
                                        <br><br>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- // Feather icons section end -->
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalCustomer" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
               
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="icon-ini"></i> <span id="modalCustomerTitle">Add Approver</span></h4>
                </div>
                <form id="formCustomer" autocomplete="off">
                    @csrf
                    <div class="modal-body">
                        <input type="text" id="txtCustomerId" name="customer_id" style="display: none;">
                        <div class="form-group">
                            <label>Customer Name:</label>
                            <input type="text" name="customer_name" id="txtCustName" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-sm btn-success" id="btnSaveCustomer">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
@endsection

@section('js_content')
<script>
    let dtCustomer
    $(document).ready(function () {
        $('#btnShowAddCustomerModal').on('click', function(){
            $('#modalCustomer').modal('show');
            $('#modalCustomerTitle').text('Add Customer')
        })

        $('#modalCustomer').on('hidden.bs.modal', function(){
            console.log('modalCustomer closed');
            $('#formCustomer')[0].reset();
        });

        dtCustomer = $("#tblCustomer").DataTable({
            "processing" : true,
            "serverSide" : true,
            "ajax" : {
                url: "{{ route('dt_get_customer_list') }}",
                // data: function (param){
                //     param.po = $("#id").val();
                // }
            },
            fixedHeader: true,
            "columns":[
                { "data" : "customer_name" },
                { "data" : "action", orderable:false, searchable:false },
            ],
            "columnDefs": [
                {"className": "dt-center", "targets": "_all"},
            ],
        });//end of dataTable

        $('#formCustomer').on('submit', function(e){
            e.preventDefault();
            $.ajax({
                type: "POST",
                url: "{{ route('save_customer') }}",
                data: $(this).serialize(),
                dataType: "json",
                beforeSend: function(){
                    $('#btnSaveCustomer').prop('disabled', true);
                },
                success: function (response) {
                    $('#btnSaveCustomer').prop('disabled', false);
                    if(!response.result){
                        Swal({
                            position: 'top-end',
                            type: 'error',
                            title: 'Error saving customer!',
                            showConfirmButton: false,
                            timer: 1500,
                            customClass: 'swal-wide',
                        });
                    }
                    else{
                        Swal({
                            position: 'top-end',
                            type: 'success',
                            title: 'Customer saved successfully!',
                            showConfirmButton: false,
                            timer: 1500,
                            customClass: 'swal-wide',
                        });
                        $('#modalCustomer').modal('hide');
                        dtCustomer.draw();
                    }

                },
                error: function(xhr, status, error){
                    $('#btnSaveCustomer').prop('disabled', false);
                    console.log('xhr: ' + xhr + "\n" + "status: " + status + "\n" + "error: " + error);
                }
            });
        })

        $(document).on('click','.btnEditCustomer', function(){
            $('#txtCustomerId').val($(this).attr('data-id'));
            $('#txtCustName').val($(this).attr('data-name'));
            $('#modalCustomer').modal('show');
        })

        $(document).on('click', '.btnDeleteCustomer', function(){
            let message = $(this).attr('data-status') == '0' ? "Deactivate" : "Activate";
            Swal.fire({
                title: "Are you sure you want to " + message + " this customer?",
                // showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: "Yes",
                // denyButtonText: `Don't save`
            }).then((result) => {
                if (result.value) {
                    updateCustomerStatus($(this).attr('data-id'), $(this).attr('data-status'));
                }
            })
        });
    });

    
</script>
@endsection