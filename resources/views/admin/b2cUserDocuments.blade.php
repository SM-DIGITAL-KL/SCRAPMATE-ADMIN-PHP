@extends('index')
@section('content')

<div class="content-body">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        @include('layouts.flashmessage')
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title" style="color: #000; font-weight: 600; font-size: 1.5rem;">B2C User Details</h4>
                            <a href="{{ route('b2cUsers') }}" class="btn btn-secondary btn-sm" style="font-weight: 500;">
                                <i class="fa fa-arrow-left"></i> Back to B2C Users
                            </a>
                        </div>
                        <hr>

                        @if(isset($user))
                            <!-- User Information -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="card" style="border: 1px solid #e0e0e0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                        <div class="card-body" style="padding: 25px;">
                                            <h5 class="card-title mb-4" style="color: #333; font-weight: 600; font-size: 1.25rem; border-bottom: 2px solid #6c5ce7; padding-bottom: 10px;">User Information</h5>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                        <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">Name:</strong> 
                                                        <span style="color: #000; font-weight: 400;">{{ $user->name ?? 'N/A' }}</span>
                                                    </p>
                                                    <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                        <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">Email:</strong> 
                                                        <span style="color: #000; font-weight: 400;">{{ $user->email ?? 'N/A' }}</span>
                                                    </p>
                                                    <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                        <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">Phone:</strong> 
                                                        <span style="color: #000; font-weight: 400;">{{ $user->phone ?? 'N/A' }}</span>
                                                    </p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                        <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">User Type:</strong> 
                                                        <span style="color: #000; font-weight: 400;">{{ $user->user_type ?? 'N/A' }}</span>
                                                    </p>
                                                    <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                        <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">App Type:</strong> 
                                                        <span style="color: #000; font-weight: 400;">{{ $user->app_type ?? 'N/A' }}</span>
                                                    </p>
                                                    <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                        <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">Sign Up Date:</strong> 
                                                        <span style="color: #000; font-weight: 400;">{{ isset($user->created_at) ? \Carbon\Carbon::parse($user->created_at)->format('Y-m-d H:i:s') : 'N/A' }}</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if(isset($user->shop) && $user->shop)
                                <!-- Address Information -->
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <div class="card" style="border: 1px solid #e0e0e0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                            <div class="card-body" style="padding: 25px;">
                                                <h5 class="card-title mb-4" style="color: #333; font-weight: 600; font-size: 1.25rem; border-bottom: 2px solid #6c5ce7; padding-bottom: 10px;">Address Information</h5>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                            <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">Address:</strong> 
                                                            <span style="color: #000; font-weight: 400;">{{ $user->shop->address ?? 'N/A' }}</span>
                                                        </p>
                                                        <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                            <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">Contact Number:</strong> 
                                                            <span style="color: #000; font-weight: 400;">{{ $user->shop->contact ?? 'N/A' }}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Approval Status -->
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <div class="card" style="border: 1px solid #e0e0e0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                            <div class="card-body" style="padding: 25px;">
                                                <h5 class="card-title mb-4" style="color: #333; font-weight: 600; font-size: 1.25rem; border-bottom: 2px solid #6c5ce7; padding-bottom: 10px;">Approval Status</h5>
                                                @php
                                                    $approvalStatus = $user->shop->approval_status ?? null;
                                                    $rejectionReason = $user->shop->rejection_reason ?? null;
                                                @endphp
                                                <div class="mb-4" style="color: #000; font-size: 15px;">
                                                    <strong style="color: #000; font-weight: 600; margin-right: 10px;">Current Status:</strong>
                                                    @if($approvalStatus === 'approved')
                                                        <span class="badge bg-success" style="font-size: 14px; padding: 8px 16px;">Approved</span>
                                                    @elseif($approvalStatus === 'pending')
                                                        <span class="badge bg-warning" style="font-size: 14px; padding: 8px 16px;">Pending</span>
                                                    @elseif($approvalStatus === 'rejected')
                                                        <span class="badge bg-danger" style="font-size: 14px; padding: 8px 16px;">Rejected</span>
                                                    @else
                                                        <span class="badge bg-secondary" style="font-size: 14px; padding: 8px 16px;">N/A</span>
                                                    @endif
                                                </div>

                                                @if($approvalStatus === 'rejected' && $rejectionReason)
                                                    <div class="mb-4 alert alert-danger" style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;">
                                                        <strong style="font-weight: 600;">Rejection Reason:</strong>
                                                        <p class="mb-0 mt-2" style="color: #721c24;">{{ $rejectionReason }}</p>
                                                    </div>
                                                @endif

                                                <!-- Approval Actions -->
                                                <form id="approvalForm" action="{{ route('updateB2CApprovalStatus', ['userId' => $user->id]) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="approval_status" id="approval_status_input" value="">
                                                    <input type="hidden" name="rejection_reason" id="rejection_reason_input" value="">
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-success" 
                                                                onclick="submitApproval('approved')">
                                                            <i class="fa fa-check"></i> Approve
                                                        </button>
                                                        <button type="button" class="btn btn-danger"
                                                                onclick="showRejectionModal()">
                                                            <i class="fa fa-times"></i> Reject
                                                        </button>
                                                        <button type="button" class="btn btn-warning"
                                                                onclick="submitApproval('pending')">
                                                            <i class="fa fa-clock"></i> Set Pending
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Documents -->
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="card" style="border: 1px solid #e0e0e0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                            <div class="card-body" style="padding: 25px;">
                                                <h5 class="card-title mb-4" style="color: #333; font-weight: 600; font-size: 1.25rem; border-bottom: 2px solid #6c5ce7; padding-bottom: 10px;">Uploaded Documents</h5>
                                                
                                                <div class="row">
                                                    <!-- Aadhar Card -->
                                                    <div class="col-md-6 mb-3">
                                                        <div class="card" style="border: 1px solid #e0e0e0;">
                                                            <div class="card-body" style="padding: 20px;">
                                                                <h6 class="card-subtitle mb-3" style="color: #000; font-weight: 600; font-size: 16px;">Aadhar Card</h6>
                                                                @if(!empty($user->shop->aadhar_card))
                                                                    <div class="mb-2">
                                                                        <a href="{{ $user->shop->aadhar_card }}" target="_blank" class="btn btn-primary btn-sm me-2">
                                                                            <i class="fa fa-file-pdf"></i> Open in New Tab
                                                                        </a>
                                                                        <a href="https://docs.google.com/viewer?url={{ urlencode($user->shop->aadhar_card) }}&embedded=true" target="_blank" class="btn btn-info btn-sm">
                                                                            <i class="fa fa-eye"></i> View with Google Viewer
                                                                        </a>
                                                                    </div>
                                                                    <iframe src="https://docs.google.com/viewer?url={{ urlencode($user->shop->aadhar_card) }}&embedded=true" 
                                                                            style="width: 100%; height: 500px; margin-top: 10px; border: 1px solid #ddd;" 
                                                                            frameborder="0"></iframe>
                                                                @else
                                                                    <p style="color: #666; font-size: 14px;">No document uploaded</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Driving License -->
                                                    <div class="col-md-6 mb-3">
                                                        <div class="card" style="border: 1px solid #e0e0e0;">
                                                            <div class="card-body" style="padding: 20px;">
                                                                <h6 class="card-subtitle mb-3" style="color: #000; font-weight: 600; font-size: 16px;">Driving License</h6>
                                                                @if(!empty($user->shop->driving_license))
                                                                    <div class="mb-2">
                                                                        <a href="{{ $user->shop->driving_license }}" target="_blank" class="btn btn-primary btn-sm me-2">
                                                                            <i class="fa fa-file-pdf"></i> Open in New Tab
                                                                        </a>
                                                                        <a href="https://docs.google.com/viewer?url={{ urlencode($user->shop->driving_license) }}&embedded=true" target="_blank" class="btn btn-info btn-sm">
                                                                            <i class="fa fa-eye"></i> View with Google Viewer
                                                                        </a>
                                                                    </div>
                                                                    <iframe src="https://docs.google.com/viewer?url={{ urlencode($user->shop->driving_license) }}&embedded=true" 
                                                                            style="width: 100%; height: 500px; margin-top: 10px; border: 1px solid #ddd;" 
                                                                            frameborder="0"></iframe>
                                                                @else
                                                                    <p style="color: #666; font-size: 14px;">No document uploaded</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    <i class="fa fa-exclamation-triangle"></i> No shop information found for this user.
                                </div>
                            @endif
                        @else
                            <div class="alert alert-danger">
                                <i class="fa fa-exclamation-circle"></i> User data not found.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Reason Modal -->
<div class="modal fade" id="rejectionModal" tabindex="-1" aria-labelledby="rejectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectionModalLabel">Select Rejection Reason</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Please select a reason for rejection:</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="rejection_reason" id="reason1" value="Aadhar card not proper">
                        <label class="form-check-label" for="reason1">
                            Aadhar card not proper
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="rejection_reason" id="reason2" value="Contact address not proper">
                        <label class="form-check-label" for="reason2">
                            Contact address not proper
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="rejection_reason" id="reason3" value="Email not proper">
                        <label class="form-check-label" for="reason3">
                            Email not proper
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="rejection_reason" id="reason4" value="Driving license not proper">
                        <label class="form-check-label" for="reason4">
                            Driving license not proper
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="rejection_reason" id="reason5" value="Documents are unclear or incomplete">
                        <label class="form-check-label" for="reason5">
                            Documents are unclear or incomplete
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="rejection_reason" id="reason6" value="Information mismatch in documents">
                        <label class="form-check-label" for="reason6">
                            Information mismatch in documents
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="rejection_reason" id="reason7" value="Other">
                        <label class="form-check-label" for="reason7">
                            Other
                        </label>
                    </div>
                </div>
                <div class="mb-3" id="otherReasonDiv" style="display: none;">
                    <label for="otherReason" class="form-label">Please specify:</label>
                    <textarea class="form-control" id="otherReason" rows="3" placeholder="Enter rejection reason"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmRejection()">Confirm Rejection</button>
            </div>
        </div>
    </div>
</div>

<script>
function submitApproval(status) {
    if (status === 'approved' && !confirm('Are you sure you want to approve this B2C user?')) {
        return;
    }
    if (status === 'pending' && !confirm('Are you sure you want to set status to pending?')) {
        return;
    }
    
    document.getElementById('approval_status_input').value = status;
    document.getElementById('rejection_reason_input').value = '';
    document.getElementById('approvalForm').submit();
}

function showRejectionModal() {
    // Reset form
    document.querySelectorAll('input[name="rejection_reason"]').forEach(radio => {
        radio.checked = false;
    });
    document.getElementById('otherReason').value = '';
    document.getElementById('otherReasonDiv').style.display = 'none';
    
    // Show modal
    var modal = new bootstrap.Modal(document.getElementById('rejectionModal'));
    modal.show();
    
    // Show other reason textarea if "Other" is selected
    document.getElementById('reason7').addEventListener('change', function() {
        if (this.checked) {
            document.getElementById('otherReasonDiv').style.display = 'block';
        } else {
            document.getElementById('otherReasonDiv').style.display = 'none';
        }
    });
}

function confirmRejection() {
    var selectedReason = document.querySelector('input[name="rejection_reason"]:checked');
    
    if (!selectedReason) {
        alert('Please select a rejection reason');
        return;
    }
    
    var rejectionReason = selectedReason.value;
    
    if (rejectionReason === 'Other') {
        var otherReason = document.getElementById('otherReason').value.trim();
        if (!otherReason) {
            alert('Please specify the rejection reason');
            return;
        }
        rejectionReason = otherReason;
    }
    
    if (!confirm('Are you sure you want to reject this B2C user with reason: ' + rejectionReason + '?')) {
        return;
    }
    
    document.getElementById('approval_status_input').value = 'rejected';
    document.getElementById('rejection_reason_input').value = rejectionReason;
    document.getElementById('approvalForm').submit();
}
</script>

@endsection

