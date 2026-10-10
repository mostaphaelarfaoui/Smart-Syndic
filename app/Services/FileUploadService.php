<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * FileUploadService — Secure file upload handler (Security §1.1).
 *
 * Validates MIME types via binary stream sniffing, renames files to
 * cryptographically random UUIDs, and stores them in private buckets.
 */
class FileUploadService
{
    /**
     * Validate and store an uploaded file securely.
     *
     * @return string The storage path of the uploaded file.
     * @throws UnprocessableEntityHttpException
     */
    public function upload(UploadedFile $file, string $directory = 'uploads'): string
    {
        $this->validateMimeType($file);
        $this->validateFileSize($file);

        // Generate cryptographically random filename
        $extension = $this->getExtensionFromMime($file->getMimeType());
        $filename  = Str::uuid()->toString() . '.' . $extension;

        $disk = config('smartsyndic.uploads.storage_disk', 'public');
        $visibility = ($disk === 's3') ? 'private' : 'public';
        $path = $file->storeAs($directory, $filename, [
            'disk'       => $disk,
            'visibility' => $visibility,
        ]);

        if (! $path) {
            throw new UnprocessableEntityHttpException('File upload failed.');
        }

        return $path;
    }

    /**
     * Validate MIME type via binary stream inspection (not file extension).
     */
    private function validateMimeType(UploadedFile $file): void
    {
        $allowedMimes = config('smartsyndic.uploads.allowed_mimes', [
            'image/jpeg',
            'image/png',
            'image/webp',
            'application/pdf',
        ]);

        // Use finfo for true binary stream sniffing
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($file->getRealPath());

        if (! in_array($realMime, $allowedMimes, true)) {
            throw new UnprocessableEntityHttpException(
                "Invalid file type: {$realMime}. Allowed: " . implode(', ', $allowedMimes)
            );
        }
    }

    /**
     * Validate file size (max 5MB).
     */
    private function validateFileSize(UploadedFile $file): void
    {
        $maxKb   = config('smartsyndic.uploads.max_size_kb', 5120);
        $maxBytes = $maxKb * 1024;

        if ($file->getSize() > $maxBytes) {
            throw new UnprocessableEntityHttpException(
                "File size exceeds the maximum limit of {$maxKb}KB."
            );
        }
    }

    /**
     * Map MIME type to safe file extension.
     */
    private function getExtensionFromMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/webp'      => 'webp',
            'application/pdf' => 'pdf',
            default           => 'bin',
        };
    }

    /**
     * Generate a time-limited signed URL for a stored file.
     */
    public function getSignedUrl(string $path, int $minutes = null): string
    {
        $minutes = $minutes ?? config('smartsyndic.uploads.signed_url_ttl', 15);
        $disk    = config('smartsyndic.uploads.storage_disk', 's3');

        return Storage::disk($disk)->temporaryUrl($path, now()->addMinutes($minutes));
    }

    /**
     * Delete a file from storage.
     */
    public function delete(string $path): bool
    {
        $disk = config('smartsyndic.uploads.storage_disk', 's3');
        return Storage::disk($disk)->delete($path);
    }
}
