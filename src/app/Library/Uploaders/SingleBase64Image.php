<?php

namespace Backpack\CRUD\app\Library\Uploaders;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SingleBase64Image extends Uploader
{
    public function uploadFiles(Model $entry, $value = null)
    {
        $value = $value ?? CRUD::getRequest()->get($this->getName());
        $previousImage = $entry->getOriginal($this->getName());

        if (! $value && $previousImage) {
            Storage::disk($this->getDisk())->delete($previousImage);

            return null;
        }

        if (Str::startsWith($value, 'data:image')) {
            if ($previousImage) {
                Storage::disk($this->getDisk())->delete($previousImage);
            }

            $base64Image = Str::after($value, ';base64,');
            $finalPath = $this->getPath().$this->getFileName($value);

            Storage::disk($this->getDisk())->put($finalPath, base64_decode($base64Image));

            return $finalPath;
        }

        return $previousImage;
    }

    public function uploadRepeatableFiles($values, $previousRepeatableValues)
    {
        foreach ($values as $row => $rowValue) {
            if ($rowValue) {
                if (Str::startsWith($rowValue, 'data:image')) {
                    $base64Image = Str::after($rowValue, ';base64,');
                    $finalPath = $this->getPath().$this->getFileName($rowValue);
                    Storage::disk($this->getDisk())->put($finalPath, base64_decode($base64Image));
                    $values[$row] = $previousRepeatableValues[] = $finalPath;

                    continue;
                }
            }
        }

        $imagesToDelete = array_diff($previousRepeatableValues, $values);

        foreach ($imagesToDelete as $image) {
            Storage::disk($this->getDisk())->delete($image);
        }

        return $values;
    }
}