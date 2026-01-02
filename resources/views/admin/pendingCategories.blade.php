@extends('index')
@section('content')

<div class="content-body">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <!-- Header Section -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h4 class="card-title mb-1">Pending Subcategory Requests</h4>
                                <p class="text-muted mb-0">Review and approve/reject subcategory requests from B2C users</p>
                            </div>
                            <a href="{{ route('categories') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to Categories
                            </a>
                        </div>
                        <hr>
                        
                        @include('layouts.flashmessage')
                        
                        @if(isset($error))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error!</strong> {{ $error }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <!-- Pending Subcategory Requests Table -->
                        @if(isset($pendingRequests) && is_array($pendingRequests) && count($pendingRequests) > 0)
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <i class="fa fa-clock"></i> Pending Subcategory Requests ({{ count($pendingRequests) }})
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Subcategory Name</th>
                                                <th>Category</th>
                                                <th>Requested By</th>
                                                <th>Default Price</th>
                                                <th>Price Unit</th>
                                                <th>Request Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($pendingRequests as $request)
                                            @php
                                                $requestId = $request['id'] ?? null;
                                                $subcategoryName = $request['subcategory_name'] ?? 'N/A';
                                                $subcategoryImg = $request['subcategory_img'] ?? '';
                                                $mainCategory = $request['main_category'] ?? null;
                                                $requester = $request['requester'] ?? null;
                                                $defaultPrice = $request['default_price'] ?? '0';
                                                $priceUnit = $request['price_unit'] ?? 'kg';
                                                $createdAt = $request['created_at'] ?? null;
                                            @endphp
                                            @if($requestId)
                                            <tr>
                                                <td>
                                                    <strong>{{ $subcategoryName }}</strong>
                                                    @if(!empty($subcategoryImg))
                                                        <br>
                                                        <img src="{{ $subcategoryImg }}" alt="Subcategory Image" 
                                                             class="img-thumbnail mt-2" style="max-width: 80px; cursor: pointer;" 
                                                             onclick="previewImage({{ json_encode($subcategoryImg) }})">
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($mainCategory && isset($mainCategory['name']))
                                                        <div class="d-flex align-items-center">
                                                            @if(!empty($mainCategory['image']))
                                                                <img src="{{ $mainCategory['image'] }}" 
                                                                     alt="{{ $mainCategory['name'] }}" 
                                                                     class="img-thumbnail me-2" 
                                                                     style="max-width: 40px; cursor: pointer;"
                                                                     onclick="previewImage({{ json_encode($mainCategory['image']) }})">
                                                            @endif
                                                            <span>{{ $mainCategory['name'] }}</span>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($requester)
                                                        <div>
                                                            <strong>{{ $requester['name'] ?? 'N/A' }}</strong><br>
                                                            <small class="text-muted">
                                                                @if(!empty($requester['contact']))
                                                                    {{ $requester['contact'] }}
                                                                @endif
                                                                @if(!empty($requester['email']))
                                                                    @if(!empty($requester['contact']))
                                                                        <br>
                                                                    @endif
                                                                    {{ $requester['email'] }}
                                                                @endif
                                                            </small>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>
                                                <td>₹{{ $defaultPrice }}</td>
                                                <td>
                                                    <span class="badge bg-secondary">{{ strtoupper($priceUnit) }}</span>
                                                </td>
                                                <td>
                                                    <small>
                                                        @if($createdAt)
                                                            {{ \Carbon\Carbon::parse($createdAt)->format('M d, Y') }}
                                                            <br>
                                                            <span class="text-muted">{{ \Carbon\Carbon::parse($createdAt)->format('h:i A') }}</span>
                                                        @else
                                                            N/A
                                                        @endif
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <form action="{{ route('approveRejectSubcategory', $requestId) }}" method="POST" style="display: inline;" onsubmit="return confirmApproval(event, 'approve');">
                                                            @csrf
                                                            <input type="hidden" name="action" value="approve">
                                                            <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                                                <i class="fa fa-check"></i> Approve
                                                            </button>
                                                        </form>
                                                        <button type="button" class="btn btn-sm btn-danger" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#rejectModal{{ $requestId }}"
                                                                title="Reject">
                                                            <i class="fa fa-times"></i> Reject
                                                        </button>
                                                    </div>
                                                    
                                                    <!-- Reject Modal -->
                                                    <div class="modal fade" id="rejectModal{{ $requestId }}" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Reject Subcategory Request</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <form action="{{ route('approveRejectSubcategory', $requestId) }}" method="POST">
                                                                    @csrf
                                                                    <input type="hidden" name="action" value="reject">
                                                                    <div class="modal-body">
                                                                        <p>Are you sure you want to reject the subcategory request for <strong>{{ $subcategoryName }}</strong>?</p>
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Rejection Reason (Optional)</label>
                                                                            <textarea class="form-control" name="approval_notes" rows="3" 
                                                                                      placeholder="Enter reason for rejection..."></textarea>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" class="btn btn-danger">Reject</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @else
                            <div class="alert alert-info">
                                <strong>No Pending Requests!</strong><br>
                                There are currently no pending subcategory requests to review.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-header border-0">
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
            </div>
            <div class="modal-body text-center p-0">
                <img id="previewImage" src="" class="img-fluid rounded" style="max-height: 85vh;">
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Image Preview Logic
    const imagePreviewModal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
    const previewImage = document.getElementById('previewImage');

    // Preview image function (for pending requests table)
    window.previewImage = function(imageUrl) {
        if (imageUrl && !imageUrl.includes('placeholder')) {
            previewImage.src = imageUrl;
            imagePreviewModal.show();
        }
    };

    document.body.addEventListener('click', function(e) {
        if (e.target.tagName === 'IMG' && e.target.classList.contains('img-thumbnail')) {
            if (e.target.src && !e.target.src.includes('placeholder')) {
                previewImage.src = e.target.src;
                imagePreviewModal.show();
            }
        }
    });

    // Approval confirmation function
    window.confirmApproval = function(event, action) {
        event.preventDefault();
        const form = event.target;
        const subcategoryName = form.closest('tr').querySelector('td:first-child strong').textContent;
        
        if (confirm(`Are you sure you want to approve the subcategory request for "${subcategoryName}"?`)) {
            form.submit();
        }
        return false;
    };
});
</script>

@endsection


