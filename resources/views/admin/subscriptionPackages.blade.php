@extends('index')
@section('content')

<div class="content-body">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        @include('layouts.flashmessage')
                        
                        <!-- Header Section -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h4 class="card-title mb-1">Subscription Packages List</h4>
                                <p class="text-muted mb-0">Manage your subscription packages and pricing</p>
                            </div>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPackageModal">
                                <i class="fa fa-plus"></i> Add New Package
                            </button>
                        </div>
                        <hr>
                        
                        @if(isset($error))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error!</strong> {{ $error }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <strong>Success!</strong> {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <!-- Packages Grid -->
                        <div class="row" id="packagesContainer">
                            @forelse($packages as $package)
                                <div class="col-lg-4 col-md-6 mb-4">
                                    <div class="card h-100 package-card {{ isset($package['popular']) && $package['popular'] ? 'popular-package' : '' }} {{ isset($package['duration']) && $package['duration'] === 'year' ? 'yearly-package' : '' }}" style="position: relative;">
                                        @if(isset($package['popular']) && $package['popular'])
                                            <span class="badge bg-primary position-absolute top-0 end-0 m-2">Most Popular</span>
                                        @endif
                                        
                                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <div class="package-icon me-2">
                                                    <i class="fa fa-star text-success"></i>
                                                </div>
                                                <div>
                                                    <h5 class="card-title mb-0">{{ $package['name'] ?? 'N/A' }}</h5>
                                                    <small class="text-muted">{{ isset($package['userType']) ? strtoupper($package['userType']) : 'Package' }}</small>
                                                </div>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input package-status-toggle" type="checkbox" 
                                                       data-package-id="{{ $package['id'] }}"
                                                       {{ (!isset($package['isActive']) || $package['isActive']) ? 'checked' : '' }}
                                                       onchange="togglePackageStatus('{{ $package['id'] }}', this.checked)">
                                                <label class="form-check-label small">Active</label>
                                            </div>
                                        </div>
                                        
                                        <div class="card-body">
                                            <div class="text-center mb-3">
                                                <h2 class="text-primary mb-0">
                                                    ₹ {{ number_format($package['price'] ?? 0, 0) }}
                                                </h2>
                                                <p class="text-muted mb-0">
                                                    / {{ ucfirst($package['duration'] ?? 'N/A') }}
                                                    @if(isset($package['duration']) && $package['duration'] === 'order')
                                                        + GST
                                                    @else
                                                        + GST
                                                    @endif
                                                </p>
                                            </div>
                                            
                                            @if(isset($package['description']) && !empty($package['description']))
                                                <p class="text-muted small mb-3">{{ Str::limit($package['description'], 100) }}</p>
                                            @endif
                                            
                                            @if(isset($package['features']) && is_array($package['features']) && count($package['features']) > 0)
                                                <div class="features-list mb-3">
                                                    @foreach(array_slice($package['features'], 0, 3) as $feature)
                                                        <div class="d-flex align-items-center mb-2">
                                                            <i class="fa fa-check-circle text-success me-2"></i>
                                                            <small>{{ $feature }}</small>
                                                        </div>
                                                    @endforeach
                                                    @if(count($package['features']) > 3)
                                                        <small class="text-muted">+ {{ count($package['features']) - 3 }} more features</small>
                                                    @endif
                                                </div>
                                            @endif
                                            
                                            <div class="mb-3">
                                                <small class="text-muted d-block">UPI ID:</small>
                                                <strong>{{ $package['upiId'] ?? 'N/A' }}</strong>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <small class="text-muted d-block">Merchant:</small>
                                                <strong>{{ $package['merchantName'] ?? 'N/A' }}</strong>
                                            </div>
                                            
                                            <div class="d-grid gap-2">
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                        onclick="editPackage('{{ $package['id'] }}')">
                                                    <i class="fa fa-edit"></i> Edit Plan
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="deletePackage('{{ $package['id'] }}', '{{ $package['name'] ?? 'this package' }}')">
                                                    <i class="fa fa-trash"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="alert alert-info text-center">
                                        <i class="fa fa-info-circle fa-2x mb-3"></i>
                                        <h5>No subscription packages found</h5>
                                        <p>Click "Add New Package" to create your first subscription plan.</p>
                                        <p class="small text-muted">Or run the seed script: <code>npm run seed:subscription-packages</code></p>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Package Modal -->
<div class="modal fade" id="editPackageModal" tabindex="-1" aria-labelledby="editPackageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPackageModalLabel">Edit Subscription Package</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editPackageForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Package Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price (₹) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="price" id="edit_price" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Duration <span class="text-danger">*</span></label>
                            <select class="form-select" name="duration" id="edit_duration" required>
                                <option value="month">Per Month</option>
                                <option value="year">Per Year</option>
                                <option value="order">Per Order</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">User Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="userType" id="edit_userType" required>
                            <option value="b2b">B2B</option>
                            <option value="b2c">B2C</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Features (one per line)</label>
                        <textarea class="form-control" name="features" id="edit_features" rows="5" placeholder="Unlimited orders&#10;Priority support&#10;Real-time tracking"></textarea>
                        <small class="form-text text-muted">Enter each feature on a new line</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">UPI ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="upiId" id="edit_upiId" placeholder="7736068251@pthdfc" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Merchant Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="merchantName" id="edit_merchantName" placeholder="Scrapmate Partner" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="popular" value="1" id="edit_popular">
                                <label class="form-check-label" for="edit_popular">Mark as Popular</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="isActive" value="1" id="edit_isActive" checked>
                                <label class="form-check-label" for="edit_isActive">Active</label>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="id" id="edit_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Update Package
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Package Modal -->
<div class="modal fade" id="addPackageModal" tabindex="-1" aria-labelledby="addPackageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPackageModalLabel">Add New Subscription Package</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addPackageForm" method="POST" action="{{ url('/subPackages/new') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Package ID <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="id" required placeholder="e.g., b2b-order, b2c-monthly">
                        <small class="form-text text-muted">Unique identifier for this package</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Package Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price (₹) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="price" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Duration <span class="text-danger">*</span></label>
                            <select class="form-select" name="duration" required>
                                <option value="month">Per Month</option>
                                <option value="year">Per Year</option>
                                <option value="order">Per Order</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">User Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="userType" required>
                            <option value="b2b">B2B</option>
                            <option value="b2c">B2C</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Features (one per line)</label>
                        <textarea class="form-control" name="features" rows="5" placeholder="Unlimited orders&#10;Priority support&#10;Real-time tracking"></textarea>
                        <small class="form-text text-muted">Enter each feature on a new line</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">UPI ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="upiId" placeholder="7736068251@pthdfc" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Merchant Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="merchantName" placeholder="Scrapmate Partner" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="popular" value="1">
                                <label class="form-check-label">Mark as Popular</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="isActive" value="1" checked>
                                <label class="form-check-label">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Create Package
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .package-card {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    
    .package-card:hover {
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        transform: translateY(-2px);
    }
    
    .popular-package {
        border-color: #6c5ce7;
    }
    
    .yearly-package {
        border: none !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
    }
    
    .yearly-package.popular-package {
        border: none !important;
    }
    
    .package-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .features-list {
        border-top: 1px solid #eee;
        border-bottom: 1px solid #eee;
        padding: 10px 0;
    }
    
    .form-label {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
    }
    
    .form-control, .form-select {
        border: 1px solid #ddd;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #6c5ce7;
        box-shadow: 0 0 0 0.2rem rgba(108, 92, 231, 0.25);
    }
    
    .card-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
    }
</style>

<script>
// Package data from server
const packagesData = @json($packages ?? []);

function editPackage(packageId) {
    const package = packagesData.find(p => p.id === packageId);
    if (!package) {
        alert('Package not found');
        return;
    }
    
    // Populate form
    document.getElementById('edit_id').value = package.id;
    document.getElementById('edit_name').value = package.name || '';
    document.getElementById('edit_price').value = package.price || 0;
    document.getElementById('edit_duration').value = package.duration || 'month';
    document.getElementById('edit_userType').value = package.userType || 'b2b';
    document.getElementById('edit_description').value = package.description || '';
    document.getElementById('edit_features').value = Array.isArray(package.features) ? package.features.join('\n') : (package.features || '');
    document.getElementById('edit_upiId').value = package.upiId || '';
    document.getElementById('edit_merchantName').value = package.merchantName || '';
    document.getElementById('edit_popular').checked = package.popular || false;
    document.getElementById('edit_isActive').checked = package.isActive !== false;
    
    // Set form action
    document.getElementById('editPackageForm').action = '{{ url("/subPackages") }}/' + packageId;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('editPackageModal'));
    modal.show();
}

function deletePackage(packageId, packageName) {
    if (!confirm(`Are you sure you want to delete "${packageName}"? This action cannot be undone.`)) {
        return;
    }
    
    // Create a form and submit DELETE request
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ url("/subPackages") }}/' + packageId;
    
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = '{{ csrf_token() }}';
    form.appendChild(csrfToken);
    
    const methodField = document.createElement('input');
    methodField.type = 'hidden';
    methodField.name = '_method';
    methodField.value = 'DELETE';
    form.appendChild(methodField);
    
    document.body.appendChild(form);
    form.submit();
}

function togglePackageStatus(packageId, isActive) {
    // This would require an AJAX call or form submission
    // For now, we'll just show a message
    console.log('Toggle package status:', packageId, isActive);
    // You can implement AJAX call here to update status without page reload
}
</script>

@endsection
