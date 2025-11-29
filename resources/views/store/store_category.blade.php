@extends('index')
@section('content')

<div class="content-body ">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="text-end">
                            <a href="javascript:;"  onclick="basic_modal('','manage_store_cat','Manage Category')" data-bs-toggle="modal" data-bs-target="#basicModal" class="btn btn-primary" > + Add Store Category</a>
                        </div>
                        <hr>
                        <div class="card-body p-0" >
                            <div class="table-responsive">
                                <table id="example4" class="display table" >
                                    <thead>
                                        <tr>
                                            <th>Sl.No</th>
                                            <th>Category Name</th>
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
       ajax: "{{route('view_store_category')}}",
       columns: [
            {
                data: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            {data:'name', name:'name'},
            {data: 'action',name: 'action',orderable: false,searchable: false},
        ]
    });
 });
</script>
@endsection
