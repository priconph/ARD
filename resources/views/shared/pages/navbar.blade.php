<!-- main menu-->
<div data-scroll-to-active="true" class="main-menu menu-fixed menu-dark menu-accordion menu-shadow">
  <!-- main menu header-->
  <!-- <div class="main-menu-header">
    <input type="text" placeholder="Search" class="menu-search form-control round"/>
  </div> -->
  <!-- / main menu header-->
  <!-- main menu content-->
  <div class="main-menu-content">
    <ul id="main-menu-navigation" data-menu="menu-navigation" class="navigation navigation-main">
      <li class="active nav-item"><a href="../RapidX/"><i class="icon-home3"></i><span data-i18n="nav.bootstrap_tables.table_basic" class="menu-title">RapidX</span></a>
          </li>
    </ul>
    <ul id="main-menu-navigation" data-menu="menu-navigation" class="navigation navigation-main">
      <li class="nav-item"><a href="../QADS/"><i class="icon-arrow-left"></i><span data-i18n="nav.bootstrap_tables.table_basic" class="menu-title">QUADS</span></a>
          </li>
    </ul>
    <ul id="main-menu-navigation" data-menu="menu-navigation" class="navigation navigation-main">
      <li class="nav-item"><a href="{{ route('home') }}"><i class="icon-dashboard"></i><span data-i18n="nav.bootstrap_tables.table_basic" class="menu-title">Dashboard</span></a>
          </li>
    </ul>
    {{-- <ul id="main-menu-navigation" data-menu="menu-navigation" class="navigation navigation-main">
      <li class="nav-item has-sub">
        <a href="#">
          <i class="icon-files-o"></i>
          <span class="menu-title" data-i18n="nav.pages.main">Audit Result V2</span>
        </a>
        <ul class="menu-content">
          <li class=" nav-item"><a href="{{ route('audit_result_v2') }}"><i class="icon-paper"></i><span data-i18n="nav.bootstrap_tables.table_basic" class="menu-title">Audit Result v2</span></a>
          </li>
          <li class=" nav-item"><a href="{{ route('archived_v2') }}"><i class="icon-bin"></i><span data-i18n="nav.bootstrap_tables.table_basic" class="menu-title">Archived Repository v2</span></a>
          </li>
          <li class=" nav-item"><a href="{{ route('for_approval_v2') }}"><i class="icon-paper"></i><span data-i18n="nav.bootstrap_tables.table_basic" class="menu-title">For Approval v2</span></a>
          </li>
        </ul>
      </li>
    </ul> --}}
    
    <ul id="main-menu-navigation" data-menu="menu-navigation" class="navigation navigation-main">
      <li class=" nav-item"><a href="{{ route('audit_result_v2') }}"><i class="icon-paper"></i><span data-i18n="nav.bootstrap_tables.table_basic" class="menu-title">Audit Result v2</span></a>
          </li>
    </ul>
    <ul id="main-menu-navigation" data-menu="menu-navigation" class="navigation navigation-main">
      <li class=" nav-item"><a href="{{ route('archived_v2') }}"><i class="icon-bin"></i><span data-i18n="nav.bootstrap_tables.table_basic" class="menu-title">Archived Repository v2</span></a>
          </li>
    </ul>
    <ul id="main-menu-navigation" data-menu="menu-navigation" class="navigation navigation-main">
      <li class=" nav-item"><a href="{{ route('for_approval_v2') }}"><i class="icon-paper"></i><span data-i18n="nav.bootstrap_tables.table_basic" class="menu-title">For Approval v2</span></a>
          </li>
    </ul>

    {{-- <ul id="main-menu-navigation" data-menu="menu-navigation" class="navigation navigation-main">
      <li class=" nav-item"><a href="{{ route('audit_result') }}"><i class="icon-paper"></i><span data-i18n="nav.bootstrap_tables.table_basic" class="menu-title">Audit Result</span></a>
          </li>
    </ul>
    <ul id="main-menu-navigation" data-menu="menu-navigation" class="navigation navigation-main">
      <li class=" nav-item"><a href="{{ route('archived') }}"><i class="icon-bin"></i><span data-i18n="nav.bootstrap_tables.table_basic" class="menu-title">Archived Repository</span></a>
          </li>
    </ul>
    <!-- <ul id="main-menu-navigation" data-menu="menu-navigation" class="navigation navigation-main">
      <li class=" nav-item"><a href="{{ route('archived_audit_result') }}"><i class="icon-paper"></i><span data-i18n="nav.bootstrap_tables.table_basic" class="menu-title">Archived Audit Result</span></a>
          </li>
    </ul> -->
    <ul id="main-menu-navigation" data-menu="menu-navigation" class="navigation navigation-main">
      <li class=" nav-item"><a href="{{ route('for_approval') }}"><i class="icon-paper"></i><span data-i18n="nav.bootstrap_tables.table_basic" class="menu-title">For Approval</span></a>
          </li>
    </ul> --}}
    <!-- <ul id="main-menu-navigation" data-menu="menu-navigation" class="navigation navigation-main">
      <li class=" nav-item"><a href="{{ route('audit_result_batch') }}"><i class="icon-paper"></i><span data-i18n="nav.bootstrap_tables.table_basic" class="menu-title">Audit Result Batch</span></a>
          </li>
    </ul> -->
    {{-- <ul id="main-menu-navigation" data-menu="menu-navigation" class="navigation navigation-main">
      <li class=" nav-item"><a href="{{ route('reports') }}"><i class="icon-files-o"></i><span data-i18n="nav.bootstrap_tables.table_basic" class="menu-title">Reports</span></a>
          </li>
    </ul> --}}
    {{-- <ul id="main-menu-navigation" data-menu="menu-navigation" class="navigation navigation-main">
      <li class=" nav-item"><a href="{{ route('charts') }}"><i class="icon-pie-chart2"></i><span data-i18n="nav.bootstrap_tables.table_basic" class="menu-title">Charts</span></a>
          </li>
    </ul> --}}
    <ul id="main-menu-navigation" data-menu="menu-navigation" class="navigation navigation-main">
      <li class=" nav-item"><a href="{{ route('email_recipient') }}"><i class="icon-envelope-o"></i><span data-i18n="nav.bootstrap_tables.table_basic" class="menu-title">Email Recipient</span></a>
          </li>
    </ul>
    {{-- <ul id="main-menu-navigation" data-menu="menu-navigation" class="navigation navigation-main">
      <li class=" nav-item"><a href="{{ route('approver') }}"><i class="icon-user"></i><span data-i18n="nav.bootstrap_tables.table_basic" class="menu-title">Approver</span></a>
          </li>
    </ul> --}}
    {{-- <ul id="main-menu-navigation" data-menu="menu-navigation" class="navigation navigation-main">
      <li class=" nav-item"><a href="{{ route('evaluation_item') }}"><i class="icon-files-o"></i><span data-i18n="nav.bootstrap_tables.table_basic" class="menu-title">Evaluation Item</span></a>
          </li>
    </ul> --}}
    {{-- <ul id="main-menu-navigation" data-menu="menu-navigation" class="navigation navigation-main">
      <li class=" nav-item"><a href="{{ route('standard_clause') }}"><i class="icon-files-o"></i><span data-i18n="nav.bootstrap_tables.table_basic" class="menu-title">Standard Clause</span></a>
          </li>
    </ul> --}}
    {{-- <ul id="main-menu-navigation" data-menu="menu-navigation" class="navigation navigation-main">
      <li class=" nav-item"><a href="{{ route('audit_schedule') }}"><i class="icon-calendar4"></i><span data-i18n="nav.bootstrap_tables.table_basic" class="menu-title">Audit Schedule</span></a>
          </li>
    </ul> --}}
    {{-- <ul id="main-menu-navigation" data-menu="menu-navigation" class="navigation navigation-main">
      <li class=" nav-item"><a href="{{ route('audit_schedule') }}"><i class="icon-files-o"></i><span data-i18n="nav.bootstrap_tables.table_basic" class="menu-title">Audit Plan</span></a>
          </li>
    </ul> --}}
    <ul id="main-menu-navigation" data-menu="menu-navigation" class="navigation navigation-main">
      <li class=" nav-item"><a href="{{ route('customer') }}"><i class="icon-cog2"></i><span data-i18n="nav.bootstrap_tables.table_basic" class="menu-title">Customer</span></a>
          </li>
    </ul>
    
    <!-- <ul id="main-menu-navigation" data-menu="menu-navigation" class="navigation navigation-main">
      <li class=" nav-item"><a href="ARD/"><i class="icon-paper"></i><span data-i18n="nav.bootstrap_tables.table_basic" class="menu-title">Audit</span></a>
          </li>
    </ul> -->
    <!-- <ul id="main-menu-navigation" data-menu="menu-navigation" class="navigation navigation-main">
      <li class=" nav-item"><a href="reports"><i class="icon-clipboard4"></i><span data-i18n="nav.bootstrap_tables.table_basic" class="menu-title">Reports</span></a>
          </li>
    </ul> -->
  </div>
  <!-- /main menu content-->
  <!-- main menu footer-->
  <!-- include includes/menu-footer-->
  <!-- main menu footer-->
</div>
<!-- / main menu-->