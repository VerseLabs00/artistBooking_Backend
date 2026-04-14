<?php

namespace App\Traits;

use Cloudinary\Cloudinary;

trait HandlesCloudinaryUploads
{
    /**
     * Initialize Cloudinary with local SSL workaround.
     */
    protected function getCloudinaryInstance()
    {
        $cloudinary = new Cloudinary(config('services.cloudinary.url'));

        // SMART TOGGLE: Bypass SSL on local Windows dev
        if (app()->environment('local')) {
            $cloudinary->configuration->api->uploadPrefix = 'http://api.cloudinary.com';
            $cloudinary->configuration->api->secure = false;
        }

        return $cloudinary;
    }

    /**
     * Upload a file to Cloudinary.
     */
    protected function uploadFile($file, $folder, $resourceType = 'auto')
    {
        $cloudinary = $this->getCloudinaryInstance();

        return $cloudinary->uploadApi()->upload($file->getRealPath(), [
            'folder' => $folder,
            'resource_type' => $resourceType
        ]);
    }
}
