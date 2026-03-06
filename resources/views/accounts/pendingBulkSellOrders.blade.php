@extends('index')
@section('content')

<div class="content-body">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Pending Bulk Sell Orders Management</h4>
                        <p class="text-muted mb-0">Manage and view pending bulk sell orders from B2B vendors</p>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="pendingBulkSellOrdersTable" class="display table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Sl.No</th>
                                        <th>Seller Name</th>
                                        <th>Order Details</th>
                                        <th>Interested Buyers</th>
                                        <th>Payment Info</th>
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

<!-- View Buyers Modal -->
<div class="modal fade" id="buyersModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Interested Buyers</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="buyersList">
                    <!-- Buyers will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- View Documents Modal -->
<div class="modal fade" id="documentsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Uploaded Documents</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="documentsList">
                    <!-- Documents will be loaded here -->
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
$(document).ready(function() {
    $('#pendingBulkSellOrdersTable').DataTable({
        processing: true,
        serverSide: true,
        destroy: true,
        ajax: "{{ route('view_pendingBulkSellOrders') }}",
        columns: [
            {
                data: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            {
                data: 'seller_name',
                name: 'seller_name'
            },
            {
                data: 'order_details',
                name: 'order_details',
                orderable: false
            },
            {
                data: 'buyers_info',
                name: 'buyers_info',
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

// View buyers
function viewBuyers(requestId, acceptedBuyers) {
    let buyersHtml = '';
    
    if (acceptedBuyers && acceptedBuyers.length > 0) {
        buyersHtml = '<table class="table table-bordered">';
        buyersHtml += '<thead><tr><th>Buyer ID</th><th>Committed Qty</th><th>Bidding Price</th><th>Accepted At</th><th>Status</th></tr></thead>';
        buyersHtml += '<tbody>';
        
        acceptedBuyers.forEach(function(buyer) {
            const committedQty = buyer.committed_quantity ? buyer.committed_quantity + ' kg' : 'N/A';
            const biddingPrice = buyer.bidding_price ? '₹' + buyer.bidding_price + '/kg' : 'N/A';
            const acceptedAt = buyer.accepted_at ? new Date(buyer.accepted_at).toLocaleString() : 'N/A';
            const status = buyer.status || 'accepted';
            
            buyersHtml += '<tr>';
            buyersHtml += '<td>' + buyer.user_id + '</td>';
            buyersHtml += '<td>' + committedQty + '</td>';
            buyersHtml += '<td>' + biddingPrice + '</td>';
            buyersHtml += '<td>' + acceptedAt + '</td>';
            buyersHtml += '<td><span class="badge badge-success">' + status + '</span></td>';
            buyersHtml += '</tr>';
        });
        
        buyersHtml += '</tbody></table>';
    } else {
        buyersHtml = '<div class="alert alert-info">No buyers have accepted this request yet.</div>';
    }
    
    $('#buyersList').html(buyersHtml);
    $('#buyersModal').modal('show');
}

// View documents
function viewDocuments(requestId, documents) {
    let docsHtml = '';
    
    if (documents && documents.length > 0) {
        docsHtml = '<div class="row">';
        
        documents.forEach(function(doc, index) {
            const isPdf = doc.toLowerCase().endsWith('.pdf');
            
            docsHtml += '<div class="col-md-4 mb-3">';
            docsHtml += '<div class="card">';
            
            if (isPdf) {
                docsHtml += '<div class="card-body text-center">';
                docsHtml += '<i class="fas fa-file-pdf fa-3x text-danger"></i>';
                docsHtml += '<p class="mt-2">Document ' + (index + 1) + '</p>';
                docsHtml += '</div>';
            } else {
                docsHtml += '<img src="' + doc + '" class="card-img-top" style="height: 150px; object-fit: cover;" alt="Document">';
            }
            
            docsHtml += '<div class="card-footer text-center">';
            docsHtml += '<a href="' + doc + '" target="_blank" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i> View</a> ';
            docsHtml += '<a href="' + doc + '" download class="btn btn-secondary btn-sm"><i class="fas fa-download"></i> Download</a>';
            docsHtml += '</div>';
            docsHtml += '</div>';
            docsHtml += '</div>';
        });
        
        docsHtml += '</div>';
    } else {
        docsHtml = '<div class="alert alert-info">No documents uploaded for this request.</div>';
    }
    
    $('#documentsList').html(docsHtml);
    $('#documentsModal').modal('show');
}

// Cancel/Delete request
function cancelRequest(requestId) {
    if (!confirm('Are you sure you want to cancel this bulk sell request?')) {
        return;
    }
    
    $.ajax({
        url: "{{ route('pendingBulkSellOrderCancel') }}",
        method: 'POST',
        data: {
            request_id: requestId,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message || 'Request cancelled successfully');
                setTimeout(function() {
                    $('#pendingBulkSellOrdersTable').DataTable().ajax.reload(null, false);
                }, 500);
            } else {
                toastr.error(response.message || 'Failed to cancel request');
            }
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'An error occurred while cancelling request';
            toastr.error(errorMsg);
        }
    });
}

// Mark as sold
function markAsSold(requestId) {
    if (!confirm('Are you sure you want to mark this request as sold? This will complete the order.')) {
        return;
    }
    
    $.ajax({
        url: "{{ route('pendingBulkSellOrderStatus') }}",
        method: 'POST',
        data: {
            request_id: requestId,
            status: 'sold',
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message || 'Request marked as sold successfully');
                setTimeout(function() {
                    $('#pendingBulkSellOrdersTable').DataTable().ajax.reload(null, false);
                }, 500);
            } else {
                toastr.error(response.message || 'Failed to update request status');
            }
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'An error occurred while updating request status';
            toastr.error(errorMsg);
        }
    });
}
</script>
@endsection
