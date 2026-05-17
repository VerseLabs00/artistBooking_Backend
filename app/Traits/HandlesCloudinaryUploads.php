<?php

namespace App\Traits;

use Cloudinary\Cloudinary;

trait HandlesCloudinaryUploads
{

    protected function getCloudinaryInstance()
    {
        $cloudinary = new Cloudinary(config('services.cloudinary.url'));


        if (app()->environment('local')) {
            $cloudinary->configuration->api->uploadPrefix = 'http://api.cloudinary.com';
            $cloudinary->configuration->api->secure = false;
        }

        return $cloudinary;
    }


    protected function uploadFile($file, $folder, $resourceType = 'auto')
    {
        $cloudinary = $this->getCloudinaryInstance();

        return $cloudinary->uploadApi()->upload($file->getRealPath(), [
            'folder' => $folder,
            'resource_type' => $resourceType
        ]);
    }
}
