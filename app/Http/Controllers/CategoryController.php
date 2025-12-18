<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NodeApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    protected $nodeApi;

    public function __construct(NodeApiService $nodeApi)
    {
        $this->nodeApi = $nodeApi;
    }

    /**
     * Display categories and subcategories list
     */
    public function index(Request $request)
    {
        try {
            // Clear cache if requested (for testing)
            if ($request->has('clear_cache')) {
                $this->nodeApi->clearCache('/category_img_list');
            }
            
            // Fetch main categories - add bypass_cache parameter to force fresh data from Node.js
            // Always add timestamp to prevent caching
            $timestamp = time();
            
            // Force cache bypass if clear_cache or uploaded parameter is present
            $forceRefresh = $request->has('clear_cache') || $request->has('uploaded');
            
            $cacheParams = $forceRefresh
                ? ['bypass_cache' => '1', 't' => $timestamp, 'nocache' => '1'] 
                : ['t' => $timestamp];
            
            $categoriesResponse = $this->nodeApi->get("/category_img_list", $cacheParams, 30);
            $categories = [];
            
            Log::info('Categories API Response', ['response' => $categoriesResponse]);
            
            if (isset($categoriesResponse['status']) && $categoriesResponse['status'] === 'success') {
                $categories = $categoriesResponse['data'] ?? [];
                
                // DEBUG: Log category 7 specifically to see what URL is returned
                foreach ($categories as $cat) {
                    if (isset($cat['id']) && $cat['id'] == 7) {
                        Log::info('🔍 [DEBUG] Category 7 from API Response', [
                            'category_name' => $cat['category_name'] ?? 'N/A',
                            'category_img' => isset($cat['category_img']) ? substr($cat['category_img'], 0, 150) . '...' : 'NOT SET',
                            'cat_img' => isset($cat['cat_img']) ? substr($cat['cat_img'], 0, 150) . '...' : 'NOT SET',
                            'category_img_length' => isset($cat['category_img']) ? strlen($cat['category_img']) : 0,
                            'cat_img_length' => isset($cat['cat_img']) ? strlen($cat['cat_img']) : 0,
                            'has_presigned_url' => (isset($cat['category_img']) && strpos($cat['category_img'], 'X-Amz-Signature') !== false) || 
                                                  (isset($cat['cat_img']) && strpos($cat['cat_img'], 'X-Amz-Signature') !== false)
                        ]);
                        break;
                    }
                }
            } else {
                Log::warning('Categories API returned error', ['response' => $categoriesResponse]);
            }

            // Fetch subcategories grouped by main category
            $subcategoriesResponse = $this->nodeApi->get('/subcategories/grouped', [], 30);
            $subcategoriesGrouped = [];
            
            Log::info('Subcategories API Response', ['response' => $subcategoriesResponse]);
            
            if (isset($subcategoriesResponse['status']) && $subcategoriesResponse['status'] === 'success') {
                $subcategoriesGrouped = $subcategoriesResponse['data'] ?? [];
            } else {
                Log::warning('Subcategories API returned error', ['response' => $subcategoriesResponse]);
            }

            // Create a map for quick lookup
            $subcategoriesMap = [];
            foreach ($subcategoriesGrouped as $group) {
                if (isset($group['main_category']['id'])) {
                    $subcategoriesMap[$group['main_category']['id']] = $group['subcategories'] ?? [];
                }
            }

            Log::info('Categories data prepared', [
                'categories_count' => count($categories),
                'subcategories_groups_count' => count($subcategoriesGrouped),
                'subcategories_map_keys' => array_keys($subcategoriesMap)
            ]);

            $data = [
                'pagename' => 'Categories & Subcategories',
                'categories' => $categories,
                'subcategoriesGrouped' => $subcategoriesGrouped,
                'subcategoriesMap' => $subcategoriesMap
            ];

            return view('admin/categories', $data);
        } catch (\Exception $e) {
            Log::error('Error fetching categories: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $data = [
                'pagename' => 'Categories & Subcategories',
                'categories' => [],
                'subcategoriesGrouped' => [],
                'subcategoriesMap' => [],
                'error' => 'Failed to load categories: ' . $e->getMessage()
            ];
            return view('admin/categories', $data);
        }
    }

    /**
     * Update main category
     */
    public function updateCategory(Request $request, $id)
    {
        try {
            // Log all incoming request data for debugging
            Log::info('📥 [ADMIN PANEL] Update Category Request Received', [
                'category_id' => $id,
                'request_method' => $request->method(),
                'all_inputs' => $request->all(),
                'has_file_category_image' => $request->hasFile('category_image'),
                'has_category_img' => $request->has('category_img'),
                'category_name' => $request->input('category_name'),
                'category_img_url' => $request->input('category_img'),
                'files' => $_FILES // Log raw files array to check for errors
            ]);

            // Check for file upload errors
            if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] !== UPLOAD_ERR_OK) {
                $uploadError = $_FILES['category_image']['error'];
                
                // Get current PHP upload limits for better error messages
                $uploadMaxFilesize = ini_get('upload_max_filesize');
                $postMaxSize = ini_get('post_max_size');
                
                $errorMessage = 'File upload failed: ';
                switch ($uploadError) {
                    case UPLOAD_ERR_INI_SIZE:
                        $errorMessage .= "File exceeds PHP upload limit ({$uploadMaxFilesize}). ";
                        $errorMessage .= "Note: Images will be automatically compressed to 50KB after upload, but the initial upload must be allowed. ";
                        $errorMessage .= "Please increase 'upload_max_filesize' in php.ini (currently: {$uploadMaxFilesize}) to at least 64M.";
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        $errorMessage .= "File exceeds form upload limit. ";
                        $errorMessage .= "Please increase 'post_max_size' in php.ini (currently: {$postMaxSize}) to at least 64M.";
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $errorMessage .= 'The uploaded file was only partially uploaded';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $errorMessage .= 'No file was uploaded';
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $errorMessage .= 'Missing a temporary folder';
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $errorMessage .= 'Failed to write file to disk';
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $errorMessage .= 'A PHP extension stopped the file upload';
                        break;
                    default:
                        $errorMessage .= 'Unknown error code: ' . $uploadError;
                        break;
                }
                
                if ($uploadError !== UPLOAD_ERR_NO_FILE) {
                    Log::error('❌ [ADMIN PANEL] File Upload Error', [
                        'error_code' => $uploadError,
                        'message' => $errorMessage
                    ]);
                    return redirect()->back()->with('error', $errorMessage);
                }
            }
            
            // Custom validation: only validate image if a file is actually uploaded
            // This handles cases where an empty UploadedFile object might be sent
            // Note: category_img max length increased to 2000 to accommodate presigned S3 URLs
            $validationRules = [
                'category_name' => 'required|string|max:255',
                'category_img' => 'nullable|string|max:2000', // Increased from 500 to accommodate presigned S3 URLs
            ];
            
            // Only add image validation if a file is actually being uploaded
            if ($request->hasFile('category_image') && $request->file('category_image')->isValid()) {
                $validationRules['category_image'] = 'required|image|mimes:jpeg,jpg,png,gif|max:10240'; // 10MB max
            } else {
                // If category_image is present but invalid/empty, allow it to be nullable
                $validationRules['category_image'] = 'nullable';
            }
            
            $request->validate($validationRules);

            $data = [
                'category_name' => $request->input('category_name'),
            ];

            // Handle file upload
            $fileField = null;
            $filePath = null;
            
            if ($request->hasFile('category_image')) {
                $file = $request->file('category_image');
                $filePath = $file->getRealPath();
                $fileField = 'category_image';
                $fileSize = $file->getSize();
                $originalFileName = $file->getClientOriginalName();
                
                Log::info('📤 [ADMIN PANEL] File upload detected', [
                    'category_id' => $id,
                    'original_file_name' => $originalFileName,
                    'temp_file_path' => $filePath,
                    'file_size' => $fileSize,
                    'file_size_mb' => round($fileSize / 1024 / 1024, 2),
                    'mime_type' => $file->getMimeType()
                ]);
                
                // Validate minimum file size - reject suspiciously small files
                $MIN_FILE_SIZE = 100; // Minimum 100 bytes (1x1 pixel PNGs are ~70 bytes)
                if ($fileSize < $MIN_FILE_SIZE) {
                    Log::error('❌ [ADMIN PANEL] File too small - rejecting upload', [
                        'category_id' => $id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_size' => $fileSize,
                        'minimum_required' => $MIN_FILE_SIZE,
                        'error' => 'File is too small (likely corrupted or placeholder)'
                    ]);
                    
                    return redirect()->route('categories', ['clear_cache' => 1])
                        ->with('error', "File is too small ({$fileSize} bytes). Minimum required: {$MIN_FILE_SIZE} bytes. The file might be corrupted or a placeholder image.");
                }
                
                // Validate image file header to ensure it's a valid image
                $fileContent = file_get_contents($filePath);
                $firstBytes = substr($fileContent, 0, 4);
                $isValidImage = false;
                $detectedFormat = null;
                
                // Check for PNG signature
                if ($firstBytes === "\x89\x50\x4E\x47") {
                    $isValidImage = true;
                    $detectedFormat = 'PNG';
                }
                // Check for JPEG signature
                elseif (substr($firstBytes, 0, 3) === "\xFF\xD8\xFF") {
                    $isValidImage = true;
                    $detectedFormat = 'JPEG';
                }
                // Check for GIF signature
                elseif (substr($firstBytes, 0, 3) === "GIF") {
                    $isValidImage = true;
                    $detectedFormat = 'GIF';
                }
                // Check for WebP signature (RIFF)
                elseif (substr($firstBytes, 0, 4) === "RIFF") {
                    $isValidImage = true;
                    $detectedFormat = 'WebP';
                }
                
                if (!$isValidImage) {
                    $hexBytes = bin2hex($firstBytes);
                    Log::error('❌ [ADMIN PANEL] Invalid image file - rejecting upload', [
                        'category_id' => $id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_size' => $fileSize,
                        'first_bytes_hex' => $hexBytes,
                        'error' => 'File does not appear to be a valid image (PNG, JPEG, GIF, or WebP)'
                    ]);
                    
                    return redirect()->route('categories', ['clear_cache' => 1])
                        ->with('error', 'Invalid image file. Please upload a valid PNG, JPEG, GIF, or WebP image.');
                }
                
                // Compress image to 50KB before upload
                $originalSizeBeforeCompression = $fileSize;
                $filePath = \App\Helpers\ImageCompressor::compressImage($filePath, $originalFileName);
                $fileSizeAfterCompression = filesize($filePath);
                $compressionRatio = $originalSizeBeforeCompression > 0 
                    ? round((1 - $fileSizeAfterCompression / $originalSizeBeforeCompression) * 100, 1) 
                    : 0;
                
                Log::info('📎 [ADMIN PANEL] File Upload Detected, Validated & Compressed', [
                    'category_id' => $id,
                    'file_name' => $file->getClientOriginalName(),
                    'original_size_bytes' => $originalSizeBeforeCompression,
                    'original_size_kb' => round($originalSizeBeforeCompression / 1024, 2),
                    'compressed_size_bytes' => $fileSizeAfterCompression,
                    'compressed_size_kb' => round($fileSizeAfterCompression / 1024, 2),
                    'compression_ratio' => "{$compressionRatio}%",
                    'target_50kb_met' => $fileSizeAfterCompression <= (50 * 1024),
                    'file_mime' => $file->getMimeType(),
                    'image_format' => $detectedFormat,
                    'file_path' => $filePath,
                    'file_exists' => file_exists($filePath),
                    'validation_passed' => true
                ]);
            } elseif ($request->has('category_img')) {
                // If no file upload but URL provided, use the URL
                $data['category_img'] = $request->input('category_img');
                
                Log::info('🔗 [ADMIN PANEL] Image URL Provided (No File Upload)', [
                    'category_id' => $id,
                    'image_url' => $request->input('category_img'),
                    'url_length' => strlen($request->input('category_img'))
                ]);
            }
            
            Log::info('📦 [ADMIN PANEL] Prepared Data for Node.js API', [
                'category_id' => $id,
                'data' => $data,
                'file_field' => $fileField,
                'has_file' => !empty($fileField)
            ]);

            // Use multipart if file is uploaded, otherwise use regular PUT
            if ($fileField && $filePath) {
                $originalFileName = isset($file) ? $file->getClientOriginalName() : basename($filePath);
                
                Log::info('🚀 [ADMIN PANEL] Calling Node.js API - POST Multipart with File (using POST for better multipart support)', [
                    'category_id' => $id,
                    'endpoint' => "/category_img_keywords/{$id}",
                    'file_field' => $fileField,
                    'file_path' => $filePath,
                    'original_file_name' => $originalFileName,
                    'file_size' => filesize($filePath),
                    'file_size_mb' => round(filesize($filePath) / 1024 / 1024, 2),
                    'data' => $data,
                    'data_keys' => array_keys($data)
                ]);
                // Use POST for multipart uploads as PUT can be problematic with some multipart parsers
                $response = $this->nodeApi->postMultipart("/category_img_keywords/{$id}", $data, $fileField, $filePath, $originalFileName);
            } else {
                Log::info('🚀 [ADMIN PANEL] Calling Node.js API - PUT without File', [
                    'category_id' => $id,
                    'endpoint' => "/category_img_keywords/{$id}",
                    'data' => $data,
                    'data_keys' => array_keys($data),
                    'has_image_url' => isset($data['category_img'])
                ]);
                $response = $this->nodeApi->put("/category_img_keywords/{$id}", $data);
            }

            Log::info('📥 [ADMIN PANEL] Node.js API Response Received', [
                'category_id' => $id,
                'response_status' => $response['status'] ?? 'unknown',
                'response_message' => $response['msg'] ?? 'N/A',
                'has_data' => isset($response['data']),
                'response_data_keys' => isset($response['data']) ? array_keys($response['data']) : [],
                'response_data' => $response['data'] ?? null,
                'category_img_in_response' => isset($response['data']['category_img']) ? substr($response['data']['category_img'], 0, 100) . '...' : 'N/A',
                'cat_img_in_response' => isset($response['data']['cat_img']) ? substr($response['data']['cat_img'], 0, 100) . '...' : 'N/A',
                'full_response' => $response
            ]);

            if (isset($response['status']) && $response['status'] === 'success') {
                // Get the new image URL from response first
                $newImageUrl = isset($response['data']['category_img']) 
                    ? $response['data']['category_img'] 
                    : (isset($response['data']['cat_img']) ? $response['data']['cat_img'] : null);
                
                // IMPORTANT: Only use new URL if it's actually an S3 URL (new upload)
                // If file was uploaded, the URL should be an S3 URL, not the old external URL
                if ($fileField && $newImageUrl) {
                    $isS3Url = strpos($newImageUrl, 'scrapmate-images.s3') !== false || 
                               strpos($newImageUrl, 's3.amazonaws.com') !== false;
                    $isOldExternalUrl = strpos($newImageUrl, 'app.scrapmate.co.in') !== false;
                    
                    Log::info('🔍 [URL VALIDATION] Checking if response URL is valid S3 URL', [
                        'category_id' => $id,
                        'file_uploaded' => true,
                        'returned_url_preview' => substr($newImageUrl, 0, 100) . '...',
                        'full_returned_url' => $newImageUrl, // Log full URL for debugging
                        'is_s3_url' => $isS3Url,
                        'is_old_external_url' => $isOldExternalUrl,
                        'url_length' => strlen($newImageUrl)
                    ]);
                    
                    if (!$isS3Url) {
                        Log::error('❌ [ERROR] Uploaded file but response contains OLD EXTERNAL URL - NOT storing in session!', [
                            'category_id' => $id,
                            'returned_url' => $newImageUrl,
                            'is_s3_url' => false,
                            'url_type' => 'old_external_url',
                            'action' => 'NOT_STORING_IN_SESSION'
                        ]);
                        // Don't store old URL - wait for database to update
                        $newImageUrl = null;
                    } else {
                        Log::info('✅ [S3 URL CONFIRMED] New S3 URL is valid - will store in session', [
                            'category_id' => $id,
                            's3_url_preview' => substr($newImageUrl, 0, 100) . '...',
                            'url_length' => strlen($newImageUrl)
                        ]);
                    }
                }
                
                Log::info('✅ [SUCCESS] Category update successful - starting cache clearing...', [
                    'category_id' => $id,
                    'image_uploaded' => $fileField !== null,
                    'has_file_field' => !empty($fileField),
                    'file_field' => $fileField,
                    'new_image_url_exists' => !empty($newImageUrl),
                    'new_image_url_is_s3' => $newImageUrl ? (strpos($newImageUrl, 's3') !== false) : false,
                    'new_image_url_length' => $newImageUrl ? strlen($newImageUrl) : 0,
                    'new_image_url_preview' => $newImageUrl ? substr($newImageUrl, 0, 100) . '...' : 'N/A',
                    'will_store_in_session' => !empty($newImageUrl) && ($fileField === null || strpos($newImageUrl, 's3') !== false)
                ]);
                
                // Clear cache to ensure updated image is shown
                Log::info('🧹 [CACHE CLEAR] Clearing all caches...');
                
                // Clear Node.js Redis cache
                $this->nodeApi->clearCache('/category_img_list');
                $this->nodeApi->clearCache('/subcategories/grouped');
                
                // Clear PHP cache
                Cache::flush();
                
                // Wait longer to ensure DynamoDB update is fully propagated
                // DynamoDB eventual consistency can take a moment, especially after cache clear
                Log::info('⏳ [WAITING] Waiting for DynamoDB eventual consistency...');
                usleep(2500000); // 2.5 seconds - longer wait for DynamoDB propagation
                
                Log::info('✅ [CACHE CLEARED] All caches cleared and database ready after 2.5s wait');
                
                Log::info('Category updated successfully', [
                    'category_id' => $id,
                    'image_uploaded' => $fileField !== null,
                    'new_image_url' => $newImageUrl ? substr($newImageUrl, 0, 100) . '...' : 'N/A',
                    'new_image_url_length' => $newImageUrl ? strlen($newImageUrl) : 0,
                    'response_has_data' => isset($response['data']),
                    'response_data_keys' => isset($response['data']) ? array_keys($response['data']) : []
                ]);
                
                // Simple redirect after upload - page will load once with fresh data
                // Include category ID and NEW image URL to use directly (bypass database)
                $redirectUrl = route('categories', [
                    'clear_cache' => '1', 
                    'uploaded' => '1',
                    'category_id' => $id
                ]);
                
                Log::info('🔄 [REDIRECT] Redirecting to categories page', [
                    'url' => $redirectUrl,
                    'category_id' => $id,
                    'has_new_image' => !empty($newImageUrl),
                    'new_image_url' => $newImageUrl ? substr($newImageUrl, 0, 100) . '...' : 'N/A'
                ]);
                
                // Store new image URL in session so JavaScript can use it directly
                // IMPORTANT: Only store S3 URLs when a file was uploaded
                // For URL-only updates (no file), don't store - use the URL from database instead
                $shouldStoreInSession = false;
                if (!empty($newImageUrl)) {
                    if ($fileField !== null) {
                        // File was uploaded - only store if it's an S3 URL
                        $isS3Url = strpos($newImageUrl, 'scrapmate-images.s3') !== false || 
                                   strpos($newImageUrl, 's3.amazonaws.com') !== false;
                        if ($isS3Url) {
                            $shouldStoreInSession = true;
                            Log::info('💾 [SESSION] Storing S3 URL in session (file uploaded)', [
                                'category_id' => $id,
                                'new_image_url_preview' => substr($newImageUrl, 0, 100) . '...',
                                'is_s3_url' => true
                            ]);
                        } else {
                            Log::warning('⚠️ [SESSION] File uploaded but URL is NOT S3 - NOT storing in session', [
                                'category_id' => $id,
                                'url_preview' => substr($newImageUrl, 0, 100) . '...',
                                'reason' => 'API returned old external URL instead of new S3 URL'
                            ]);
                            $newImageUrl = null; // Don't store old URL
                        }
                    } else {
                        // No file uploaded (URL-only update) - don't store in session
                        // Let the page load fresh data from API instead
                        Log::info('💾 [SESSION] URL-only update (no file) - NOT storing in session', [
                            'category_id' => $id,
                            'reason' => 'Page will load fresh data from API'
                        ]);
                        $newImageUrl = null; // Don't store for URL-only updates
                    }
                } else {
                    Log::warning('⚠️ [SESSION] Not storing image URL - URL is empty', [
                        'category_id' => $id,
                        'file_uploaded' => $fileField !== null
                    ]);
                }
                
                return redirect($redirectUrl)
                    ->with('success', 'Category updated successfully!')
                    ->with('new_image_url', $newImageUrl) // Will be null if invalid
                    ->with('updated_category_id', $id);
            } else {
                // Extract error message from response
                $errorMsg = $response['msg'] ?? 'Unknown error occurred';
                
                // Check if it's a file upload specific error
                $isUploadError = strpos(strtolower($errorMsg), 'upload') !== false || 
                                 strpos(strtolower($errorMsg), 'image') !== false ||
                                 strpos(strtolower($errorMsg), 'file') !== false;
                
                Log::error('❌ [ADMIN PANEL] Category update failed', [
                    'category_id' => $id,
                    'response_status' => $response['status'] ?? 'unknown',
                    'response_msg' => $errorMsg,
                    'is_upload_error' => $isUploadError,
                    'file_uploaded' => !empty($fileField),
                    'full_response' => $response
                ]);
                
                // Provide more specific error message - but don't duplicate if already contains "upload" or "failed"
                if ($fileField && $isUploadError) {
                    // Only add prefix if error message doesn't already contain upload-related text
                    if (stripos($errorMsg, 'upload') === false && stripos($errorMsg, 'failed') === false) {
                        $errorMsg = "Image upload failed: {$errorMsg}";
                    }
                }
                
                return redirect()->route('categories', ['clear_cache' => 1])
                    ->with('error', $errorMsg);
            }
        } catch (\Exception $e) {
            Log::error('❌ [ADMIN PANEL] Exception updating category', [
                'category_id' => $id ?? 'unknown',
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'has_file' => isset($fileField) && !empty($fileField)
            ]);
            
            // Provide more specific error message based on exception
            // But avoid duplicating if the message already contains the prefix
            $errorMsg = $e->getMessage();
            $errorMsgLower = strtolower($errorMsg);
            
            // Check if message already contains upload/error prefixes
            $alreadyHasUploadError = stripos($errorMsg, 'upload') !== false && 
                                     (stripos($errorMsg, 'failed') !== false || stripos($errorMsg, 'error') !== false);
            $alreadyHasCategoryError = stripos($errorMsg, 'category') !== false && 
                                       (stripos($errorMsg, 'failed') !== false || stripos($errorMsg, 'error') !== false);
            
            if (!$alreadyHasUploadError && (strpos($errorMsgLower, 'upload') !== false || strpos($errorMsgLower, 'image') !== false)) {
                $errorMsg = "The category image failed to upload: {$errorMsg}";
            } elseif (!$alreadyHasCategoryError) {
                $errorMsg = "An error occurred while updating the category: {$errorMsg}";
            }
            
            return redirect()->route('categories', ['clear_cache' => 1])
                ->with('error', $errorMsg);
        }
    }

    /**
     * Update subcategory
     */
    public function updateSubcategory(Request $request, $id)
    {
        try {
            $request->validate([
                'subcategory_name' => 'required|string|max:255',
                'default_price' => 'nullable|string|max:50',
                'price_unit' => 'nullable|string|in:kg,pcs',
                'subcategory_img' => 'nullable|string|max:2000', // Increased from 500 to accommodate presigned S3 URLs
                'subcategory_image' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:10240' // 10MB max
            ]);

            $data = [
                'subcategory_name' => $request->input('subcategory_name'),
            ];

            if ($request->has('default_price')) {
                $data['default_price'] = $request->input('default_price');
            }

            if ($request->has('price_unit')) {
                $data['price_unit'] = $request->input('price_unit');
            }

            // Handle file upload
            $fileField = null;
            $filePath = null;
            
            if ($request->hasFile('subcategory_image')) {
                $file = $request->file('subcategory_image');
                $filePath = $file->getRealPath();
                $fileField = 'subcategory_image';
                $originalFileName = $file->getClientOriginalName();
                
                // Compress image to 50KB before upload
                try {
                    $filePath = \App\Helpers\ImageCompressor::compressImage($filePath, $originalFileName);
                    Log::info('✅ Subcategory image compressed successfully', ['path' => $filePath]);
                } catch (\Exception $e) {
                    Log::error('❌ Failed to compress subcategory image', ['error' => $e->getMessage()]);
                    // Continue with original file if compression fails
                }
            } elseif ($request->has('subcategory_img')) {
                // If no file upload but URL provided, use the URL
                $data['subcategory_img'] = $request->input('subcategory_img');
            }

            // Use multipart if file is uploaded, otherwise use regular PUT
            if ($fileField && $filePath) {
                $originalFileName = isset($file) ? $file->getClientOriginalName() : basename($filePath);
                $response = $this->nodeApi->postMultipart("/subcategories/{$id}", $data, $fileField, $filePath, $originalFileName);
            } else {
                $response = $this->nodeApi->put("/subcategories/{$id}", $data);
            }

            if (isset($response['status']) && $response['status'] === 'success') {
                // Clear cache to ensure updated image is shown
                $this->nodeApi->clearCache('/category_img_list');
                $this->nodeApi->clearCache('/subcategories/grouped');
                
                return redirect()->route('categories')->with('success', 'Subcategory updated successfully!');
            } else {
                return redirect()->route('categories')->with('error', $response['msg'] ?? 'Failed to update subcategory.');
            }
        } catch (\Exception $e) {
            Log::error('Error updating subcategory: ' . $e->getMessage());
            return redirect()->route('categories')->with('error', 'An error occurred while updating the subcategory: ' . $e->getMessage());
        }
    }

    /**
     * Create new subcategory
     */
    public function createSubcategory(Request $request)
    {
        try {
            $request->validate([
                'main_category_id' => 'required|integer',
                'subcategory_name' => 'required|string|max:255',
                'default_price' => 'nullable|string|max:50',
                'price_unit' => 'nullable|string|in:kg,pcs',
                'subcategory_img' => 'nullable|string|max:500',
                'subcategory_image' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:10240' // 10MB max
            ]);

            $data = [
                'main_category_id' => $request->input('main_category_id'),
                'subcategory_name' => $request->input('subcategory_name'),
                'default_price' => $request->input('default_price', '0'),
                'price_unit' => $request->input('price_unit', 'kg')
            ];

            // Handle file upload
            $fileField = null;
            $filePath = null;
            
            if ($request->hasFile('subcategory_image')) {
                $file = $request->file('subcategory_image');
                $filePath = $file->getRealPath();
                $fileField = 'subcategory_image';
                $originalFileName = $file->getClientOriginalName();
                
                // Compress image to 50KB before upload
                try {
                    $filePath = \App\Helpers\ImageCompressor::compressImage($filePath, $originalFileName);
                    Log::info('✅ Subcategory image compressed successfully', ['path' => $filePath]);
                } catch (\Exception $e) {
                    Log::error('❌ Failed to compress subcategory image', ['error' => $e->getMessage()]);
                    // Continue with original file if compression fails
                }
            } elseif ($request->has('subcategory_img')) {
                // If no file upload but URL provided, use the URL
                $data['subcategory_img'] = $request->input('subcategory_img');
            }

            // Use multipart if file is uploaded, otherwise use regular POST
            if ($fileField && $filePath) {
                $originalFileName = isset($file) ? $file->getClientOriginalName() : basename($filePath);
                $response = $this->nodeApi->postMultipart('/subcategories', $data, $fileField, $filePath, $originalFileName);
            } else {
                $response = $this->nodeApi->post('/subcategories', $data);
            }

            if (isset($response['status']) && $response['status'] === 'success') {
                // Clear cache to ensure new subcategory is shown
                $this->nodeApi->clearCache('/category_img_list');
                $this->nodeApi->clearCache('/subcategories/grouped');
                
                return redirect()->route('categories')->with('success', 'Subcategory created successfully!');
            } else {
                return redirect()->route('categories')->with('error', $response['msg'] ?? 'Failed to create subcategory.');
            }
        } catch (\Exception $e) {
            Log::error('Error creating subcategory: ' . $e->getMessage());
            return redirect()->route('categories')->with('error', 'An error occurred while creating the subcategory: ' . $e->getMessage());
        }
    }

    /**
     * Delete subcategory
     */
    public function deleteSubcategory($id)
    {
        try {
            $response = $this->nodeApi->delete("/subcategories/{$id}");

            if (isset($response['status']) && $response['status'] === 'success') {
                return redirect()->route('categories')->with('success', 'Subcategory deleted successfully!');
            } else {
                return redirect()->route('categories')->with('error', $response['msg'] ?? 'Failed to delete subcategory.');
            }
        } catch (\Exception $e) {
            Log::error('Error deleting subcategory: ' . $e->getMessage());
            return redirect()->route('categories')->with('error', 'An error occurred while deleting the subcategory.');
        }
    }
}
