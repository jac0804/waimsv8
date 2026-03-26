<?php

namespace App\Http\Classes\api;

use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\Logger;
use App\Http\Classes\othersClass;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

use Exception;

class imageapi
{
    private $otherclass;

    public function __construct()
    {
        $this->otherclass = new othersClass;
    }

    public function imageapifunc($params)
    {

        switch ($params['action']) {
            case md5('saveimage'):
                return $this->saveimage($params);
                break;
            case md5('loadimage'):
                return $this->loadimage($params);
                break;
            case md5('deleteimage'):
                return $this->deleteimage($params);
                break;
        }

        return ['status' => true];
    }

    private function saveimage($params)
    {

        $base64String = $params['image'];

        // Add prefix if not already present (PHP 7.x compatible version)
        if (strpos($base64String, 'data:image') !== 0) {
            $base64String = "data:image/jpeg;base64," . $base64String;
        }

        if (!empty($base64String)) {
            $extension = 'jpeg';

            // Extract extension and data
            if (strpos($base64String, ';base64') !== false) {
                list($type, $base64String) = explode(';', $base64String);
                list(, $base64String) = explode(',', $base64String);
                $extension = explode('/', $type)[1];
            }

            // Decode image
            $imageData = base64_decode($base64String);
            if ($imageData === false) {
                return ['status' => false, 'msg' => 'Invalid image data for ' . $params['filename']];
            }

            // Generate filename and ensure directory exists
            $filename = $params['doc'] . '/' . $params['filename'] . '.' . $extension;

            try {
                // Create directory if it doesn't exist
                if (!Storage::disk('public')->exists(dirname($filename))) {
                    Storage::disk('public')->makeDirectory(dirname($filename));
                }

                // Save file
                $putResult = Storage::disk('public')->put($filename, $imageData);

                if (!$putResult) {
                    return ['status' => false, 'msg' => 'Failed to save image for ' . $params['filename']];
                }


                return ['status' => true, 'msg' => 'Sucessfully save'];
            } catch (\Exception $e) {
                return [
                    'status' => false,
                    'msg' => 'Image upload error: ' . $e->getMessage(),
                    // 'storage_path' => storage_path('app/public/' . $filename)
                ];
            }
        }
    }

    private function loadimage($params)
    {
        // Path to the image in the public directory (relative to public folder)
        $imagePath = 'images/' . $params['doc'] . '/' . $params['filename'] . '.jpeg';

        // Get the full filesystem path
        $fullPath = public_path($imagePath);

        // Check if file exists
        if (!file_exists($fullPath)) {
            // abort(404, 'Image not found');
            return ['status' => false, 'msg' => 'Image not found. Path ' . $imagePath];
        }

        // Get the file content
        $imageData = file_get_contents($fullPath);

        // Convert to base64
        $base64 = base64_encode($imageData);

        // Output or use the base64 string
        return ['status' => true, 'image' => $base64];
    }

    private function deleteimage($params)
    {
        // Path to the image in the public directory (relative to public folder)
        $parentfolder = 'images/';
        $imagePath = $params['doc'] . '/' . $params['filename'] . '.jpeg';

        // Get the full filesystem path
        $fullPath = public_path($parentfolder . $imagePath);

        // Check if file exists
        if (!file_exists($fullPath)) {
            // abort(404, 'Image not found');
            return ['status' => false, 'msg' => 'Image not found. Path ' . $imagePath];
        } else {

            Storage::disk('public')->delete($imagePath);

            Storage::delete($parentfolder . $imagePath);
        }

        // Output or use the base64 string
        return ['status' => true, 'msg' => 'Successfully deleted'];
    }
}
