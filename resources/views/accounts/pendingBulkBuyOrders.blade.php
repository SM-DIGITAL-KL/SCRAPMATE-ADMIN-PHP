@extends('index')
@section('content')

<div class="content-body">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Pending Bulk Buy Orders Management</h4>
                        <p class="text-muted mb-0">Manage and approve pending bulk buy orders with payment verification</p>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="pendingBulkBuyOrdersTable" class="display table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Sl.No</th>
                                        <th>User Name</th>
                                        <th>Order Details</th>
                                        <th>Payment Information</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th>Action</th>
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

<!-- Rejection Notes Modal -->
<div class="modal fade" id="rejectionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Order</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="rejectionForm">
                    <input type="hidden" id="rejectOrderId" name="order_id">
                    <div class="form-group">
                        <label for="rejectionNotes">Rejection Notes (Optional)</label>
                        <textarea class="form-control" id="rejectionNotes" name="notes" rows="3" placeholder="Enter reason for rejection..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmReject()">Reject Order</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('contentjs')
<script>
$(document).ready(function() {
    $('#pendingBulkBuyOrdersTable').DataTable({
        processing: true,
        serverSide: true,
        destroy: true,
        ajax: "{{ route('view_pendingBulkBuyOrders') }}",
        columns: [
            {
                data: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            {
                data: 'user_name',
                name: 'user_name'
            },
            {
                data: 'order_details',
                name: 'order_details',
                orderable: false
            },
            {
                data: 'payment_info',
                name: 'payment_info',
                orderable: false
            },
            {
                data: 'status',
                name: 'status'
            },
            {
                data: 'created_at',
                name: 'created_at'
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>'
        }
    });
});

// Approve order
function approveOrder(orderId) {
    if (!confirm('Are you sure you want to approve this order?')) {
        return;
    }
    
    $.ajax({
        url: "{{ route('pendingBulkBuyOrderApproval') }}",
        method: 'POST',
        data: {
            order_id: orderId,
            action: 'approve',
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message || 'Order approved successfully');
                setTimeout(function() {
                    $('#pendingBulkBuyOrdersTable').DataTable().ajax.reload(null, false);
                }, 500);
            } else {
                toastr.error(response.message || 'Failed to approve order');
            }
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'An error occurred while approving order';
            toastr.error(errorMsg);
        }
    });
}

// Reject order
function rejectOrder(orderId) {
    $('#rejectOrderId').val(orderId);
    $('#rejectionNotes').val('');
    $('#rejectionModal').modal('show');
}

// Confirm reject
function confirmReject() {
    const orderId = $('#rejectOrderId').val();
    const notes = $('#rejectionNotes').val();
    
    $.ajax({
        url: "{{ route('pendingBulkBuyOrderApproval') }}",
        method: 'POST',
        data: {
            order_id: orderId,
            action: 'reject',
            notes: notes,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message || 'Order rejected successfully');
                $('#rejectionModal').modal('hide');
                setTimeout(function() {
                    $('#pendingBulkBuyOrdersTable').DataTable().ajax.reload(null, false);
                }, 500);
            } else {
                toastr.error(response.message || 'Failed to reject order');
            }
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'An error occurred while rejecting order';
            toastr.error(errorMsg);
        }
    });
}
</script>
@endsection


