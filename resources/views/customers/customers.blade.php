@extends('index')
@section('content')

<div class="content-body ">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        @include('layouts.flashmessage')
                        <div class="text-center">
                            {{-- <a href="javascript:;"  onclick="large_modal('','manage_agent','Manage Agent')" data-bs-toggle="modal" data-bs-target=".bd-example-modal-lg" class="btn btn-primary" >+ Add Shop</a> --}}
                        </div>

                        <div class="card-body p-0" >
                            <div class="table-responsive">
                                <table id="example4" class="display table" >
                                    <thead>
                                        <tr>
                                            <th>SL NO</th>
                                            <th>CUSTOMER NAME</th>
                                            <th>DETAILS</th>
                                            <th>SIGN UP DATE</th>
                                            {{-- <th>E-MAIL</th> --}}
                                            <th>CONTACT NO</th>
                                            <th>ACTION</th> 
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
$(document).ready(function() {
    $('#example4').DataTable({
        processing: true,
        serverSide: true,
        destroy: true,
        ajax: {
            url: "{{ route('view_customers') }}",
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'details', name: 'details' },
            { data: 'created_at', name: 'created_at', render: function(data){
                return moment(data).format('DD-MM-YYYY');
            }},
            { data: 'contact', name: 'contact' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
    });
});
</script>
@endsection