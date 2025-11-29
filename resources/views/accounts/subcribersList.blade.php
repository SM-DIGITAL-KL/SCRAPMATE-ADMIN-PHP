@extends('index')
@section('content')

<div class="content-body ">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="card-body p-0" >
                        <div class="table-responsive">
                            <table id="example4" class="display table" >
                                <thead>
                                    <tr>
                                        <th>Sl.No</th>
                                        <th>User Name</th>
                                        <th>Package Name</th>
                                        <th>Period</th>
                                        <th>Amount</th>
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
       ajax: "{{route('view_subcribersList')}}",
       columns: [
            {
                data: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            {data:'user_name', name:'user_name'},
            {data:'name', name:'name'},
            {data:'period', name:'period'},
            {data:'price', name:'price'}
            // {data:'phone', name:'phone'},
            // {data: 'action',name: 'action',orderable: false,searchable: false},
        ]
    });
 });
</script>
@endsection
