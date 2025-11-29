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
                            <a href="javascript:;"  onclick="large_modal('','manage_users','Manage Users')" data-bs-toggle="modal" data-bs-target=".bd-example-modal-lg" class="btn btn-primary" >+ Add New User</a>
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
$('#example4').dataTable({
       processing: true,
       serverSide: true,
       destroy: true,
       ajax: "{{route('view_users')}}",
       columns: [
            {
                data: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            {data:'name', name:'name'},
            {data:'email', name:'email'},
            {data:'phone', name:'phone'},
            {data: 'action',name: 'action',orderable: false,searchable: false},
        ]
    });
 });
</script>
@endsection
