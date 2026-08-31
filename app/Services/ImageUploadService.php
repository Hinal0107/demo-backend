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

    public function upload($imageInput, string $directory): ?string
    {
        if (!$imageInput) {
            return null;
        }

        // 1. Handle Multipart Uploaded File
        if ($imageInput instanceof UploadedFile) {
            $this->validateMimeType($imageInput->getClientMimeType());
            $this->validateFileSize($imageInput->getSize());

            $filename = Str::random(40) . '.' . $imageInput->getClientOriginalExtension();
            $path = $imageInput->storeAs($directory, $filename, 'public');

            if (!$path) {
                throw new Exception('Failed to store uploaded file.');
            }

            return $this->formatUrl($path);
        }

        // 2. Handle Base64 Image String
        if (is_string($imageInput) && (str_starts_with($imageInput, 'data:image/') || $this->isBase64($imageInput))) {
            try {
                if (preg_match('/^data:image\/(\w+);base64,/', $imageInput, $matches)) {
                    $extension = $matches[1];
                    $data = substr($imageInput, strpos($imageInput, ',') + 1);
                } else {
                    $extension = 'png';
                    $data = $imageInput;
                }

                $decodedData = base64_decode($data);
                if (!$decodedData) {
                    throw new Exception('Invalid base64 data.');
                }

                $this->validateFileSize(strlen($decodedData));

                $filename = Str::random(40) . '.' . $extension;
                $path = $directory . '/' . $filename;

                $stored = Storage::disk('public')->put($path, $decodedData);
                if (!$stored) {
                    throw new Exception('Failed to store decoded base64 image.');
                }

                return $this->formatUrl($path);
            } catch (Exception $e) {
                throw $e;
            }
        }

        // 3. Handle Direct Image URL
        if (is_string($imageInput) && (str_starts_with($imageInput, 'http://') || str_starts_with($imageInput, 'https://'))) {
            return $imageInput;
        }

        throw new Exception('Unsupported image format.');
    }

    public function delete(?string $imageUrl): bool
    {
        if (!$imageUrl) {
            return false;
        }

        if (str_contains($imageUrl, '/storage/')) {
            $storageSegment = '/storage/';
            $relativePath = substr($imageUrl, strpos($imageUrl, $storageSegment) + strlen($storageSegment));
            if (Storage::disk('public')->exists($relativePath)) {
                return Storage::disk('public')->delete($relativePath);
            }
        }

        return false;
    }

    protected function validateMimeType(string $mimeType): void
    {
        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            throw new Exception('Invalid image format. Allowed formats: JPEG, JPG, PNG, WEBP.');
        }
    }

    protected function validateFileSize(int $bytes): void
    {
        if (($bytes / 1024) > $this->maxFileSize) {
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
     * Format a clean public storage URL path.
     */
    public function formatUrl(string $path): string
    {
        if (str_contains($path, '/storage/')) {
            $relativePath = substr($path, strpos($path, '/storage/') + strlen('/storage/'));
        } else {
            $relativePath = ltrim($path, '/');
        }

        return '/storage/' . ltrim($relativePath, '/');
    }
}
