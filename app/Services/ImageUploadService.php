<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class ImageUploadService
{
    protected array $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    protected int $maxFileSize = 5120; // 5MB in KB

    /**
     * Upload an image to the specified directory.
     * Supports UploadedFile, Base64 string, or direct URL.
     *
     * @param mixed $imageInput
     * @param string $directory (profile-images, restaurants, menu-categories, menu-items)
     * @return string|null
     * @throws Exception
     */
    public function upload($imageInput, string $directory): ?string
    {
        if (!$imageInput) {
            return null;
        }

        // 1. Handle Multipart Uploaded File
        if ($imageInput instanceof UploadedFile) {
            Log::info('Image upload detected: UploadedFile', [
                'mime' => $imageInput->getClientMimeType(),
                'size_kb' => $imageInput->getSize() / 1024,
            ]);

            // Validate
            $this->validateMimeType($imageInput->getClientMimeType());
            $this->validateFileSize($imageInput->getSize());

            $filename = Str::random(40) . '.' . $imageInput->getClientOriginalExtension();
            $path = $imageInput->storeAs($directory, $filename, 'public');

            if (!$path) {
                Log::error('Image store failed for UploadedFile');
                throw new Exception('Failed to store uploaded file.');
            }

            return $this->formatUrl($path);
        }

        // 2. Handle Base64 Image String
        if (is_string($imageInput) && (str_starts_with($imageInput, 'data:image/') || $this->isBase64($imageInput))) {
            Log::info('Image upload detected: Base64 string');

            try {
                // Parse base64 header if exists
                if (preg_match('/^data:image\/(\w+);base64,/', $imageInput, $matches)) {
                    $extension = $matches[1];
                    $data = substr($imageInput, strpos($imageInput, ',') + 1);
                } else {
                    $extension = 'png'; // default fallback
                    $data = $imageInput;
                }

                $decodedData = base64_decode($data);
                if (!$decodedData) {
                    Log::error('Base64 decode failed');
                    throw new Exception('Invalid base64 data.');
                }

                // Size validation
                $sizeInBytes = strlen($decodedData);
                $this->validateFileSize($sizeInBytes);

                // MIME validation using fileinfo
                $finfo = finfo_open();
                $mimeType = finfo_buffer($finfo, $decodedData, FILEINFO_MIME_TYPE);
                finfo_close($finfo);

                Log::info('Base64 detected MIME', ['mime' => $mimeType]);
                $this->validateMimeType($mimeType);

                $filename = Str::random(40) . '.' . $extension;
                $path = $directory . '/' . $filename;

                $stored = Storage::disk('public')->put($path, $decodedData);
                if (!$stored) {
                    Log::error('Image store failed for Base64');
                    throw new Exception('Failed to store decoded base64 image.');
                }

                return $this->formatUrl($path);
            } catch (Exception $e) {
                Log::error('Base64 upload exception: ' . $e->getMessage());
                throw $e;
            }
        }

        // 3. Handle Direct Image URL
        if (is_string($imageInput) && (str_starts_with($imageInput, 'http://') || str_starts_with($imageInput, 'https://'))) {
            Log::info('Image upload detected: External URL', ['url' => $imageInput]);

            // Validate URL format
            if (filter_var($imageInput, FILTER_VALIDATE_URL) === false) {
                Log::error('Invalid image URL format', ['url' => $imageInput]);
                throw new Exception('Invalid external image URL format.');
            }

            return $imageInput; // Save external URL directly
        }

        Log::error('Image upload failed: Unsupported format');
        throw new Exception('Unsupported image format. Must be an uploaded file, base64 string, or direct URL.');
    }

    /**
     * Delete an existing image file from storage if it is hosted locally.
     * Do not delete external URLs.
     *
     * @param string|null $imageUrl
     * @return bool
     */
    public function delete(?string $imageUrl): bool
    {
        if (!$imageUrl) {
            return false;
        }

        $appUrl = config('app.url');
        
        // Check if the image belongs to our server/storage
        if (str_contains($imageUrl, $appUrl) || str_contains($imageUrl, '/storage/')) {
            // Extract relative path from URL (e.g. from http://domain.com/storage/profile-images/abc.png to profile-images/abc.png)
            $storageSegment = '/storage/';
            $startPos = strpos($imageUrl, $storageSegment);
            
            if ($startPos !== false) {
                $relativePath = substr($imageUrl, $startPos + strlen($storageSegment));
                if (Storage::disk('public')->exists($relativePath)) {
                    Log::info('Deleting old local image', ['path' => $relativePath]);
                    return Storage::disk('public')->delete($relativePath);
                }
            }
        }

        Log::info('Skipped deletion: Image is external URL or not found locally', ['url' => $imageUrl]);
        return false;
    }

    protected function validateMimeType(string $mimeType): void
    {
        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            Log::error('Invalid image MIME type rejected', ['mime' => $mimeType]);
            throw new Exception('Invalid image format. Allowed formats: JPEG, JPG, PNG, WEBP.');
        }
    }

    protected function validateFileSize(int $bytes): void
    {
        $sizeInKb = $bytes / 1024;
        if ($sizeInKb > $this->maxFileSize) {
            Log::error('Image file size rejected: Oversized', ['size_kb' => $sizeInKb]);
            throw new Exception('Image is too large. Maximum size allowed is 5MB.');
        }
    }

    protected function isBase64(string $string): bool
    {
        if (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $string)) {
            return base64_encode(base64_decode($string, true)) === $string;
        }
        return false;
    }

    /**
     * Format a clean, consistent public URL for a stored image path.
     * Ensures images always use APP_URL or server host without MAMP subfolder clutter.
     */
    public function formatUrl(string $path): string
    {
        // Extract relative storage path if string contains full URL / MAMP paths
        if (str_contains($path, '/storage/')) {
            $storageSegment = '/storage/';
            $relativePath = substr($path, strpos($path, $storageSegment) + strlen($storageSegment));
        } else {
            $relativePath = ltrim($path, '/');
        }

        $appUrl = config('app.url');
        if (empty($appUrl) || $appUrl === 'http://localhost') {
            $baseUrl = 'http://192.168.1.231:8000';
        } else {
            $baseUrl = $appUrl;
        }

        return rtrim($baseUrl, '/') . '/storage/' . ltrim($relativePath, '/');
    }
}
