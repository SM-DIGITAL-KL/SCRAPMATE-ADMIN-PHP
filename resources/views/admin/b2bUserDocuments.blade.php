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
                            <h4 class="card-title" style="color: #000; font-weight: 600; font-size: 1.5rem;">B2B User Documents</h4>
                            <a href="{{ route('b2bUsers') }}" class="btn btn-secondary btn-sm" style="font-weight: 500;">
                                <i class="fa fa-arrow-left"></i> Back to B2B Users
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
                                <!-- Company Information -->
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <div class="card" style="border: 1px solid #e0e0e0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                            <div class="card-body" style="padding: 25px;">
                                                <h5 class="card-title mb-4" style="color: #333; font-weight: 600; font-size: 1.25rem; border-bottom: 2px solid #6c5ce7; padding-bottom: 10px;">Company Information</h5>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                            <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">Company Name:</strong> 
                                                            <span style="color: #000; font-weight: 400;">{{ $user->shop->company_name ?? 'N/A' }}</span>
                                                        </p>
                                                        <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                            <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">GST Number:</strong> 
                                                            <span style="color: #000; font-weight: 400;">{{ $user->shop->gst_number ?? 'N/A' }}</span>
                                                        </p>
                                                        <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                            <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">PAN Number:</strong> 
                                                            <span style="color: #000; font-weight: 400;">{{ $user->shop->pan_number ?? 'N/A' }}</span>
                                                        </p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                            <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">Shop Name:</strong> 
                                                            <span style="color: #000; font-weight: 400;">{{ $user->shop->shopname ?? 'N/A' }}</span>
                                                        </p>
                                                        <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                            <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">Owner Name:</strong> 
                                                            <span style="color: #000; font-weight: 400;">{{ $user->shop->ownername ?? 'N/A' }}</span>
                                                        </p>
                                                        <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                            <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">Address:</strong> 
                                                            <span style="color: #000; font-weight: 400;">{{ $user->shop->address ?? 'N/A' }}</span>
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

                                                <!-- Approval Actions -->
                                                <form action="{{ route('updateB2BApprovalStatus', ['userId' => $user->id]) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <div class="btn-group" role="group">
                                                        <button type="submit" name="approval_status" value="approved" class="btn btn-success" 
                                                                onclick="return confirm('Are you sure you want to approve this B2B user?')">
                                                            <i class="fa fa-check"></i> Approve
                                                        </button>
                                                        <button type="submit" name="approval_status" value="rejected" class="btn btn-danger"
                                                                onclick="return confirm('Are you sure you want to reject this B2B user?')">
                                                            <i class="fa fa-times"></i> Reject
                                                        </button>
                                                        <button type="submit" name="approval_status" value="pending" class="btn btn-warning"
                                                                onclick="return confirm('Are you sure you want to set status to pending?')">
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
                                                    <!-- Business License -->
                                                    <div class="col-md-6 mb-3">
                                                        <div class="card" style="border: 1px solid #e0e0e0;">
                                                            <div class="card-body" style="padding: 20px;">
                                                                <h6 class="card-subtitle mb-3" style="color: #000; font-weight: 600; font-size: 16px;">Business License</h6>
                                                                @if(!empty($user->shop->business_license_url))
                                                                    <div class="mb-2">
                                                                        <a href="{{ $user->shop->business_license_url }}" target="_blank" class="btn btn-primary btn-sm me-2">
                                                                            <i class="fa fa-file-pdf"></i> Open in New Tab
                                                                        </a>
                                                                        <a href="https://docs.google.com/viewer?url={{ urlencode($user->shop->business_license_url) }}&embedded=true" target="_blank" class="btn btn-info btn-sm">
                                                                            <i class="fa fa-eye"></i> View with Google Viewer
                                                                        </a>
                                                                    </div>
                                                                    <iframe src="https://docs.google.com/viewer?url={{ urlencode($user->shop->business_license_url) }}&embedded=true" 
                                                                            style="width: 100%; height: 500px; margin-top: 10px; border: 1px solid #ddd;" 
                                                                            frameborder="0"></iframe>
                                                                @else
                                                                    <p style="color: #666; font-size: 14px;">No document uploaded</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- GST Certificate -->
                                                    <div class="col-md-6 mb-3">
                                                        <div class="card" style="border: 1px solid #e0e0e0;">
                                                            <div class="card-body" style="padding: 20px;">
                                                                <h6 class="card-subtitle mb-3" style="color: #000; font-weight: 600; font-size: 16px;">GST Certificate</h6>
                                                                @if(!empty($user->shop->gst_certificate_url))
                                                                    <div class="mb-2">
                                                                        <a href="{{ $user->shop->gst_certificate_url }}" target="_blank" class="btn btn-primary btn-sm me-2">
                                                                            <i class="fa fa-file-pdf"></i> Open in New Tab
                                                                        </a>
                                                                        <a href="https://docs.google.com/viewer?url={{ urlencode($user->shop->gst_certificate_url) }}&embedded=true" target="_blank" class="btn btn-info btn-sm">
                                                                            <i class="fa fa-eye"></i> View with Google Viewer
                                                                        </a>
                                                                    </div>
                                                                    <iframe src="https://docs.google.com/viewer?url={{ urlencode($user->shop->gst_certificate_url) }}&embedded=true" 
                                                                            style="width: 100%; height: 500px; margin-top: 10px; border: 1px solid #ddd;" 
                                                                            frameborder="0"></iframe>
                                                                @else
                                                                    <p style="color: #666; font-size: 14px;">No document uploaded</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Address Proof -->
                                                    <div class="col-md-6 mb-3">
                                                        <div class="card" style="border: 1px solid #e0e0e0;">
                                                            <div class="card-body" style="padding: 20px;">
                                                                <h6 class="card-subtitle mb-3" style="color: #000; font-weight: 600; font-size: 16px;">Business Address Proof</h6>
                                                                @if(!empty($user->shop->address_proof_url))
                                                                    <div class="mb-2">
                                                                        <a href="{{ $user->shop->address_proof_url }}" target="_blank" class="btn btn-primary btn-sm me-2">
                                                                            <i class="fa fa-file-pdf"></i> Open in New Tab
                                                                        </a>
                                                                        <a href="https://docs.google.com/viewer?url={{ urlencode($user->shop->address_proof_url) }}&embedded=true" target="_blank" class="btn btn-info btn-sm">
                                                                            <i class="fa fa-eye"></i> View with Google Viewer
                                                                        </a>
                                                                    </div>
                                                                    <iframe src="https://docs.google.com/viewer?url={{ urlencode($user->shop->address_proof_url) }}&embedded=true" 
                                                                            style="width: 100%; height: 500px; margin-top: 10px; border: 1px solid #ddd;" 
                                                                            frameborder="0"></iframe>
                                                                @else
                                                                    <p style="color: #666; font-size: 14px;">No document uploaded</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- KYC of Owner -->
                                                    <div class="col-md-6 mb-3">
                                                        <div class="card" style="border: 1px solid #e0e0e0;">
                                                            <div class="card-body" style="padding: 20px;">
                                                                <h6 class="card-subtitle mb-3" style="color: #000; font-weight: 600; font-size: 16px;">KYC of Owner</h6>
                                                                @if(!empty($user->shop->kyc_owner_url))
                                                                    <div class="mb-2">
                                                                        <a href="{{ $user->shop->kyc_owner_url }}" target="_blank" class="btn btn-primary btn-sm me-2">
                                                                            <i class="fa fa-file-pdf"></i> Open in New Tab
                                                                        </a>
                                                                        <a href="https://docs.google.com/viewer?url={{ urlencode($user->shop->kyc_owner_url) }}&embedded=true" target="_blank" class="btn btn-info btn-sm">
                                                                            <i class="fa fa-eye"></i> View with Google Viewer
                                                                        </a>
                                                                    </div>
                                                                    <iframe src="https://docs.google.com/viewer?url={{ urlencode($user->shop->kyc_owner_url) }}&embedded=true" 
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

@endsection

