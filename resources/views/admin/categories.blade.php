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
                                <h4 class="card-title mb-1">Categories & Subcategories Management</h4>
                                <p class="text-muted mb-0">Manage main categories and their subcategories</p>
                            </div>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                <i class="fa fa-plus"></i> Add Category
                            </button>
                        </div>
                        <hr>
                        
                        @include('layouts.flashmessage')
                        
                        @if(isset($error))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error!</strong> {{ $error }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <!-- Categories List -->
                        @if(count($categories) > 0)
                        <div class="row">
                            @foreach($categories as $category)
                                <div class="col-md-6 mb-4">
                                    <div class="card border">
                                        <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h5 class="mb-0" style="color: white; font-weight: 600; flex: 1; margin-right: 1rem;">{{ $category['category_name'] ?? 'N/A' }}</h5>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#editCategoryModal{{ $category['id'] }}" style="white-space: nowrap;">
                                                        <i class="fa fa-edit"></i> Edit
                                                    </button>
                                                    <form action="{{ route('categories.destroy', $category['id']) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category? This will also delete all subcategories under it if any exist.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" style="white-space: nowrap;">
                                                            <i class="fa fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                @php
                                                    // Get image URL from category data
                                                    $categoryImage = $category['category_img'] ?? $category['cat_img'] ?? '';
                                                    
                                                    // DEBUG: Log the raw image URL for debugging
                                                    if (isset($category['id']) && $category['id'] == 7) {
                                                        \Log::info('🔍 [DEBUG] Category 7 Image URL', [
                                                            'category_img' => $category['category_img'] ?? 'NOT SET',
                                                            'cat_img' => $category['cat_img'] ?? 'NOT SET',
                                                            'final_categoryImage' => $categoryImage,
                                                            'url_length' => strlen($categoryImage),
                                                            'is_presigned' => strpos($categoryImage, 'X-Amz-Signature') !== false
                                                        ]);
                                                    }
                                                    
                                                    // Validate URL - allow all valid HTTP(S) URLs (don't be too strict)
                                                    // Only reject obviously invalid URLs (too short or not HTTP)
                                                    if ($categoryImage) {
                                                        $isValidUrl = filter_var($categoryImage, FILTER_VALIDATE_URL) !== false;
                                                        $isHttpUrl = strpos($categoryImage, 'http://') === 0 || strpos($categoryImage, 'https://') === 0;
                                                        $hasMinLength = strlen($categoryImage) >= 10;
                                                        
                                                        // DEBUG: Log validation for category 7
                                                        if (isset($category['id']) && $category['id'] == 7) {
                                                            \Log::info('🔍 [DEBUG] Category 7 URL Validation', [
                                                                'isValidUrl' => $isValidUrl,
                                                                'isHttpUrl' => $isHttpUrl,
                                                                'hasMinLength' => $hasMinLength,
                                                                'will_clear' => !$isHttpUrl || !$hasMinLength
                                                            ]);
                                                        }
                                                        
                                                        // Only clear if URL is clearly invalid
                                                        if (!$isHttpUrl || !$hasMinLength) {
                                                            $categoryImage = ''; // Clear only obviously invalid URLs
                                                        }
                                                    }
                                                    
                                                    // Add cache-busting parameter to image URL - but NOT for presigned S3 URLs
                                                    // Presigned URLs have a signature based on exact query parameters - adding params breaks it!
                                                    if ($categoryImage) {
                                                        $isPresignedS3Url = strpos($categoryImage, 'X-Amz-Signature') !== false || 
                                                                           strpos($categoryImage, 'X-Amz-Algorithm') !== false;
                                                        
                                                        // DEBUG: Log presigned detection for category 7
                                                        if (isset($category['id']) && $category['id'] == 7) {
                                                            \Log::info('🔍 [DEBUG] Category 7 Presigned URL Check', [
                                                                'isPresigned' => $isPresignedS3Url,
                                                                'url_preview' => substr($categoryImage, 0, 100) . '...'
                                                            ]);
                                                        }
                                                        
                                                        // Only add cache-busting for non-presigned URLs
                                                        // Presigned URLs should be used as-is to preserve signature
                                                        if (!$isPresignedS3Url) {
                                                            $separator = strpos($categoryImage, '?') !== false ? '&' : '?';
                                                            // Use category ID + timestamp for unique cache-busting per category
                                                            $cacheBuster = isset($category['id']) 
                                                                ? 'v=' . time() . '&cat=' . $category['id'] 
                                                                : 'v=' . time() . '&r=' . mt_rand(1000, 9999);
                                                            $categoryImage = $categoryImage . $separator . $cacheBuster;
                                                        }
                                                        // For presigned URLs, use as-is - the presigned URL already handles access
                                                    }
                                                @endphp
                                                @if($categoryImage)
                                                    @php
                                                        // DEBUG: Log for category 7
                                                        if (isset($category['id']) && $category['id'] == 7) {
                                                            \Log::info('🖼️ [DEBUG] Category 7 Rendering Image', [
                                                                'final_url_length' => strlen($categoryImage),
                                                                'url_preview' => substr($categoryImage, 0, 150) . '...',
                                                                'url_ends_with' => substr($categoryImage, -50),
                                                                'has_X-Amz' => strpos($categoryImage, 'X-Amz') !== false
                                                            ]);
                                                        }
                                                    @endphp
                                                    <img src="{{ $categoryImage }}" 
                                                         alt="{{ $category['category_name'] ?? '' }}" 
                                                         class="img-thumbnail category-image" 
                                                         data-category-id="{{ $category['id'] ?? '' }}"
                                                         data-image-url="{{ $categoryImage }}"
                                                         style="max-width: 100px; max-height: 100px; object-fit: cover;" 
                                                         onerror="console.error('❌ Image failed to load for category {{ $category['id'] ?? 'N/A' }}'); console.error('   URL (first 200 chars):', this.src.substring(0, 200)); console.error('   URL length:', this.src.length); console.error('   Error event:', event); this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                                         onload="console.log('✅ Image loaded successfully for category {{ $category['id'] ?? 'N/A' }}');">
                                                    <div style="display: none; width: 100px; height: 100px; background-color: #f0f0f0; align-items: center; justify-content: center;" class="img-thumbnail">
                                                        <span class="text-muted small">No Image</span>
                                                    </div>
                                                @else
                                                    @php
                                                        // DEBUG: Log why no image for category 7
                                                        if (isset($category['id']) && $category['id'] == 7) {
                                                            \Log::warning('⚠️ [DEBUG] Category 7 has NO image URL', [
                                                                'category_img' => $category['category_img'] ?? 'NOT SET',
                                                                'cat_img' => $category['cat_img'] ?? 'NOT SET',
                                                                'final_categoryImage' => 'EMPTY'
                                                            ]);
                                                        }
                                                    @endphp
                                                    <div class="img-thumbnail d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; background-color: #f0f0f0;">
                                                        <span class="text-muted small">No Image</span>
                                                    </div>
                                                @endif
                                            </div>
                                            <p class="text-muted mb-3">Category ID: {{ $category['id'] }}</p>
                                            
                                            <!-- Subcategories Section -->
                                            <div class="subcategories-section">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="mb-0">Subcategories</h6>
                                                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addSubcategoryModal{{ $category['id'] }}">
                                                        <i class="fa fa-plus"></i> Add
                                                    </button>
                                                </div>
                                                
                                                @php
                                                    $subcategories = $subcategoriesMap[$category['id']] ?? [];
                                                @endphp
                                                
                                                @if(count($subcategories) > 0)
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-bordered">
                                                            <thead>
                                                                <tr>
                                                                    <th>Name</th>
                                                                    <th>Image</th>
                                                                    <th>Price</th>
                                                                    <th>Unit</th>
                                                                    <th>Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($subcategories as $subcategory)
                                                                    <tr>
                                                                        <td>{{ $subcategory['subcategory_name'] ?? 'N/A' }}</td>
                                                                        <td>
                                                                            @if(isset($subcategory['subcategory_img']) && $subcategory['subcategory_img'])
                                                                                <img src="{{ $subcategory['subcategory_img'] }}" 
                                                                                     alt="{{ $subcategory['subcategory_name'] }}" 
                                                                                     class="img-thumbnail" 
                                                                                     style="max-width: 50px; max-height: 50px; object-fit: cover;">
                                                                            @else
                                                                                <span class="text-muted small">No Image</span>
                                                                            @endif
                                                                        </td>
                                                                        <td>₹{{ $subcategory['default_price'] ?? '0' }}</td>
                                                                        <td>{{ $subcategory['price_unit'] ?? 'kg' }}</td>
                                                                        <td>
                                                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editSubcategoryModal{{ $subcategory['id'] }}">
                                                                                <i class="fa fa-edit"></i>
                                                                            </button>
                                                                            <a href="{{ route('deleteSubcategory', $subcategory['id']) }}" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this subcategory?')">
                                                                                <i class="fa fa-trash"></i>
                                                                            </a>
                                                                        </td>
                                                                    </tr>
                                                                    <!-- Edit Subcategory Modal -->
                                                                    <div class="modal fade" id="editSubcategoryModal{{ $subcategory['id'] }}" tabindex="-1">
                                                                        <div class="modal-dialog">
                                                                            <div class="modal-content">
                                                                                <div class="modal-header">
                                                                                    <h5 class="modal-title">Edit Subcategory</h5>
                                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                                </div>
                                                                                <form action="{{ route('updateSubcategory', $subcategory['id']) }}" method="POST" enctype="multipart/form-data">
                                                                                    @csrf
                                                                                    <div class="modal-body">
                                                                                        <div class="mb-3">
                                                                                            <label class="form-label">Subcategory Name <span class="text-danger">*</span></label>
                                                                                            <input type="text" class="form-control" name="subcategory_name" value="{{ $subcategory['subcategory_name'] ?? '' }}" required>
                                                                                        </div>
                                                                                        <div class="mb-3">
                                                                                            <label class="form-label">Subcategory Image</label>
                                                                                            <input type="file" class="form-control" name="subcategory_image" accept="image/jpeg,image/jpg,image/png,image/gif">
                                                                                            <small class="text-muted">Upload a new image (max 10MB) or use URL below</small>
                                                                                        </div>
                                                                                        <div class="mb-3">
                                                                                            <label class="form-label">Subcategory Image URL</label>
                                                                                            <input type="text" class="form-control" name="subcategory_img" value="{{ $subcategory['subcategory_img'] ?? '' }}" placeholder="Or enter image URL" maxlength="2000">
                                                                                            @if(isset($subcategory['subcategory_img']))
                                                                                                <small class="text-muted d-block mt-2">Current image:</small>
                                                                                                <img src="{{ $subcategory['subcategory_img'] }}" alt="Subcategory Image" class="img-thumbnail mt-2" style="max-width: 200px;">
                                                                                            @endif
                                                                                        </div>
                                                                                        <div class="mb-3">
                                                                                            <label class="form-label">Default Price</label>
                                                                                            <input type="text" class="form-control" name="default_price" value="{{ $subcategory['default_price'] ?? '0' }}">
                                                                                        </div>
                                                                                        <div class="mb-3">
                                                                                            <label class="form-label">Price Unit</label>
                                                                                            <select class="form-select" name="price_unit">
                                                                                                <option value="kg" {{ ($subcategory['price_unit'] ?? 'kg') === 'kg' ? 'selected' : '' }}>kg</option>
                                                                                                <option value="pcs" {{ ($subcategory['price_unit'] ?? 'kg') === 'pcs' ? 'selected' : '' }}>pcs</option>
                                                                                            </select>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="modal-footer">
                                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                                        <button type="submit" class="btn btn-primary">Update</button>
                                                                                    </div>
                                                                                </form>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @else
                                                    <p class="text-muted">No subcategories found. Click "Add" to create one.</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Edit Category Modal -->
                                <div class="modal fade" id="editCategoryModal{{ $category['id'] }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Category</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="{{ route('updateCategory', $category['id']) }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Category Name <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="category_name" value="{{ $category['category_name'] ?? '' }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Category Image</label>
                                                        <input type="file" class="form-control" name="category_image" accept="image/jpeg,image/jpg,image/png,image/gif">
                                                        <small class="text-muted">Upload a new image (max 10MB) or use URL below</small>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Category Image URL</label>
                                                        <input type="text" class="form-control" name="category_img" value="{{ $category['category_img'] ?? $category['cat_img'] ?? '' }}" placeholder="Or enter image URL" maxlength="2000">
                                                        @php
                                                            $previewImageUrl = $category['category_img'] ?? $category['cat_img'] ?? '';
                                                            // Validate URL - allow all valid HTTP(S) URLs
                                                            if ($previewImageUrl) {
                                                                $isHttpUrl = strpos($previewImageUrl, 'http://') === 0 || strpos($previewImageUrl, 'https://') === 0;
                                                                $hasMinLength = strlen($previewImageUrl) >= 10;
                                                                // Only clear obviously invalid URLs
                                                                if (!$isHttpUrl || !$hasMinLength) {
                                                                    $previewImageUrl = '';
                                                                }
                                                            }
                                                        @endphp
                                                        @if($previewImageUrl)
                                                            <small class="text-muted d-block mt-2">Current image:</small>
                                                            @php
                                                                // Don't add cache-busting to presigned S3 URLs - it breaks the signature!
                                                                $isPresignedS3 = strpos($previewImageUrl, 'X-Amz-Signature') !== false || 
                                                                                strpos($previewImageUrl, 'X-Amz-Algorithm') !== false;
                                                                if (!$isPresignedS3) {
                                                                    $separator = strpos($previewImageUrl, '?') !== false ? '&' : '?';
                                                                    $previewImage = $previewImageUrl . $separator . 'v=' . time();
                                                                } else {
                                                                    // Use presigned URL as-is - signature depends on exact query parameters
                                                                    $previewImage = $previewImageUrl;
                                                                }
                                                            @endphp
                                                            <img src="{{ $previewImage }}" alt="Category Image" class="img-thumbnail mt-2" style="max-width: 200px; max-height: 200px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                            <div style="display: none; color: red; font-size: 12px; margin-top: 5px;">
                                                                <i class="fa fa-exclamation-triangle"></i> Image failed to load. Please upload a new image.
                                                            </div>
                                                        @else
                                                            <small class="text-muted d-block mt-2">No image URL set.</small>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Update</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Add Subcategory Modal -->
                                <div class="modal fade" id="addSubcategoryModal{{ $category['id'] }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Add Subcategory to {{ $category['category_name'] ?? 'Category' }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="{{ route('createSubcategory') }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <input type="hidden" name="main_category_id" value="{{ $category['id'] }}">
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Subcategory Name <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="subcategory_name" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Subcategory Image</label>
                                                        <input type="file" class="form-control" name="subcategory_image" accept="image/jpeg,image/jpg,image/png,image/gif">
                                                        <small class="text-muted">Upload an image (max 10MB) or use URL below</small>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Subcategory Image URL</label>
                                                        <input type="text" class="form-control" name="subcategory_img" placeholder="Or enter image URL" maxlength="2000">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Default Price</label>
                                                        <input type="text" class="form-control" name="default_price" value="0">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Price Unit</label>
                                                        <select class="form-select" name="price_unit">
                                                            <option value="kg" selected>kg</option>
                                                            <option value="pcs">pcs</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-success">Create</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @else
                            <div class="alert alert-warning">
                                <strong>No Categories Found!</strong><br>
                                Please check if the Node.js API is running and accessible.
                                @if(isset($error))
                                    <br><small>Error: {{ $error }}</small>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('categories.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category Image</label>
                        <input type="file" class="form-control" name="category_image" accept="image/jpeg,image/jpg,image/png,image/gif">
                        <small class="text-muted">Upload a new image (max 10MB) or use URL below</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category Image URL</label>
                        <input type="text" class="form-control" name="category_img" placeholder="Or enter image URL" maxlength="2000">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
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
// Handle page after image upload - no reload needed, page loads once via PHP redirect
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
            // Don't open if it's the placeholder "No Image" div (which isn't an img tag, but checking just in case)
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

    // Add cursor pointer to all thumbnails
    const style = document.createElement('style');
    style.textContent = '.img-thumbnail { cursor: pointer; transition: transform 0.2s; } .img-thumbnail:hover { transform: scale(1.05); }';
    document.head.appendChild(style);

    const urlParams = new URLSearchParams(window.location.search);
    
    // If we're coming back from an upload, replace old image with NEW URL from session
    if (urlParams.get('uploaded') === '1') {
        const uploadedCategoryId = urlParams.get('category_id');
        
        // Get the NEW image URL from PHP session (passed from upload response)
        @php
            $newImageUrlFromSession = session('new_image_url');
            $updatedCategoryIdFromSession = session('updated_category_id');
        @endphp
        
        const newImageUrl = @json($newImageUrlFromSession ?? null);
        const updatedCategoryId = @json($updatedCategoryIdFromSession ?? null);
        
        // Check if the new URL is actually an S3 URL (new upload) or just the old URL
        const isS3Url = newImageUrl && (
            newImageUrl.includes('scrapmate-images.s3') || 
            newImageUrl.includes('s3.amazonaws.com')
        );
        
        console.log('✅ [ADMIN PANEL] Image uploaded successfully - checking for NEW S3 URL', {
            'uploaded_category_id': uploadedCategoryId,
            'updated_category_id_from_session': updatedCategoryId,
            'has_new_image_url': !!newImageUrl,
            'new_image_url_preview': newImageUrl ? newImageUrl.substring(0, 100) + '...' : 'N/A',
            'is_s3_url': isS3Url,
            'is_old_external_url': newImageUrl && !isS3Url && newImageUrl.includes('app.scrapmate.co.in')
        });
        
        // Only use the NEW URL if it's actually an S3 URL (new upload)
        // If it's the old external URL, don't use it - database hasn't updated yet
        if (newImageUrl && isS3Url && (uploadedCategoryId || updatedCategoryId)) {
            const targetCategoryId = uploadedCategoryId || updatedCategoryId;
            
            console.log('🔄 [IMAGE UPDATE] Updating image with new URL from upload...', {
                'targetCategoryId': targetCategoryId,
                'newImageUrl': newImageUrl.substring(0, 100) + '...',
                'isS3Url': isS3Url
            });
            
            // Wait for page to fully load and images to be rendered
            setTimeout(function() {
                const targetImage = document.querySelector(`img.category-image[data-category-id="${targetCategoryId}"]`);
                console.log('🔍 Looking for image element:', `img.category-image[data-category-id="${targetCategoryId}"]`);
                
                if (targetImage) {
                    console.log('✅ Found target image element, current src:', targetImage.src.substring(0, 100) + '...');
                    
                    // Check if URL is a presigned S3 URL - DON'T add cache-busting if it is!
                    // Presigned URLs have a signature that depends on exact query parameters
                    const isPresignedUrl = newImageUrl.includes('X-Amz-Signature') || newImageUrl.includes('X-Amz-Algorithm');
                    
                    let updatedSrc;
                    if (isPresignedUrl) {
                        // Use presigned URL as-is - adding parameters breaks AWS signature validation
                        updatedSrc = newImageUrl;
                        console.log('   📸 Using presigned S3 URL (no cache-busting)');
                    } else {
                        // Add cache-busting to non-presigned URLs
                        const separator = newImageUrl.includes('?') ? '&' : '?';
                        updatedSrc = newImageUrl + separator + 'v=' + Date.now() + '&nocache=' + Math.random();
                    }
                    
                    // Update image with new URL - force reload
                    targetImage.style.opacity = '0';
                    targetImage.onload = function() {
                        console.log('   ✅ Image loaded successfully');
                        targetImage.style.transition = 'opacity 0.3s';
                        targetImage.style.opacity = '1';
                    };
                    targetImage.onerror = function() {
                        console.error('   ❌ Error loading image:', updatedSrc.substring(0, 100) + '...');
                        targetImage.style.opacity = '1';
                    };
                    targetImage.src = updatedSrc;
                    
                    console.log('   ✅ Image src updated for category ID:', targetCategoryId);
                    console.log('   📝 New src:', updatedSrc.substring(0, 100) + '...');
                } else {
                    console.error('   ❌ Target image not found for category ID:', targetCategoryId);
                    console.log('   🔍 Available images:', document.querySelectorAll('img.category-image').length);
                    document.querySelectorAll('img.category-image').forEach(function(img) {
                        console.log('   - Image category ID:', img.getAttribute('data-category-id'), 'src:', img.src.substring(0, 80) + '...');
                    });
                }
            }, 1000); // Longer delay to ensure page is fully loaded
        } else {
            console.warn('   ⚠️ NEW image URL not available in session', {
                'newImageUrl': newImageUrl,
                'uploadedCategoryId': uploadedCategoryId,
                'updatedCategoryId': updatedCategoryId
            });
        }
        
        // Clean up URL by removing query parameters after images reload
        setTimeout(function() {
            const cleanUrl = window.location.pathname;
            window.history.replaceState({}, document.title, cleanUrl);
        }, 4000);
    }
    
    // Handle form submissions
    const forms = document.querySelectorAll('form[action*="updateCategory"], form[action*="updateSubcategory"], form[action*="createSubcategory"]');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const formAction = form.getAttribute('action');
            const formData = new FormData(form);
            
            // Extract all form data for logging
            const formDataObj = {};
            for (let [key, value] of formData.entries()) {
                if (value instanceof File) {
                    formDataObj[key] = {
                        type: 'File',
                        name: value.name,
                        size: value.size,
                        type_mime: value.type,
                        lastModified: new Date(value.lastModified).toISOString()
                    };
                } else {
                    formDataObj[key] = value;
                }
            }
            
            console.log('📤 [ADMIN PANEL] Form Submit - PUT Image Upload', {
                'form_action': formAction,
                'form_method': form.method,
                'form_data': formDataObj,
                'has_file': formData.has('category_image') || formData.has('subcategory_image'),
                'file_info': (function() {
                    const fileInput = form.querySelector('input[type="file"]');
                    if (fileInput && fileInput.files && fileInput.files.length > 0) {
                        const file = fileInput.files[0];
                        return {
                            name: file.name,
                            size: file.size,
                            type: file.type,
                            lastModified: new Date(file.lastModified).toISOString()
                        };
                    }
                    return null;
                })(),
                'category_name': formData.get('category_name') || formData.get('subcategory_name') || 'N/A',
                'image_url': formData.get('category_img') || formData.get('subcategory_img') || 'N/A'
            });
            
            console.log('📦 [ADMIN PANEL] Full FormData dump:', formData);
            
            // Show loading indicator
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Uploading...';
                
                // Reset after 30 seconds in case of error
                setTimeout(function() {
                    if (submitBtn.disabled) {
                        console.log('   ⚠️ Form submission timeout - resetting button');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                }, 30000);
            }
        });
    });
    
    // Retry loading images if they fail - only for error handling
    // IMPORTANT: Don't retry presigned S3 URLs by modifying them - it breaks the signature!
    document.querySelectorAll('img').forEach(function(img) {
        let retryCount = 0;
        img.addEventListener('error', function() {
            const currentSrc = this.src;
            const isPresignedUrl = currentSrc.includes('X-Amz-Signature') || currentSrc.includes('X-Amz-Algorithm');
            
            // Don't retry presigned URLs by modifying them - the signature will break
            if (isPresignedUrl) {
                console.warn('   ⚠️ Presigned S3 URL failed to load - cannot retry by modifying URL (signature would break)');
                return;
            }
            
            if (retryCount < 2 && this.src) {
                retryCount++;
                const baseUrl = this.src.split('?')[0];
                this.src = baseUrl + '?v=' + Date.now();
            }
        });
    });
});
</script>

@endsection
