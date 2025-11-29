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
                            @foreach ($shoptype as $s)
                                @php
                                    // Get shop count from API response if available, otherwise show 0
                                    // The count should be provided by the controller from the Node.js API
                                    $shops = 0;
                                    if (isset($shop_counts) && isset($shop_counts[$s->id])) {
                                        $shops = $shop_counts[$s->id];
                                    }
                                @endphp
                                <a href="javascript:;" class="filterbutton btn btn-outline-success btn-xs" data-shop-type-id="{{ $s->id }}">{{ $s->name }} ({{ $shops }})</a> 
                            @endforeach
                            {{-- <a href="javascript:;"  onclick="large_modal('','manage_agent','Manage Agent')" data-bs-toggle="modal" data-bs-target=".bd-example-modal-lg" class="btn btn-primary" >+ Add Shop</a> --}}
                        </div>
                        <hr>
                        <input type="hidden" id="shop_type_id">

                        <div class="card-body p-0" >
                            <div class="table-responsive">
                                <table id="example4" class="display table" width="100%">
                                    <thead>
                                        <tr>
                                            <th>SL NO</th>
                                            <th>VENDOR NAME</th>
                                            <th>DETAILS</th>
                                            <th>SIGN UP DATE</th>
                                            <th>SATUS</th>
                                            <th>SHOP TYPE</th>
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
    
$(document).ready( function () {
    $('#example4').DataTable({
        processing: true,
        serverSide: true,
        destroy: true,
        ajax: {
            url: "{{route('view_shops')}}",
            data: function(d) {
                d.shop_type_id = $('#shop_type_id').val();
            }
        },
        columns: [
            {data: 'DT_RowIndex',orderable: false,searchable: false},
            {data:'shopname', name:'shopname'},
            {data:'details', name:'details'},
            {data:'created_at', name:'created_at'},
            {data:'status', name:'status'},
            {data:'shop_type', name:'shop_type'},
            {data:'contact', name:'contact', searchable: true, type: 'num'},
            {data: 'action',name: 'action',orderable: false,searchable: false},
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
        const shopTypeId = $(this).data('shop-type-id');
        $('#shop_type_id').val(shopTypeId);
        $('#example4').DataTable().ajax.reload();
    });

 });
</script>
@endsection
