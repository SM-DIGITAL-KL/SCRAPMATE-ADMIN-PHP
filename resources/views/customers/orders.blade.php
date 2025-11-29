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
                            <a href="javascript:;" class="filterbutton btn btn-outline-warning btn-xs toggle-class" data-shop-type-id="1">Request Pending</a>
                            <a href="javascript:;" class="filterbutton btn btn-outline-warning btn-xs toggle-class" data-shop-type-id="2">Shop Accepted</a>
                            <a href="javascript:;" class="filterbutton btn btn-outline-warning btn-xs toggle-class" data-shop-type-id="3">Assigned Door Step Buyer</a>
                            <a href="javascript:;" class="filterbutton btn btn-outline-success btn-xs toggle-class" data-shop-type-id="4">Completed</a>
                            <a href="javascript:;" class="filterbutton btn btn-outline-danger btn-xs toggle-class" data-shop-type-id="5">Shop Declined</a>
                            <a href="javascript:;" class="filterbutton btn btn-outline-danger btn-xs toggle-class" data-shop-type-id="6">Customer Cancelled</a>
                            {{-- <a href="javascript:;"  onclick="large_modal('','manage_agent','Manage Agent')" data-bs-toggle="modal" data-bs-target=".bd-example-modal-lg" class="btn btn-primary" >+ Add Shop</a> --}}
                        </div>
                        <input type="hidden" id="status_id">
                        
                        <div class="card-body p-0" >
                            <div class="table-responsive">
                                <table id="example4" class="display table" >
                                    <thead>
                                        <tr>
                                            <th>SL NO</th>
                                            <th>ORDER NUMBER</th>
                                            <th>STATUS</th>
                                            <th>Shop||Customer</th>
                                            <th>CUSTOMER</th>
                                            <th>SHOP</th>
                                            <th>ORDER DATE</th>
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
            url: "{{ route('view_orders') }}",
            data: function(d) {
                d.status_id = $('#status_id').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'order_number', name: 'order_number' },
            { data: 'status', name: 'status' },
            { data: 'callStatus', name: 'callStatus' },
            { data: 'customerdetails', name: 'customerdetails' },
            { data: 'shopdetails', name: 'shopdetails' },
            { data: 'date', name: 'date' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        language: {
            paginate: {
                previous: '&lt;',
                next: '&gt;'
            }
        }
    });
    $('.filterbutton').on('click', function () {
        $(".filterbutton").removeClass("active text-light");
        $(this).addClass("active text-light");
        const status_id = $(this).data('shop-type-id');
        $('#status_id').val(status_id);
        $('#example4').DataTable().ajax.reload();
    });
});
</script>
@endsection