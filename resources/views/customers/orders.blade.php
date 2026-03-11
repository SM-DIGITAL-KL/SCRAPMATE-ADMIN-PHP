@extends('index')
@section('content')

@php
    $is_zone_email = preg_match('/^zone/i', (string) session('user_email', '')) === 1;
@endphp


<div class="content-body">
    <div class="container-fluid">
        <div class="row mt-4">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header border-0 d-flex justify-content-between align-items-center flex-wrap">
                        <h4 class="card-title mb-0">Recent Customer App Orders (v2)</h4>
                        <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                            @if(!$is_zone_email)
                            <label for="customerOrdersStateFilter" class="mb-0 me-2">State</label>
                            <select id="customerOrdersStateFilter" class="form-control form-control-sm me-2" style="min-width: 190px;">
                                <option value="">All States</option>
                                <option value="Andhra Pradesh">Andhra Pradesh</option>
                                <option value="Arunachal Pradesh">Arunachal Pradesh</option>
                                <option value="Assam">Assam</option>
                                <option value="Bihar">Bihar</option>
                                <option value="Chhattisgarh">Chhattisgarh</option>
                                <option value="Goa">Goa</option>
                                <option value="Gujarat">Gujarat</option>
                                <option value="Haryana">Haryana</option>
                                <option value="Himachal Pradesh">Himachal Pradesh</option>
                                <option value="Jharkhand">Jharkhand</option>
                                <option value="Karnataka">Karnataka</option>
                                <option value="Kerala">Kerala</option>
                                <option value="Madhya Pradesh">Madhya Pradesh</option>
                                <option value="Maharashtra">Maharashtra</option>
                                <option value="Manipur">Manipur</option>
                                <option value="Meghalaya">Meghalaya</option>
                                <option value="Mizoram">Mizoram</option>
                                <option value="Nagaland">Nagaland</option>
                                <option value="Odisha">Odisha</option>
                                <option value="Punjab">Punjab</option>
                                <option value="Rajasthan">Rajasthan</option>
                                <option value="Sikkim">Sikkim</option>
                                <option value="Tamil Nadu">Tamil Nadu</option>
                                <option value="Telangana">Telangana</option>
                                <option value="Tripura">Tripura</option>
                                <option value="Uttar Pradesh">Uttar Pradesh</option>
                                <option value="Uttarakhand">Uttarakhand</option>
                                <option value="West Bengal">West Bengal</option>
                                <option value="Andaman and Nicobar Islands">Andaman and Nicobar Islands</option>
                                <option value="Chandigarh">Chandigarh</option>
                                <option value="Dadra and Nagar Haveli and Daman and Diu">Dadra and Nagar Haveli and Daman and Diu</option>
                                <option value="Delhi">Delhi</option>
                                <option value="Jammu and Kashmir">Jammu and Kashmir</option>
                                <option value="Ladakh">Ladakh</option>
                                <option value="Lakshadweep">Lakshadweep</option>
                                <option value="Puducherry">Puducherry</option>
                            </select>
                            @endif
                            <a href="/api/dashboard/export-scheduled-orders" class="btn btn-success btn-sm">
                                <i class="fa fa-file-excel-o"></i> Download Scheduled Orders (Excel)
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="customerAppOrdersTable">
                                <thead>
                                    <tr>
                                        <th>Sl.No</th>
                                        <th>Order ID</th>
                                        <th>Order Number</th>
                                        <th>Customer ID</th>
                                        <th>Shop ID</th>
                                        <th>Status</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" role="dialog" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderDetailsModalLabel">Order Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('contentjs')
<script>
let customerAppOrdersTable = null;

function initializeCustomerAppOrdersTable() {
    if (customerAppOrdersTable) {
        customerAppOrdersTable.destroy();
    }

    customerAppOrdersTable = $('#customerAppOrdersTable').DataTable({
        processing: true,
        serverSide: true,
        destroy: true,
        ajax: {
            url: "{{ route('api.dashboard.customerAppOrders') }}",
            type: 'GET',
            data: function(d) {
                d.state = $('#customerOrdersStateFilter').val() || '';
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            error: function(xhr, error, thrown) {
                console.error('DataTables Ajax Error:', {
                    xhr: xhr,
                    error: error,
                    thrown: thrown,
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    responseJSON: xhr.responseJSON
                });

                let errorMsg = 'Error loading orders. ';
                if (xhr.status === 0) {
                    errorMsg += 'Network error - please check your connection.';
                } else if (xhr.status === 404) {
                    errorMsg += 'Endpoint not found.';
                } else if (xhr.status === 500) {
                    errorMsg += 'Server error.';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMsg += ' ' + xhr.responseJSON.error;
                    }
                } else {
                    errorMsg += 'Status: ' + xhr.status + ' ' + xhr.statusText;
                }

                alert(errorMsg);
            }
        },
        columns: [
            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            {
                data: 'id',
                name: 'id'
            },
            {
                data: 'order_number',
                name: 'order_number'
            },
            {
                data: 'customer_id',
                name: 'customer_id'
            },
            {
                data: 'shop_id',
                name: 'shop_id'
            },
            {
                data: 'status_badge',
                name: 'status',
                orderable: true,
                searchable: false
            },
            {
                data: 'amount',
                name: 'amount',
                orderable: true,
                searchable: false
            },
            {
                data: 'date',
                name: 'date',
                orderable: true
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            }
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>'
        }
    });

    const stateFilter = document.getElementById('customerOrdersStateFilter');
    if (stateFilter) {
        stateFilter.addEventListener('change', function() {
            customerAppOrdersTable.ajax.reload();
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initializeCustomerAppOrdersTable();
});

@include('customers.partials.v2-order-details-functions')
</script>
@endsection
