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
                            <h4 class="card-title" style="color: #000; font-weight: 600; font-size: 1.5rem;">SR User Details (Shop + Recycler)</h4>
                            <a href="{{ route('srUsers') }}" class="btn btn-secondary btn-sm" style="font-weight: 500;">
                                <i class="fa fa-arrow-left"></i> Back to SR Users
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
                                                        <span style="color: #000; font-weight: 400;">{{ $user->phone ?? ($user->mob_num ?? 'N/A') }}</span>
                                                    </p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                        <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">User Type:</strong> 
                                                        <span style="color: #000; font-weight: 400;">{{ $user->user_type ?? 'N/A' }} (Shop + Recycler)</span>
                                                    </p>
                                                    <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                        <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">App Type:</strong> 
                                                        <span style="color: #000; font-weight: 400;">{{ $user->app_type ?? 'N/A' }}</span>
                                                    </p>
                                                    <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                        <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">Overall SR Status:</strong> 
                                                        @php
                                                            $srStatus = $user->srApprovalStatus ?? null;
                                                            // If not set, calculate from shops
                                                            if (!$srStatus && isset($b2bShop) && isset($b2cShop)) {
                                                                $b2bApproved = ($b2bShop->approval_status ?? null) === 'approved';
                                                                $b2cApproved = ($b2cShop->approval_status ?? null) === 'approved';
                                                                if ($b2bApproved && $b2cApproved) {
                                                                    $srStatus = 'approved';
                                                                } elseif (($b2bShop->approval_status ?? null) === 'rejected' || ($b2cShop->approval_status ?? null) === 'rejected') {
                                                                    $srStatus = 'rejected';
                                                                } else {
                                                                    $srStatus = 'pending';
                                                                }
                                                            } elseif (!$srStatus && isset($b2bShop)) {
                                                                $srStatus = $b2bShop->approval_status ?? 'pending';
                                                            } elseif (!$srStatus && isset($b2cShop)) {
                                                                $srStatus = $b2cShop->approval_status ?? 'pending';
                                                            } elseif (!$srStatus && isset($shop)) {
                                                                $srStatus = $shop->approval_status ?? 'pending';
                                                            }
                                                        @endphp
                                                        @if($srStatus === 'approved')
                                                            <span class="badge bg-success" style="font-size: 14px; padding: 8px 16px;">Approved</span>
                                                        @elseif($srStatus === 'pending')
                                                            <span class="badge bg-warning" style="font-size: 14px; padding: 8px 16px;">Pending</span>
                                                        @elseif($srStatus === 'rejected')
                                                            <span class="badge bg-danger" style="font-size: 14px; padding: 8px 16px;">Rejected</span>
                                                        @else
                                                            <span class="badge bg-secondary" style="font-size: 14px; padding: 8px 16px;">N/A</span>
                                                        @endif
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

                            @php
                                // Check for shops - prioritize b2bShop and b2cShop, fallback to shop
                                $b2bShop = (isset($user->b2bShop) && $user->b2bShop !== null) ? $user->b2bShop : null;
                                $b2cShop = (isset($user->b2cShop) && $user->b2cShop !== null) ? $user->b2cShop : null;
                                $shop = (isset($user->shop) && $user->shop !== null) ? $user->shop : null;
                                
                                // Use shop for backward compatibility if b2bShop/b2cShop not available
                                if (!$b2bShop && !$b2cShop && $shop) {
                                    // shop is already set
                                } elseif ($b2cShop && !$shop) {
                                    $shop = $b2cShop;
                                } elseif ($b2bShop && !$shop) {
                                    $shop = $b2bShop;
                                }
                                
                                $hasShop = ($b2bShop !== null) || ($b2cShop !== null) || ($shop !== null);
                            @endphp

                            @if($hasShop)
                                <!-- B2C Shop Information (if exists) -->
                                @if($b2cShop)
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <div class="card" style="border: 1px solid #e0e0e0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                            <div class="card-body" style="padding: 25px;">
                                                <h5 class="card-title mb-4" style="color: #333; font-weight: 600; font-size: 1.25rem; border-bottom: 2px solid #6c5ce7; padding-bottom: 10px;">
                                                    <i class="fa fa-store"></i> B2C Shop Information (Retailer)
                                                </h5>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                            <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">Shop Name:</strong> 
                                                            <span style="color: #000; font-weight: 400;">{{ $b2cShop->shopname ?? 'N/A' }}</span>
                                                        </p>
                                                        <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                            <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">Address:</strong> 
                                                            <span style="color: #000; font-weight: 400;">{{ $b2cShop->address ?? 'N/A' }}</span>
                                                        </p>
                                                        <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                            <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">Contact Number:</strong> 
                                                            <span style="color: #000; font-weight: 400;">{{ $b2cShop->contact ?? 'N/A' }}</span>
                                                        </p>
                                                        <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                            <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">Approval Status:</strong> 
                                                            @php
                                                                $b2cStatus = $b2cShop->approval_status ?? null;
                                                            @endphp
                                                            @if($b2cStatus === 'approved')
                                                                <span class="badge bg-success">Approved</span>
                                                            @elseif($b2cStatus === 'pending')
                                                                <span class="badge bg-warning">Pending</span>
                                                            @elseif($b2cStatus === 'rejected')
                                                                <span class="badge bg-danger">Rejected</span>
                                                            @else
                                                                <span class="badge bg-secondary">N/A</span>
                                                            @endif
                                                        </p>
                                                    </div>
                                                </div>
                                                
                                                <!-- B2C Approval Actions -->
                                                <div class="row mt-4">
                                                    <div class="col-md-12">
                                                        <div class="card" style="border: 1px solid #e0e0e0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                                            <div class="card-body" style="padding: 20px;">
                                                                <h6 class="card-title mb-3" style="color: #333; font-weight: 600; font-size: 1.1rem;">B2C Shop Approval Actions</h6>
                                                                @php
                                                                    $b2cRejectionReason = $b2cShop->rejection_reason ?? null;
                                                                @endphp
                                                                @if($b2cStatus === 'rejected' && $b2cRejectionReason)
                                                                    <div class="mb-3 alert alert-danger" style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;">
                                                                        <strong style="font-weight: 600;">Rejection Reason:</strong>
                                                                        <p class="mb-0 mt-2" style="color: #721c24;">{{ $b2cRejectionReason }}</p>
                                                                    </div>
                                                                @endif
                                                                <form id="b2cApprovalForm" action="{{ route('updateSRApprovalStatus', ['userId' => $user->id]) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    <input type="hidden" name="approval_status" id="b2c_approval_status_input" value="">
                                                                    <input type="hidden" name="rejection_reason" id="b2c_rejection_reason_input" value="">
                                                                    <input type="hidden" name="shop_type" value="b2c">
                                                                    <div class="btn-group" role="group">
                                                                        <button type="button" class="btn btn-success" 
                                                                                onclick="submitB2CApproval('approved')">
                                                                            <i class="fa fa-check"></i> Approve B2C
                                                                        </button>
                                                                        <button type="button" class="btn btn-danger"
                                                                                onclick="showB2CRejectionModal()">
                                                                            <i class="fa fa-times"></i> Reject B2C
                                                                        </button>
                                                                        <button type="button" class="btn btn-warning"
                                                                                onclick="submitB2CApproval('pending')">
                                                                            <i class="fa fa-clock"></i> Set Pending
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- B2C Documents -->
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <div class="card" style="border: 1px solid #e0e0e0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                            <div class="card-body" style="padding: 25px;">
                                                <h5 class="card-title mb-4" style="color: #333; font-weight: 600; font-size: 1.25rem; border-bottom: 2px solid #6c5ce7; padding-bottom: 10px;">B2C Shop Documents</h5>
                                                
                                                <div class="row">
                                                    <!-- Aadhar Card -->
                                                    <div class="col-md-6 mb-3">
                                                        <div class="card" style="border: 1px solid #e0e0e0;">
                                                            <div class="card-body" style="padding: 20px;">
                                                                <h6 class="card-subtitle mb-3" style="color: #000; font-weight: 600; font-size: 16px;">Aadhar Card</h6>
                                                                @if(!empty($b2cShop->aadhar_card))
                                                                    <div class="mb-2">
                                                                        <a href="{{ $b2cShop->aadhar_card }}" target="_blank" class="btn btn-primary btn-sm me-2">
                                                                            <i class="fa fa-file-pdf"></i> Open in New Tab
                                                                        </a>
                                                                        <a href="https://docs.google.com/viewer?url={{ urlencode($b2cShop->aadhar_card) }}&embedded=true" target="_blank" class="btn btn-info btn-sm">
                                                                            <i class="fa fa-eye"></i> View with Google Viewer
                                                                        </a>
                                                                    </div>
                                                                    <iframe src="https://docs.google.com/viewer?url={{ urlencode($b2cShop->aadhar_card) }}&embedded=true" 
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
                                                                @if(!empty($b2cShop->driving_license))
                                                                    <div class="mb-2">
                                                                        <a href="{{ $b2cShop->driving_license }}" target="_blank" class="btn btn-primary btn-sm me-2">
                                                                            <i class="fa fa-file-pdf"></i> Open in New Tab
                                                                        </a>
                                                                        <a href="https://docs.google.com/viewer?url={{ urlencode($b2cShop->driving_license) }}&embedded=true" target="_blank" class="btn btn-info btn-sm">
                                                                            <i class="fa fa-eye"></i> View with Google Viewer
                                                                        </a>
                                                                    </div>
                                                                    <iframe src="https://docs.google.com/viewer?url={{ urlencode($b2cShop->driving_license) }}&embedded=true" 
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
                                @endif

                                <!-- B2B Shop Information (if exists) -->
                                @if($b2bShop)
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <div class="card" style="border: 1px solid #e0e0e0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                            <div class="card-body" style="padding: 25px;">
                                                <h5 class="card-title mb-4" style="color: #333; font-weight: 600; font-size: 1.25rem; border-bottom: 2px solid #6c5ce7; padding-bottom: 10px;">
                                                    <i class="fa fa-building"></i> B2B Shop Information (Business)
                                                </h5>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                            <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">Company Name:</strong> 
                                                            <span style="color: #000; font-weight: 400;">{{ $b2bShop->company_name ?? 'N/A' }}</span>
                                                        </p>
                                                        <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                            <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">Shop Name:</strong> 
                                                            <span style="color: #000; font-weight: 400;">{{ $b2bShop->shopname ?? 'N/A' }}</span>
                                                        </p>
                                                        <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                            <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">Owner Name:</strong> 
                                                            <span style="color: #000; font-weight: 400;">{{ $b2bShop->ownername ?? 'N/A' }}</span>
                                                        </p>
                                                        <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                            <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">GST Number:</strong> 
                                                            <span style="color: #000; font-weight: 400;">{{ $b2bShop->gst_number ?? 'N/A' }}</span>
                                                        </p>
                                                        <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                            <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">PAN Number:</strong> 
                                                            <span style="color: #000; font-weight: 400;">{{ $b2bShop->pan_number ?? 'N/A' }}</span>
                                                        </p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                            <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">Address:</strong> 
                                                            <span style="color: #000; font-weight: 400;">{{ $b2bShop->address ?? 'N/A' }}</span>
                                                        </p>
                                                        <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                            <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">Contact Number:</strong> 
                                                            <span style="color: #000; font-weight: 400;">{{ $b2bShop->contact ?? 'N/A' }}</span>
                                                        </p>
                                                        <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                            <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">Approval Status:</strong> 
                                                            @php
                                                                $b2bStatus = $b2bShop->approval_status ?? null;
                                                            @endphp
                                                            @if($b2bStatus === 'approved')
                                                                <span class="badge bg-success">Approved</span>
                                                            @elseif($b2bStatus === 'pending')
                                                                <span class="badge bg-warning">Pending</span>
                                                            @elseif($b2bStatus === 'rejected')
                                                                <span class="badge bg-danger">Rejected</span>
                                                            @else
                                                                <span class="badge bg-secondary">N/A</span>
                                                            @endif
                                                        </p>
                                                    </div>
                                                </div>
                                                
                                                <!-- B2B Approval Actions -->
                                                <div class="row mt-4">
                                                    <div class="col-md-12">
                                                        <div class="card" style="border: 1px solid #e0e0e0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                                            <div class="card-body" style="padding: 20px;">
                                                                <h6 class="card-title mb-3" style="color: #333; font-weight: 600; font-size: 1.1rem;">B2B Shop Approval Actions</h6>
                                                                @php
                                                                    $b2bRejectionReason = $b2bShop->rejection_reason ?? null;
                                                                @endphp
                                                                @if($b2bStatus === 'rejected' && $b2bRejectionReason)
                                                                    <div class="mb-3 alert alert-danger" style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;">
                                                                        <strong style="font-weight: 600;">Rejection Reason:</strong>
                                                                        <p class="mb-0 mt-2" style="color: #721c24;">{{ $b2bRejectionReason }}</p>
                                                                    </div>
                                                                @endif
                                                                <form id="b2bApprovalForm" action="{{ route('updateSRApprovalStatus', ['userId' => $user->id]) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    <input type="hidden" name="approval_status" id="b2b_approval_status_input" value="">
                                                                    <input type="hidden" name="rejection_reason" id="b2b_rejection_reason_input" value="">
                                                                    <input type="hidden" name="shop_type" value="b2b">
                                                                    <div class="btn-group" role="group">
                                                                        <button type="button" class="btn btn-success" 
                                                                                onclick="submitB2BApproval('approved')">
                                                                            <i class="fa fa-check"></i> Approve B2B
                                                                        </button>
                                                                        <button type="button" class="btn btn-danger"
                                                                                onclick="showB2BRejectionModal()">
                                                                            <i class="fa fa-times"></i> Reject B2B
                                                                        </button>
                                                                        <button type="button" class="btn btn-warning"
                                                                                onclick="submitB2BApproval('pending')">
                                                                            <i class="fa fa-clock"></i> Set Pending
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- B2B Documents -->
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <div class="card" style="border: 1px solid #e0e0e0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                            <div class="card-body" style="padding: 25px;">
                                                <h5 class="card-title mb-4" style="color: #333; font-weight: 600; font-size: 1.25rem; border-bottom: 2px solid #6c5ce7; padding-bottom: 10px;">B2B Shop Documents</h5>
                                                
                                                <div class="row">
                                                    <!-- Business License -->
                                                    <div class="col-md-6 mb-3">
                                                        <div class="card" style="border: 1px solid #e0e0e0;">
                                                            <div class="card-body" style="padding: 20px;">
                                                                <h6 class="card-subtitle mb-3" style="color: #000; font-weight: 600; font-size: 16px;">Business License</h6>
                                                                @if(!empty($b2bShop->business_license_url))
                                                                    <div class="mb-2">
                                                                        <a href="{{ $b2bShop->business_license_url }}" target="_blank" class="btn btn-primary btn-sm me-2">
                                                                            <i class="fa fa-file-pdf"></i> Open in New Tab
                                                                        </a>
                                                                        <a href="https://docs.google.com/viewer?url={{ urlencode($b2bShop->business_license_url) }}&embedded=true" target="_blank" class="btn btn-info btn-sm">
                                                                            <i class="fa fa-eye"></i> View with Google Viewer
                                                                        </a>
                                                                    </div>
                                                                    <iframe src="https://docs.google.com/viewer?url={{ urlencode($b2bShop->business_license_url) }}&embedded=true" 
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
                                                                @if(!empty($b2bShop->gst_certificate_url))
                                                                    <div class="mb-2">
                                                                        <a href="{{ $b2bShop->gst_certificate_url }}" target="_blank" class="btn btn-primary btn-sm me-2">
                                                                            <i class="fa fa-file-pdf"></i> Open in New Tab
                                                                        </a>
                                                                        <a href="https://docs.google.com/viewer?url={{ urlencode($b2bShop->gst_certificate_url) }}&embedded=true" target="_blank" class="btn btn-info btn-sm">
                                                                            <i class="fa fa-eye"></i> View with Google Viewer
                                                                        </a>
                                                                    </div>
                                                                    <iframe src="https://docs.google.com/viewer?url={{ urlencode($b2bShop->gst_certificate_url) }}&embedded=true" 
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
                                                                <h6 class="card-subtitle mb-3" style="color: #000; font-weight: 600; font-size: 16px;">Address Proof</h6>
                                                                @if(!empty($b2bShop->address_proof_url))
                                                                    <div class="mb-2">
                                                                        <a href="{{ $b2bShop->address_proof_url }}" target="_blank" class="btn btn-primary btn-sm me-2">
                                                                            <i class="fa fa-file-pdf"></i> Open in New Tab
                                                                        </a>
                                                                        <a href="https://docs.google.com/viewer?url={{ urlencode($b2bShop->address_proof_url) }}&embedded=true" target="_blank" class="btn btn-info btn-sm">
                                                                            <i class="fa fa-eye"></i> View with Google Viewer
                                                                        </a>
                                                                    </div>
                                                                    <iframe src="https://docs.google.com/viewer?url={{ urlencode($b2bShop->address_proof_url) }}&embedded=true" 
                                                                            style="width: 100%; height: 500px; margin-top: 10px; border: 1px solid #ddd;" 
                                                                            frameborder="0"></iframe>
                                                                @else
                                                                    <p style="color: #666; font-size: 14px;">No document uploaded</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- KYC Owner -->
                                                    <div class="col-md-6 mb-3">
                                                        <div class="card" style="border: 1px solid #e0e0e0;">
                                                            <div class="card-body" style="padding: 20px;">
                                                                <h6 class="card-subtitle mb-3" style="color: #000; font-weight: 600; font-size: 16px;">KYC Owner</h6>
                                                                @if(!empty($b2bShop->kyc_owner_url))
                                                                    <div class="mb-2">
                                                                        <a href="{{ $b2bShop->kyc_owner_url }}" target="_blank" class="btn btn-primary btn-sm me-2">
                                                                            <i class="fa fa-file-pdf"></i> Open in New Tab
                                                                        </a>
                                                                        <a href="https://docs.google.com/viewer?url={{ urlencode($b2bShop->kyc_owner_url) }}&embedded=true" target="_blank" class="btn btn-info btn-sm">
                                                                            <i class="fa fa-eye"></i> View with Google Viewer
                                                                        </a>
                                                                    </div>
                                                                    <iframe src="https://docs.google.com/viewer?url={{ urlencode($b2bShop->kyc_owner_url) }}&embedded=true" 
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
                                @endif

                                <!-- Legacy Shop Display (if b2bShop/b2cShop not available but shop exists) -->
                                @if(!$b2bShop && !$b2cShop && $shop)
                                <!-- Address Information -->
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <div class="card" style="border: 1px solid #e0e0e0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                            <div class="card-body" style="padding: 25px;">
                                                <h5 class="card-title mb-4" style="color: #333; font-weight: 600; font-size: 1.25rem; border-bottom: 2px solid #6c5ce7; padding-bottom: 10px;">Address Information</h5>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                            <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">Shop Name:</strong> 
                                                            <span style="color: #000; font-weight: 400;">{{ $shop->shopname ?? 'N/A' }}</span>
                                                        </p>
                                                        <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                            <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">Address:</strong> 
                                                            <span style="color: #000; font-weight: 400;">{{ $shop->address ?? 'N/A' }}</span>
                                                        </p>
                                                        <p class="mb-3" style="color: #000; font-size: 15px; line-height: 1.8;">
                                                            <strong style="color: #000; font-weight: 600; min-width: 140px; display: inline-block;">Contact Number:</strong> 
                                                            <span style="color: #000; font-weight: 400;">{{ $shop->contact ?? 'N/A' }}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Approval Status (for backward compatibility with legacy shop) -->
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <div class="card" style="border: 1px solid #e0e0e0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                            <div class="card-body" style="padding: 25px;">
                                                <h5 class="card-title mb-4" style="color: #333; font-weight: 600; font-size: 1.25rem; border-bottom: 2px solid #6c5ce7; padding-bottom: 10px;">Approval Status</h5>
                                                @php
                                                    $approvalStatus = $shop->approval_status ?? null;
                                                    $rejectionReason = $shop->rejection_reason ?? null;
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
                                                <form id="approvalForm" action="{{ route('updateSRApprovalStatus', ['userId' => $user->id]) }}" method="POST" class="d-inline">
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

                                <!-- Legacy Documents (if b2bShop/b2cShop not available but shop exists) -->
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
                                                                @if(!empty($shop->aadhar_card))
                                                                    <div class="mb-2">
                                                                        <a href="{{ $shop->aadhar_card }}" target="_blank" class="btn btn-primary btn-sm me-2">
                                                                            <i class="fa fa-file-pdf"></i> Open in New Tab
                                                                        </a>
                                                                        <a href="https://docs.google.com/viewer?url={{ urlencode($shop->aadhar_card) }}&embedded=true" target="_blank" class="btn btn-info btn-sm">
                                                                            <i class="fa fa-eye"></i> View with Google Viewer
                                                                        </a>
                                                                    </div>
                                                                    <iframe src="https://docs.google.com/viewer?url={{ urlencode($shop->aadhar_card) }}&embedded=true" 
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
                                                                @if(!empty($shop->driving_license))
                                                                    <div class="mb-2">
                                                                        <a href="{{ $shop->driving_license }}" target="_blank" class="btn btn-primary btn-sm me-2">
                                                                            <i class="fa fa-file-pdf"></i> Open in New Tab
                                                                        </a>
                                                                        <a href="https://docs.google.com/viewer?url={{ urlencode($shop->driving_license) }}&embedded=true" target="_blank" class="btn btn-info btn-sm">
                                                                            <i class="fa fa-eye"></i> View with Google Viewer
                                                                        </a>
                                                                    </div>
                                                                    <iframe src="https://docs.google.com/viewer?url={{ urlencode($shop->driving_license) }}&embedded=true" 
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
                                @endif
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

<!-- B2B Rejection Reason Modal -->
<div class="modal fade" id="b2bRejectionModal" tabindex="-1" aria-labelledby="b2bRejectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="b2bRejectionModalLabel">Select Rejection Reason for B2B Shop</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Please select a reason for rejection:</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="b2b_rejection_reason" id="b2b_reason1" value="Business license not proper">
                        <label class="form-check-label" for="b2b_reason1">
                            Business license not proper
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="b2b_rejection_reason" id="b2b_reason2" value="GST certificate not proper">
                        <label class="form-check-label" for="b2b_reason2">
                            GST certificate not proper
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="b2b_rejection_reason" id="b2b_reason3" value="Address proof not proper">
                        <label class="form-check-label" for="b2b_reason3">
                            Address proof not proper
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="b2b_rejection_reason" id="b2b_reason4" value="KYC owner document not proper">
                        <label class="form-check-label" for="b2b_reason4">
                            KYC owner document not proper
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="b2b_rejection_reason" id="b2b_reason5" value="Company information incomplete">
                        <label class="form-check-label" for="b2b_reason5">
                            Company information incomplete
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="b2b_rejection_reason" id="b2b_reason6" value="Documents are unclear or incomplete">
                        <label class="form-check-label" for="b2b_reason6">
                            Documents are unclear or incomplete
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="b2b_rejection_reason" id="b2b_reason7" value="Information mismatch in documents">
                        <label class="form-check-label" for="b2b_reason7">
                            Information mismatch in documents
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="b2b_rejection_reason" id="b2b_reason8" value="Other">
                        <label class="form-check-label" for="b2b_reason8">
                            Other
                        </label>
                    </div>
                </div>
                <div class="mb-3" id="b2bOtherReasonDiv" style="display: none;">
                    <label for="b2bOtherReason" class="form-label">Please specify:</label>
                    <textarea class="form-control" id="b2bOtherReason" rows="3" placeholder="Enter rejection reason"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmB2BRejection()">Confirm Rejection</button>
            </div>
        </div>
    </div>
</div>

<!-- B2C Rejection Reason Modal -->
<div class="modal fade" id="b2cRejectionModal" tabindex="-1" aria-labelledby="b2cRejectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="b2cRejectionModalLabel">Select Rejection Reason for B2C Shop</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Please select a reason for rejection:</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="b2c_rejection_reason" id="b2c_reason1" value="Aadhar card not proper">
                        <label class="form-check-label" for="b2c_reason1">
                            Aadhar card not proper
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="b2c_rejection_reason" id="b2c_reason2" value="Contact address not proper">
                        <label class="form-check-label" for="b2c_reason2">
                            Contact address not proper
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="b2c_rejection_reason" id="b2c_reason3" value="Email not proper">
                        <label class="form-check-label" for="b2c_reason3">
                            Email not proper
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="b2c_rejection_reason" id="b2c_reason4" value="Driving license not proper">
                        <label class="form-check-label" for="b2c_reason4">
                            Driving license not proper
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="b2c_rejection_reason" id="b2c_reason5" value="Documents are unclear or incomplete">
                        <label class="form-check-label" for="b2c_reason5">
                            Documents are unclear or incomplete
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="b2c_rejection_reason" id="b2c_reason6" value="Information mismatch in documents">
                        <label class="form-check-label" for="b2c_reason6">
                            Information mismatch in documents
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="b2c_rejection_reason" id="b2c_reason7" value="Other">
                        <label class="form-check-label" for="b2c_reason7">
                            Other
                        </label>
                    </div>
                </div>
                <div class="mb-3" id="b2cOtherReasonDiv" style="display: none;">
                    <label for="b2cOtherReason" class="form-label">Please specify:</label>
                    <textarea class="form-control" id="b2cOtherReason" rows="3" placeholder="Enter rejection reason"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmB2CRejection()">Confirm Rejection</button>
            </div>
        </div>
    </div>
</div>

<!-- Legacy Rejection Reason Modal -->
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
// B2B Approval Functions
function submitB2BApproval(status) {
    if (status === 'approved' && !confirm('Are you sure you want to approve this B2B shop?')) {
        return;
    }
    if (status === 'pending' && !confirm('Are you sure you want to set B2B status to pending?')) {
        return;
    }
    
    document.getElementById('b2b_approval_status_input').value = status;
    document.getElementById('b2b_rejection_reason_input').value = '';
    document.getElementById('b2bApprovalForm').submit();
}

function showB2BRejectionModal() {
    // Reset form
    document.querySelectorAll('input[name="b2b_rejection_reason"]').forEach(radio => {
        radio.checked = false;
    });
    document.getElementById('b2bOtherReason').value = '';
    document.getElementById('b2bOtherReasonDiv').style.display = 'none';
    
    // Show modal
    var modal = new bootstrap.Modal(document.getElementById('b2bRejectionModal'));
    modal.show();
    
    // Show other reason textarea if "Other" is selected
    document.getElementById('b2b_reason8').addEventListener('change', function() {
        if (this.checked) {
            document.getElementById('b2bOtherReasonDiv').style.display = 'block';
        } else {
            document.getElementById('b2bOtherReasonDiv').style.display = 'none';
        }
    });
}

function confirmB2BRejection() {
    var selectedReason = document.querySelector('input[name="b2b_rejection_reason"]:checked');
    
    if (!selectedReason) {
        alert('Please select a rejection reason');
        return;
    }
    
    var rejectionReason = selectedReason.value;
    
    if (rejectionReason === 'Other') {
        var otherReason = document.getElementById('b2bOtherReason').value.trim();
        if (!otherReason) {
            alert('Please specify the rejection reason');
            return;
        }
        rejectionReason = otherReason;
    }
    
    if (!confirm('Are you sure you want to reject this B2B shop with reason: ' + rejectionReason + '?')) {
        return;
    }
    
    document.getElementById('b2b_approval_status_input').value = 'rejected';
    document.getElementById('b2b_rejection_reason_input').value = rejectionReason;
    document.getElementById('b2bApprovalForm').submit();
}

// B2C Approval Functions
function submitB2CApproval(status) {
    if (status === 'approved' && !confirm('Are you sure you want to approve this B2C shop?')) {
        return;
    }
    if (status === 'pending' && !confirm('Are you sure you want to set B2C status to pending?')) {
        return;
    }
    
    document.getElementById('b2c_approval_status_input').value = status;
    document.getElementById('b2c_rejection_reason_input').value = '';
    document.getElementById('b2cApprovalForm').submit();
}

function showB2CRejectionModal() {
    // Reset form
    document.querySelectorAll('input[name="b2c_rejection_reason"]').forEach(radio => {
        radio.checked = false;
    });
    document.getElementById('b2cOtherReason').value = '';
    document.getElementById('b2cOtherReasonDiv').style.display = 'none';
    
    // Show modal
    var modal = new bootstrap.Modal(document.getElementById('b2cRejectionModal'));
    modal.show();
    
    // Show other reason textarea if "Other" is selected
    document.getElementById('b2c_reason7').addEventListener('change', function() {
        if (this.checked) {
            document.getElementById('b2cOtherReasonDiv').style.display = 'block';
        } else {
            document.getElementById('b2cOtherReasonDiv').style.display = 'none';
        }
    });
}

function confirmB2CRejection() {
    var selectedReason = document.querySelector('input[name="b2c_rejection_reason"]:checked');
    
    if (!selectedReason) {
        alert('Please select a rejection reason');
        return;
    }
    
    var rejectionReason = selectedReason.value;
    
    if (rejectionReason === 'Other') {
        var otherReason = document.getElementById('b2cOtherReason').value.trim();
        if (!otherReason) {
            alert('Please specify the rejection reason');
            return;
        }
        rejectionReason = otherReason;
    }
    
    if (!confirm('Are you sure you want to reject this B2C shop with reason: ' + rejectionReason + '?')) {
        return;
    }
    
    document.getElementById('b2c_approval_status_input').value = 'rejected';
    document.getElementById('b2c_rejection_reason_input').value = rejectionReason;
    document.getElementById('b2cApprovalForm').submit();
}

// Legacy Approval Functions (for backward compatibility)
function submitApproval(status) {
    if (status === 'approved' && !confirm('Are you sure you want to approve this SR user?')) {
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
    
    if (!confirm('Are you sure you want to reject this SR user with reason: ' + rejectionReason + '?')) {
        return;
    }
    
    document.getElementById('approval_status_input').value = 'rejected';
    document.getElementById('rejection_reason_input').value = rejectionReason;
    document.getElementById('approvalForm').submit();
}
</script>

@endsection


