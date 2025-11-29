@extends('index')
@section('content')
<style>
/* Custom Styles for Enhanced Design */
.subscription-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.subscription-card:hover {
    transform: translateY(-10px);
}

.subscription-card:hover .card {
    box-shadow: 0 20px 40px rgba(0,0,0,0.1) !important;
}

.icon-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.price-wrapper {
    position: relative;
    margin-bottom: 1rem;
}

.currency {
    position: relative;
    top: -20px;
    font-weight: 500;
}

.price {
    line-height: 1;
    margin: 0 5px;
}

.period {
    font-size: 0.9rem;
    font-weight: 500;
}

.popular-badge {
    z-index: 10;
}

.bg-gradient-light {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
}

.features-list ul li {
    text-align: left;
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.features-list ul li:last-child {
    border-bottom: none;
}

.stat-item {
    padding: 1rem;
    border-right: 1px solid rgba(0,0,0,0.1);
}

.stat-item:last-child {
    border-right: none;
}

/* Toggle Switch Styles */
.form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
}

.form-check-input:focus {
    border-color: #28a745;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
}

.status-toggle {
    cursor: pointer;
}

.status-badge {
    transition: all 0.3s ease;
}

/* Inactive card styling */
.card.opacity-75 {
    filter: grayscale(20%);
}

.card.opacity-75 .card-header {
    background: linear-gradient(135deg, #f1f3f4 0%, #e8eaed 100%) !important;
}

.card.opacity-75 .price {
    color: #6c757d !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .stat-item {
        border-right: none;
        border-bottom: 1px solid rgba(0,0,0,0.1);
    }
    
    .stat-item:last-child {
        border-bottom: none;
    }
    
    .subscription-card:hover {
        transform: none;
    }
}

/* Card hover effects */
.card {
    transition: all 0.3s ease;
}

.btn {
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
}

/* Animation for loading */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.subscription-card {
    animation: fadeInUp 0.6s ease forwards;
}

.subscription-card:nth-child(2) {
    animation-delay: 0.1s;
}

.subscription-card:nth-child(3) {
    animation-delay: 0.2s;
}

.subscription-card:nth-child(4) {
    animation-delay: 0.3s;
}
</style>
<div class="content-body">
    <div class="container-fluid">
        <!-- Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h2 class="mb-2 text-dark fw-bold">Subscription Plans</h2>
                        <p class="text-muted mb-0">Manage your subscription packages and pricing</p>
                    </div>
                    <button type="button" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm" onclick="large_modal('','createSubPackage','Add Subscription Package')" data-bs-toggle="modal" data-bs-target=".bd-example-modal-lg">
                        <i class="fas fa-plus me-2"></i>Add New Package
                    </button>
                </div>
            </div>
        </div>

        @include('layouts.flashmessage')

        <!-- Plans Grid -->
        <div class="row g-4">
            @foreach($packages as $index => $plan)
            <div class="col-xl-4 col-lg-6 col-md-6">
                <div class="subscription-card h-100 position-relative">
                    <div class="card border-0 shadow-lg rounded-4 h-100 overflow-hidden {{ $index == 1 ? 'border-primary' : '' }} {{ isset($plan->status) && $plan->status == 0 ? 'opacity-75' : '' }}">
                        
                        <!-- Status Toggle - Top Right Corner -->
                        <div class="position-absolute top-0 end-0 p-3" style="z-index: 10;">
                            <div class="form-check form-switch">
                                <input class="form-check-input status-toggle" type="checkbox" 
                                       id="statusToggle{{ $plan->id }}" 
                                       data-plan-id="{{ $plan->id }}"
                                       {{ (isset($plan->status) && $plan->status == 1) || !isset($plan->status) ? 'checked' : '' }} onclick="togglePlanStatus(this)">
                                <label class="form-check-label text-muted small" for="statusToggle{{ $plan->id }}">
                                    {{ (isset($plan->status) && $plan->status == 1) || !isset($plan->status) ? 'Active' : 'Inactive' }}
                                </label>
                            </div>
                        </div>

                        <!-- Card Header -->
                        <div class="card-header bg-gradient-light border-0 text-center py-4">
                            <div class="plan-icon mb-3">
                                @if(strtolower($plan->type ?? '') == 1)
                                    <div class="icon-circle bg-success bg-opacity-10 text-info">
                                        <i class="material-icons">star</i>
                                    </div>
                                @else
                                    <div class="icon-circle bg-info bg-opacity-10 text-success">
                                        <i class="material-icons">rocket</i>
                                    </div>
                                @endif
                            </div>
                            <h4 class="plan-name mb-2 fw-bold text-dark">{{ $plan->name ?? 'Unnamed Plan' }}</h4>
                            <p class="text-muted mb-0 text-capitalize">{{ $plan->type ?? 'Standard' }} Package</p>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body text-center">
                            <!-- Pricing -->
                            <div class="pricing-section">
                                <div class="price-wrapper">
                                    <span class="currency text-muted fs-5">₹</span>
                                    <span class="price display-4 fw-bold text-primary">{{ $plan->price ?? '0' }}</span>
                                    <span class="period text-muted">/{{ $plan->duration ?? '' }} days</span>
                                </div>
                            </div>

                            <!-- Features List -->
                            <div class="features-list mb-1">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="material-icons text-success me-2">check</i>
                                        <span>{{ $plan->duration ?? '' }} days access</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Status Badge -->
                            <div class="status-badge mb-3">
                                @if((isset($plan->status) && $plan->status == 2))
                                    <span class="badge bg-secondary fs-6 px-3 py-2">
                                        {{-- <i class="material-icons">pause</i> --}}
                                        <i class="fas fa-pause me-1"></i>Inactive
                                    </span>
                                @else
                                    <span class="badge bg-success fs-6 px-3 py-2">
                                        <i class="fas fa-check-circle me-1"></i>Active
                                    </span>
                                @endif
                            </div>
                        </div> 
                        
                        <div class="d-flex justify-content-center mb-3">
                            @if($plan->type == 1)
                                <span class="badge badge-success light fs-6 px-3 py-2">Free Package</span>
                            @elseif($plan->type == 2)
                                <span class="badge badge-danger light fs-6 px-3 py-2">Paid Package</span>
                            @endif
                        </div>

                        <!-- Card Footer -->
                        <div class="card-footer bg-transparent border-0 pb-4 d-flex justify-content-center">
                            <button type="button" class="btn btn-primary rounded-3 shadow-sm me-2" 
                                    onclick="large_modal('{{ $plan->id }}','editSubPackage','Edit Subscription Package')" 
                                    data-bs-toggle="modal" data-bs-target=".bd-example-modal-lg">
                                Edit Plan
                            </button>
                            <a href="javascript:;" 
                               onclick="custom_delete('/delSubPackage/{{ $plan->id }}')"  
                               data-bs-toggle="modal" data-bs-target=".bd-example-modal-sm" 
                               class="btn btn-outline-danger rounded-3" title="Delete User">
                               Delete
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach

            <!-- Add New Package Card -->
            <div class="col-xl-4 col-lg-6 col-md-6">
                <div class="h-100">
                    <div class="card border-2 border-dashed border-primary bg-light h-100 rounded-4 d-flex align-items-center justify-content-center" style="min-height: 400px;">
                        <div class="text-center p-4">
                            <div class="icon-circle bg-primary bg-opacity-10 text-primary mb-3">
                                <i class="fas fa-plus fs-2"></i>
                            </div>
                            <h5 class="text-primary mb-3">Add New Package</h5>
                            <p class="text-muted mb-4">Create a new subscription plan for your users</p>
                            <button type="button" class="btn btn-primary px-4 py-2 rounded-3" 
                                    onclick="large_modal('','createSubPackage','Add Subscription Package')" 
                                    data-bs-toggle="modal" data-bs-target=".bd-example-modal-lg">
                                Create Package
                            </button>
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
function togglePlanStatus(checkbox) {
    var planId = checkbox.getAttribute('data-plan-id');
    $.ajax({
        url: '/updateSubPackageStatus',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            planId: planId
        },
        success: function(response) {
            if (response.success) {
                // alert(response.message);
                location.reload();
            } else {
                location.reload();
                // alert(response.message);
            }
        }
    });
}
</script>
@endsection