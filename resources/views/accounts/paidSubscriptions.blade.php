@extends('index')
@section('content')

<div class="content-body">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Paid Subscriptions Management</h4>
                        <p class="text-muted mb-0">Manage and approve B2B/B2C user paid subscriptions</p>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="paidSubscriptionsTable" class="display table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Sl.No</th>
                                        <th>User Name / Type</th>
                                        <th>Package Name</th>
                                        <th>Payment Information</th>
                                        <th>Amount</th>
                                        <th>Period</th>
                                        <th>Status</th>
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
                <h5 class="modal-title">Reject Subscription</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="rejectionForm">
                    <input type="hidden" id="rejectSubscriptionId" name="subscription_id">
                    <div class="form-group">
                        <label for="rejectionNotes">Rejection Notes (Optional)</label>
                        <textarea class="form-control" id="rejectionNotes" name="notes" rows="3" placeholder="Enter reason for rejection..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmReject()">Reject Subscription</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('contentjs')
<script>
$(document).ready(function() {
    $('#paidSubscriptionsTable').DataTable({
        processing: true,
        serverSide: true,
        destroy: true,
        ajax: "{{ route('view_paidSubscriptions') }}",
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
                data: 'package_name',
                name: 'package_name'
            },
            {
                data: 'payment_info',
                name: 'payment_info',
                orderable: false
            },
            {
                data: 'price',
                name: 'price'
            },
            {
                data: 'period',
                name: 'period'
            },
            {
                data: 'status',
                name: 'status'
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

// Approve subscription
function approveSubscription(subscriptionId) {
    if (!confirm('Are you sure you want to approve this subscription?')) {
        return;
    }
    
    $.ajax({
        url: "{{ route('subscriptionApproval') }}",
        method: 'POST',
        data: {
            subscription_id: subscriptionId,
            action: 'approve',
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message || 'Subscription approved successfully');
                // Force reload with cache bypass to get fresh data
                // Use setTimeout to ensure server has processed the update
                setTimeout(function() {
                    $('#paidSubscriptionsTable').DataTable().ajax.url("{{ route('view_paidSubscriptions') }}?refresh=true&_=" + Date.now()).load(null, false);
                }, 500);
            } else {
                toastr.error(response.message || 'Failed to approve subscription');
            }
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'An error occurred while approving subscription';
            toastr.error(errorMsg);
        }
    });
}

// Reject subscription - show modal first
function rejectSubscription(subscriptionId) {
    $('#rejectSubscriptionId').val(subscriptionId);
    $('#rejectionNotes').val('');
    $('#rejectionModal').modal('show');
}

// Confirm rejection
function confirmReject() {
    const subscriptionId = $('#rejectSubscriptionId').val();
    const notes = $('#rejectionNotes').val();
    
    if (!confirm('Are you sure you want to reject this subscription?')) {
        return;
    }
    
    $.ajax({
        url: "{{ route('subscriptionApproval') }}",
        method: 'POST',
        data: {
            subscription_id: subscriptionId,
            action: 'reject',
            notes: notes,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                $('#rejectionModal').modal('hide');
                toastr.success(response.message || 'Subscription rejected successfully');
                // Force reload with cache bypass to get fresh data
                // Use setTimeout to ensure server has processed the update
                setTimeout(function() {
                    $('#paidSubscriptionsTable').DataTable().ajax.url("{{ route('view_paidSubscriptions') }}?refresh=true&_=" + Date.now()).load(null, false);
                }, 500);
            } else {
                toastr.error(response.message || 'Failed to reject subscription');
            }
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'An error occurred while rejecting subscription';
            toastr.error(errorMsg);
        }
    });
}
</script>
@endsection

