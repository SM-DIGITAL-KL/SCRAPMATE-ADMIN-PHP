
@php
    // Get user data from session (since we're using Node.js API authentication)
    $auth_user = (object)[
        'id' => session('user_id'),
        'user_type' => session('user_type', 'A'),
        'name' => session('user_name', 'Admin'),
        'email' => session('user_email', '')
    ];
@endphp
<div class="dlabnav">
<div class="dlabnav-scroll">
    <ul class="metismenu" id="menu">
        <li><a href="{{ route('dashboard') }}" >
                <i class="material-symbols-outlined">home</i>
                <span class="nav-text">Dashboard</span>
            </a>
        </li>
        @if(App\Models\User::permission($auth_user->user_type,'Users Manage',$auth_user->id))
        <li><a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
            <i class="material-icons"> extension </i>
            <span class="nav-text">Users Manage</span>
            </a>
            <ul aria-expanded="false">
                <li><a href="{{ route('users') }}">User Manage</a></li>
                <li><a href="{{ route('set_permission') }}">Set Permission</a></li>
            </ul>
        </li>
        @endif
        {{-- @if(App\Models\User::permission($auth_user->user_type,'Vendor Manage',$auth_user->id))
        <li><a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
            <i class="material-icons"> extension </i>
            <span class="nav-text">Vendor Manage</span>
            </a>
            <ul aria-expanded="false">
                <li><a href="{{ route('vendors') }}">Vendor Manage</a></li>
                <li><a href="{{ route('vendors') }}">Product Manage</a></li>
                <li><a href="{{ route('vendors') }}">Orders & Tracking</a></li>
                <li><a href="{{ route('vendors') }}">Reports</a></li>
            </ul>
        </li>
        @endif --}}
         @if(App\Models\User::permission($auth_user->user_type,'Vendor Manage',$auth_user->id))
        <li><a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
            <i class="material-icons">person</i>
            <span class="nav-text">Vendor Manage</span>
            </a>
            <ul aria-expanded="false">
                <li><a href="{{ route('agents') }}">Vendor Manage</a></li>
                <li><a href="{{ route('b2bUsers') }}">B2B Manage</a></li>
                <li><a href="{{ route('b2cUsers') }}">B2C Manage</a></li>
                {{-- <li><a href="{{ route('agents_leads') }}">Leads Manage</a></li> --}}
                {{-- <li><a href="{{ route('commission_track') }}">Commission Tracking</a></li> --}}
                {{-- <li><a href="{{ route('agent_report') }}">Agent Report</a></li> --}}
            </ul>
        </li>
        @endif
        {{-- @if(App\Models\User::permission($auth_user->user_type,'Schools',$auth_user->id))
        <li><a href="{{ route('subschool') }}"><i class="material-symbols-outlined">school</i>
            <span class="nav-text">Schools</span></a>
        </li>
        @endif --}}
        @if(App\Models\User::permission($auth_user->user_type,'Custormers',$auth_user->id))
        <li><a href="{{ route('customers') }}" >
                <i class="material-symbols-outlined">person</i>
                <span class="nav-text">Customers</span>
            </a>
        </li>
        @endif
        @if(App\Models\User::permission($auth_user->user_type,'Orders',$auth_user->id))
        <li><a href="{{ route('orders') }}" >
            <i class="material-symbols-outlined">receipt_long</i>
            <span class="nav-text">Orders</span>
            </a>
        </li>
        @endif
        <li><a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
                <i class="material-symbols-outlined">account_balance_wallet</i>
                <span class="nav-text">Accounts</span>
            </a>
            <ul aria-expanded="false">
                <li><a href="{{route('subscriptionPackages')}}">Manage Packages</a></li>
                <li><a href="{{route('subcribersList.index')}}">Subscribers List</a></li>
            </ul>
        </li>
        @if(App\Models\User::permission($auth_user->user_type,'Custom Notification',$auth_user->id))
        <li><a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
            <i class="material-symbols-outlined">notifications</i>
            <span class="nav-text">Notification</span>
            </a>
            <ul aria-expanded="false">
                <li><a href="{{ route('vendorNotification') }}">Vendor Notification</a></li>
                <li><a href="{{ route('custNotification') }}">Customer Notification</a></li>
            </ul>
        </li>
        @endif
        @if(App\Models\User::permission($auth_user->user_type,'Reports',$auth_user->id))
        <li><a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
            <i class="material-icons">article</i>
            <span class="nav-text">Reports</span>
            </a>
            <ul aria-expanded="false">
                <li><a href="{{ route('signUpReport') }}">Sign Up Reports</a></li>
            </ul>
        </li>
        @endif
        @if(App\Models\User::permission($auth_user->user_type,'Site Manage',$auth_user->id))
        <li><a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
            <i class="material-icons"> extension </i>
            <span class="nav-text">Settings</span>
            </a>
            <ul aria-expanded="false">
                <li><a href="{{ route('manage_site') }}">Settings</a></li>
            </ul>
        </li>
        @endif
         {{-- @if(App\Models\User::permission($auth_user->user_type,'Store Manage',$auth_user->id))
        <li><a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
                <i class="material-symbols-outlined">store</i>
                <span class="nav-text">Store Manage</span>
            </a>
            <ul aria-expanded="false">
                <li><a href="{{ route('store_category') }}">Items Category</a></li>
                <li><a href="{{ route('manage_store') }}">Store Manage</a></li>
                <li><a href="{{ route('store_report') }}">Report</a></li>
            </ul>
        </li>
        @endif --}}
        {{--@if(App\Models\User::permission($auth_user->user_type,'Trainees Manage',$auth_user->id))
        <li><a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
            <i class="material-symbols-outlined">school</i>
            <span class="nav-text">Trainees Manage</span>
            </a>
            <ul aria-expanded="false">
                <li><a href="{{ route('student') }}">Trainees</a></li>
                <li><a href="{{ route('student_payment') }}">Trainees Payment</a></li>
                <li><a href="{{ route('student_activation') }}">Trainees Activation</a></li>
            </ul>
        </li>
        @endif
        @if(App\Models\User::permission($auth_user->user_type,'Course Manage',$auth_user->id))
        <li><a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
                <i class="material-icons"> widgets </i>
                <span class="nav-text">Course Manage</span>
            </a>
            <ul aria-expanded="false">
                <li><a href="{{ route('courses_category') }}">Course Category</a></li>
                <li><a href="{{ route('courses') }}">Course Manage</a></li>
                <li><a href="{{ route('course_report') }}">Report</a></li>
            </ul>
        </li>
        @endif
        @if(App\Models\User::permission($auth_user->user_type,'Course Contents',$auth_user->id))
        <li><a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
                <i class="material-icons"> insert_drive_file </i>
                <span class="nav-text">Course Contents</span>
            </a>
            <ul aria-expanded="false">
                <li><a href="{{ route('sub_topic_list') }}">Subject & Topic</a></li>
                <li><a href="{{ route('videos') }}">video Upload</a></li>
                <li><a href="{{ route('notes') }}">Notes Upload</a></li>
                <li><a href="{{ route('audios') }}">Audio Upload</a></li>
                <li><a href="{{ route('assignment') }}">Assignemt Upload</a></li>
            </ul>
        </li>
        @endif
        @if(App\Models\User::permission($auth_user->user_type,'Online Exam',$auth_user->id))
        <li><a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
                <i class="material-icons"> table_chart </i>
                <span class="nav-text">Online Exam</span>
            </a>
            <ul aria-expanded="false">
                <li><a href="{{ route('exams') }}">Exam Management</a></li>
                <li><a href="{{ route('questions') }}">Questions Upload</a></li>
                <li><a href="{{ route('assesment') }}">Assesment</a></li>
            </ul>
        </li>
        @endif --}}
        {{-- @if(App\Models\User::permission($auth_user->user_type,'Site Manage',$auth_user->id))
        <li><a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
            <i class="material-icons"> extension </i>
            <span class="nav-text">Site Manage</span>
            </a>
            <ul aria-expanded="false">
                <li><a href="{{ route('manage_site') }}">Site Contents</a></li>
            </ul>
        </li>
        @endif --}}
        {{-- @if(App\Models\User::permission($auth_user->user_type,'Reports',$auth_user->id))
        <li><a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
                <i class="material-icons">article</i>
                <span class="nav-text">Reports</span>
            </a>
            <ul aria-expanded="false">
                <li><a href="{{ route('report') }}">Reports</a>
                <li><a href="{{ route('report') }}">Reports</a>
                <li><a href="{{ route('report') }}">Reports</a>
                <li><a href="{{ route('report') }}">Reports</a>
            </ul>
        </li>
        @endif --}}
    </ul>
    {{-- <div class="copyright">
        <p><strong>School Admission Dashboard</strong></p>
        <p class="fs-12">Made with <span class="heart"></span> by DexignLab</p>
    </div> --}}
</div>
</div>
