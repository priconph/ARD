@extends('layouts.master_layout')
@section('title', 'Dashboard')

@section('content')
  <div class="app-content content container-fluid">
    <div class="content-wrapper">
      <div class="content-header row">
      </div>
      <div class="content-body"><!-- stats -->
        <div class="row" id="divRowQADAdmin" style="display: none;">
            <div class="col-xl-3 col-lg-6 col-xs-12">
                <a href="{{ route('audit_result_v2') }}">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-block">
                                <div class="media">
                                    <div class="media-body text-xs-left">
                                        <!-- <h3 class="deep-orange">0</h3> -->
                                        <span>Audit Result</span>
                                    </div>
                                    <div class="media-right media-middle">
                                        <i class="icon-paper deep-orange font-large-2 float-xs-right"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xl-3 col-lg-6 col-xs-12">
                <a href="{{ route('for_approval_v2') }}">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-block">
                                <div class="media">
                                    <div class="media-body text-xs-left">
                                        <!-- <h3 class="deep-orange">0</h3> -->
                                        <span>For Approval</span>
                                    </div>
                                    <div class="media-right media-middle">
                                        <i class="icon-paper deep-orange font-large-2 float-xs-right"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            {{-- <div class="col-xl-3 col-lg-6 col-xs-12">
                <a href="{{ route('reports') }}">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-block">
                                <div class="media">
                                    <div class="media-body text-xs-left">
                                        <!-- <h3 class="cyan">0</h3> -->
                                        <span>Reports</span>
                                    </div>
                                    <div class="media-right media-middle">
                                        <i class="icon-files-o cyan font-large-2 float-xs-right"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div> --}}
            {{-- <div class="col-xl-3 col-lg-6 col-xs-12">
                <a href="{{ route('charts') }}">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-block">
                                <div class="media">
                                    <div class="media-body text-xs-left">
                                        <!-- <h3 class="pink">0</h3> -->
                                        <span>Charts</span>
                                    </div>
                                    <div class="media-right media-middle">
                                        <i class="icon-pie-chart2 pink font-large-2 float-xs-right"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div> --}}
            <div class="col-xl-3 col-lg-6 col-xs-12">
                <a href="{{ route('standard_clause') }}">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-block">
                                <div class="media">
                                    <div class="media-body text-xs-left">
                                        <!-- <h3 class="cyan">0</h3> -->
                                        <span>Standard Clause</span>
                                    </div>
                                    <div class="media-right media-middle">
                                        <i class="icon-files-o cyan font-large-2 float-xs-right"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xl-3 col-lg-6 col-xs-12">
                <a href="{{ route('email_recipient') }}">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-block">
                                <div class="media">
                                    <div class="media-body text-xs-left">
                                        <!-- <h3 class="pink">0</h3> -->
                                        <span>Email Recipient</span>
                                    </div>
                                    <div class="media-right media-middle">
                                        <i class="icon-envelope-o pink font-large-2 float-xs-right"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            {{-- <div class="col-xl-3 col-lg-6 col-xs-12" style="display: block;">
                <a href="{{ route('audit_schedule') }}">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-block">
                                <div class="media">
                                    <div class="media-body text-xs-left">
                                        <!-- <h3 class="pink">0</h3> -->
                                        <span>Audit Schedule</span>
                                    </div>
                                    <div class="media-right media-middle">
                                        <i class="icon-calendar4 pink font-large-2 float-xs-right"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div> --}}

            {{-- <div class="col-xl-3 col-lg-6 col-xs-12">
                <a href="{{ route('approver') }}">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-block">
                                <div class="media">
                                    <div class="media-body text-xs-left">
                                        <!-- <h3 class="deep-orange">0</h3> -->
                                        <span>Approver</span>
                                    </div>
                                    <div class="media-right media-middle">
                                        <i class="icon-user deep-orange font-large-2 float-xs-right"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div> --}}

            {{-- <div class="col-xl-3 col-lg-6 col-xs-12" style="display: none;">
                <a href="{{ route('audit_plan') }}">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-block">
                                <div class="media">
                                    <div class="media-body text-xs-left">
                                        <!-- <h3 class="deep-orange">0</h3> -->
                                        <span>Audit Plan</span>
                                    </div>
                                    <div class="media-right media-middle">
                                        <i class="icon-paper deep-orange font-large-2 float-xs-right"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div> --}}
            <!-- <div class="col-xl-3 col-lg-6 col-xs-12">
                <a href="alert_system">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-block">
                                <div class="media">
                                    <div class="media-body text-xs-left">
                                        <h3 class="warning">0</h3>
                                        <span>Alert System</span>
                                    </div>
                                    <div class="media-right media-middle">
                                        <i class="icon-bell3 warning font-large-2 float-xs-right"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div> -->
        </div>

       
      </div>
    </div>
  </div>

  <br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
@endsection

@section('js_content')
    <script type="text/javascript">
        $(document).ready(function(){
            let globalUserAccessLevelId = $("#txtGlobalUserAccessLevelId").val();

            if(globalUserAccessLevelId == 0) {
                $("#divRowQADAdmin").css('display', 'block');
                $("#divRowOtherSec").css('display', 'block');
            }
            else if(globalUserAccessLevelId == 4) {
                $("#divRowQADAdmin").css('display', 'block');
                $("#divRowOtherSec").css('display', 'none');
            }
            else if(globalUserAccessLevelId == 5) {
                $("#divRowQADAdmin").css('display', 'none');
                $("#divRowOtherSec").css('display', 'block');
            }
        });
    </script>
@endsection