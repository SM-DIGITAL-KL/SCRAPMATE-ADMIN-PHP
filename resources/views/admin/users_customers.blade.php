@extends('index')
@section('content')

<div class="content-body ">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        @include('layouts.flashmessage')
                        <div class="text-end">
                            <span class="text-muted small">Customers (V1 & V2 app only)</span>
                        </div>
                        <hr>
                        <div class="card-body p-0" >
                            <div class="table-responsive">
                                <table id="example4" class="display table" >
                                    <thead>
                                        <tr>
                                            <th>Sl.No</th>
                                            <th>Name</th>
                                            <th>E-mail</th>
                                            <th>Phone</th>
                                            <th>App</th>
                                            <th>Date Joined</th>
                                            <th>Address</th>
                                            <th>Is Contacted</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('contentjs')
<script>
$(document).ready( function () {
$('#example4').DataTable({
       processing: true,
       serverSide: true,
       destroy: true,
       ordering: false,
       order: [],
       ajax: "{{ route('view_users_customers') }}",
       columns: [
            {
                data: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            { data: 'name', name: 'name' },
            { data: 'email', name: 'email' },
            { data: 'phone', name: 'phone' },
            { data: 'app_type', name: 'app_type', orderable: false, searchable: false },
            { data: 'date_joined', name: 'date_joined' },
            { data: 'address', name: 'address', orderable: false },
            { data: 'is_contacted', name: 'is_contacted', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
    });
});
</script>
@endsection
