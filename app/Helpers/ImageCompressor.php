<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class ImageCompressor
{
    const TARGET_SIZE_KB = 50;
    const TARGET_SIZE_BYTES = 50 * 1024; // 50KB

    /**
     * Compress image to under 50KB
     * @param string $filePath Original file path
     * @param string $originalFileName Original filename for extension detection
     * @return string Path to compressed image file (may be same as original if already small enough)
     */
    public static function compressImage($filePath, $originalFileName = null)
    {
        $originalSize = filesize($filePath);
        
        // If already under target size, return original
        if ($originalSize <= self::TARGET_SIZE_BYTES) {
            Log::info('✅ [IMAGE COMPRESSION] Image already under 50KB', [
                'original_size_kb' => round($originalSize / 1024, 2),
                'file' => basename($filePath)
            ]);
            return $filePath;
        }

        Log::info('📦 [IMAGE COMPRESSION] Starting compression', [
            'original_size_kb' => round($originalSize / 1024, 2),
            'target_size_kb' => self::TARGET_SIZE_KB,
            'file' => basename($filePath)
        ]);

        // Check if GD extension is available
        if (!extension_loaded('gd')) {
            Log::warning('⚠️ [IMAGE COMPRESSION] GD extension not available - skipping compression');
            return $filePath;
        }

        // Detect image type
        $imageInfo = @getimagesize($filePath);
        if (!$imageInfo) {
            Log::error('❌ [IMAGE COMPRESSION] Cannot read image info', ['file' => $filePath]);
            return $filePath;
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $mimeType = $imageInfo['mime'];

        // Create image resource based on type
        $sourceImage = null;
        $imageType = null;

        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = @imagecreatefromjpeg($filePath);
                $imageType = 'jpeg';
                break;
            case 'image/png':
                $sourceImage = @imagecreatefrompng($filePath);
                $imageType = 'png';
                break;
            case 'image/gif':
                $sourceImage = @imagecreatefromgif($filePath);
                $imageType = 'gif';
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $sourceImage = @imagecreatefromwebp($filePath);
                    $imageType = 'webp';
                }
                break;
            default:
                Log::warning('⚠️ [IMAGE COMPRESSION] Unsupported image type', ['mime' => $mimeType]);
                return $filePath;
        }

        if (!$sourceImage) {
            Log::error('❌ [IMAGE COMPRESSION] Failed to create image resource', ['mime' => $mimeType]);
            return $filePath;
        }

        // Calculate optimal dimensions and quality
        $maxDimension = 1920;
        $quality = 85;
        $attempts = 0;
        $maxAttempts = 10;
        $compressedSize = $originalSize;

        // Create a temporary file for compressed version
        $tempDir = sys_get_temp_dir();
        $tempFile = tempnam($tempDir, 'compressed_img_');
        $compressedPath = null;
        
        // Convert to JPEG for better compression (unless user specifically needs PNG/GIF)
        // JPEG provides much better compression for photos
        $outputType = 'jpeg';
        $needsTransparency = ($imageType === 'png' || $imageType === 'gif') && self::hasTransparency($sourceImage);

        // If image has transparency, try to preserve it with PNG, otherwise use JPEG
        if ($needsTransparency) {
            $outputType = 'png';
            $compressedPath = $tempFile . '.png';
        } else {
            $compressedPath = $tempFile . '.jpg';
        }

        Log::info('🔄 [IMAGE COMPRESSION] Compressing image', [
            'original_dimensions' => "{$width}x{$height}",
            'original_size_kb' => round($originalSize / 1024, 2),
            'output_format' => $outputType,
            'has_transparency' => $needsTransparency
        ]);

        while ($compressedSize > self::TARGET_SIZE_BYTES && $attempts < $maxAttempts) {
            $attempts++;
            
            // Calculate new dimensions
            if ($width > $maxDimension || $height > $maxDimension) {
                $ratio = min($maxDimension / $width, $maxDimension / $height);
                $newWidth = (int)($width * $ratio);
                $newHeight = (int)($height * $ratio);
            } else {
                $newWidth = $width;
                $newHeight = $height;
            }

            // Create resized image
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency for PNG/GIF
            if ($outputType === 'png' || $needsTransparency) {
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
                imagefill($resizedImage, 0, 0, $transparent);
            } else {
                // For JPEG, use white background
                $white = imagecolorallocate($resizedImage, 255, 255, 255);
                imagefill($resizedImage, 0, 0, $white);
            }

            // Resize image with better quality
            imagecopyresampled(
                $resizedImage, $sourceImage,
                0, 0, 0, 0,
                $newWidth, $newHeight, $width, $height
            );

            // Save compressed image
            if ($outputType === 'jpeg') {
                imagejpeg($resizedImage, $compressedPath, $quality);
            } else {
                // PNG compression (0-9, where 9 is highest compression)
                $pngQuality = 9 - (int)(($quality / 100) * 9);
                imagepng($resizedImage, $compressedPath, $pngQuality);
            }

            imagedestroy($resizedImage);

            // Check compressed size
            $compressedSize = filesize($compressedPath);
            $compressedSizeKB = round($compressedSize / 1024, 2);

            Log::info("   Attempt {$attempts}: {$compressedSizeKB}KB (quality: {$quality}, size: {$newWidth}x{$newHeight})");

            // If under target, we're done
            if ($compressedSize <= self::TARGET_SIZE_BYTES) {
                Log::info("✅ [IMAGE COMPRESSION] Successfully compressed to {$compressedSizeKB}KB");
                break;
            }

            // Reduce quality for next attempt
            if ($outputType === 'jpeg') {
                $quality = max(50, $quality - 10);
            } else {
                // For PNG, reduce quality affects compression level
                $quality = max(50, $quality - 10);
            }

            // Reduce dimensions if still too large after a few attempts
            if ($attempts > 3) {
                $maxDimension = max(800, $maxDimension - 200);
            }
        }

        // Clean up source image
        imagedestroy($sourceImage);

        // Check if compressed file exists and verify its size
        if (file_exists($compressedPath)) {
            $finalCompressedSize = filesize($compressedPath);
            
            // If compressed file is smaller than original, use it
            if ($finalCompressedSize < $originalSize) {
                $compressionRatio = round((1 - $finalCompressedSize / $originalSize) * 100, 1);
                Log::info('✅ [IMAGE COMPRESSION] Compression completed', [
                    'original_size_kb' => round($originalSize / 1024, 2),
                    'compressed_size_kb' => round($finalCompressedSize / 1024, 2),
                    'compression_ratio' => "{$compressionRatio}%",
                    'target_met' => $finalCompressedSize <= self::TARGET_SIZE_BYTES
                ]);

                // Replace original file with compressed version
                if (copy($compressedPath, $filePath)) {
                    unlink($compressedPath);
                    // Also clean up the temp file base if it exists
                    if (file_exists($tempFile)) {
                        @unlink($tempFile);
                    }
                    return $filePath;
                } else {
                    Log::warning('⚠️ [IMAGE COMPRESSION] Failed to copy compressed file, using original');
                }
            } else {
                Log::warning('⚠️ [IMAGE COMPRESSION] Compression did not reduce size, using original', [
                    'original_size_kb' => round($originalSize / 1024, 2),
                    'compressed_size_kb' => round($finalCompressedSize / 1024, 2)
                ]);
            }
        } else {
            Log::warning('⚠️ [IMAGE COMPRESSION] Compressed file was not created');
        }

        // Clean up temp files
        if (file_exists($compressedPath)) {
            @unlink($compressedPath);
        }
        if (file_exists($tempFile)) {
            @unlink($tempFile);
        }

        return $filePath;
    }

    /**
     * Check if image has transparency
     */
    private static function hasTransparency($image)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Sample some pixels to check for transparency
        $sampleSize = min(10, $width, $height);
        $stepX = max(1, floor($width / $sampleSize));
        $stepY = max(1, floor($height / $sampleSize));
        
        for ($x = 0; $x < $width; $x += $stepX) {
            for ($y = 0; $y < $height; $y += $stepY) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F;
                if ($alpha > 0) {
                    return true;
                }
            }
        }
        
        return false;
    }
}




