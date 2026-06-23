<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;

trait ValidatesUploadFiles
{
    protected function verificationFileRule(bool $allowPdf = true): \Closure
    {
        return function ($attribute, $value, $fail) use ($allowPdf) {
            if (!$value instanceof UploadedFile) {
                $fail('Invalid file upload.');
                return;
            }

            if (!$this->isAllowedVerificationFile($value, $allowPdf)) {
                $label = $this->verificationFieldLabel($attribute);
                $formats = $allowPdf
                    ? 'JPG, PNG, HEIC, WEBP, GIF, or PDF'
                    : 'JPG, PNG, HEIC, WEBP, or GIF';
                $fail("{$label}: \"{$value->getClientOriginalName()}\" is not a supported format. Please upload {$formats}.");
            }
        };
    }

    protected function isAllowedVerificationFile(UploadedFile $file, bool $allowPdf = true): bool
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: '');
        $mime = strtolower($file->getMimeType() ?: '');
        $filename = strtolower($file->getClientOriginalName());

        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif', 'bmp', 'tif', 'tiff', 'avif'];

        if (in_array($extension, $imageExtensions, true)) {
            return true;
        }

        if (str_starts_with($mime, 'image/')) {
            return true;
        }

        if (preg_match('/\.(heic|heif|jpg|jpeg|png|webp|gif|avif)$/i', $filename)) {
            return true;
        }

        if ($allowPdf && ($extension === 'pdf' || $mime === 'application/pdf')) {
            return true;
        }

        return false;
    }

    protected function verificationFieldLabel(string $attribute): string
    {
        return match ($attribute) {
            'front'  => 'Front side document',
            'back'   => 'Back side document',
            'selfie' => 'Selfie',
            default  => ucfirst(str_replace('_', ' ', $attribute)),
        };
    }
}
