@extends('index')
@section('content')

<div class="content-body ">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        @include('layouts.flashmessage')
                        <h4 class="card-title">Orders List</h4>
                        <hr>
                        
                        <!-- Filter Buttons -->
                        <div class="text-center mb-3">
                            <a href="javascript:;" class="filterbutton btn btn-outline-warning btn-xs toggle-class" data-shop-type-id="1">Request Pending</a>
                            <a href="javascript:;" class="filterbutton btn btn-outline-warning btn-xs toggle-class" data-shop-type-id="2">Shop Accepted</a>
                            <a href="javascript:;" class="filterbutton btn btn-outline-warning btn-xs toggle-class" data-shop-type-id="3">Assigned Door Step Buyer</a>
                            <a href="javascript:;" class="filterbutton btn btn-outline-success btn-xs toggle-class" data-shop-type-id="4">Completed</a>
                            <a href="javascript:;" class="filterbutton btn btn-outline-danger btn-xs toggle-class" data-shop-type-id="5">Shop Declined</a>
                            <a href="javascript:;" class="filterbutton btn btn-outline-danger btn-xs toggle-class" data-shop-type-id="6">Customer Cancelled</a>
                        </div>
                        <input type="hidden" id="status_id">
                        
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <div class="d-flex justify-content-between align-items-center mb-3 px-3">
                                    <div class="d-flex align-items-center">
                                        <label class="me-2">Show</label>
                                        <select class="form-select form-select-sm" style="width: auto;" id="entriesPerPage">
                                            <option value="10">10</option>
                                            <option value="20">20</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select>
                                        <label class="ms-2">entries</label>
                                    </div>
                                    <div>
                                        <label class="me-2">Search:</label>
                                        <input type="text" 
                                               class="form-control form-control-sm d-inline-block" 
                                               style="width: 200px;" 
                                               id="searchInput" 
                                               placeholder="Search orders...">
                                    </div>
                                </div>
                                <table id="example4" class="table table-striped table-hover" style="margin-bottom: 0;">
                                    <thead style="position: sticky; top: 0; z-index: 10;">
                                        <tr style="background-color: #6c5ce7; color: white;">
                                            <th style="min-width: 60px;">SL NO</th>
                                            <th style="min-width: 150px;">ORDER NUMBER</th>
                                            <th style="min-width: 120px;">STATUS</th>
                                            <th style="min-width: 150px;">Shop||Customer</th>
                                            <th style="min-width: 180px;">CUSTOMER</th>
                                            <th style="min-width: 180px;">SHOP</th>
                                            <th style="min-width: 120px;">ORDER DATE</th>
                                            <th style="min-width: 100px;">APP TYPE</th>
                                            <th style="min-width: 80px;">ACTION</th> 
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- DataTable will populate this -->
                                    </tbody>
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
<style>
    #example4 td {
        vertical-align: top;
    }
    /* Match customers page font styling */
    #example4 {
        font-family: inherit;
    }
    #example4 th {
        font-weight: 600;
    }
    #example4 td {
        font-size: 14px;
    }
    #example4_wrapper .dataTables_filter {
        display: none; /* Hide default DataTable search */
    }
    #example4_wrapper .dataTables_length {
        display: none; /* Hide default DataTable length */
    }
    #example4_wrapper .dataTables_info {
        padding-left: 15px;
        padding-top: 10px;
    }
    #example4_wrapper .dataTables_paginate {
        padding-right: 15px;
        padding-top: 10px;
    }
</style>
<script>
$(document).ready(function() {
    let ordersTable = $('#example4').DataTable({
        processing: true,
        serverSide: true,
        destroy: true,
        pageLength: 10,
        lengthMenu: [[10, 20, 50, 100], [10, 20, 50, 100]],
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
            { data: 'app_type', name: 'app_type' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        language: {
            paginate: {
                previous: '&lt;',
                next: '&gt;'
            },
            processing: '<div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: 'No matching records found',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            infoEmpty: 'Showing 0 to 0 of 0 entries',
            infoFiltered: '(filtered from _MAX_ total entries)'
        },
        drawCallback: function(settings) {
            // Update entries per page dropdown to match current page length
            $('#entriesPerPage').val(settings._iDisplayLength);
        }
    });
    
    // Filter button click handler
    $('.filterbutton').on('click', function () {
        $(".filterbutton").removeClass("active text-light");
        $(this).addClass("active text-light");
        const status_id = $(this).data('shop-type-id');
        $('#status_id').val(status_id);
        ordersTable.ajax.reload();
    });
    
    // Entries per page change handler
    $('#entriesPerPage').on('change', function() {
        const pageLength = parseInt($(this).val());
        ordersTable.page.len(pageLength).draw();
    });
    
    // Search input handler with debounce
    let searchTimeout;
    $('#searchInput').on('keyup', function() {
        clearTimeout(searchTimeout);
        const searchValue = $(this).val();
        searchTimeout = setTimeout(function() {
            ordersTable.search(searchValue).draw();
        }, 500); // Wait 500ms after user stops typing
    });
});
</script>
@endsection
