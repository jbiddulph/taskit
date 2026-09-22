<?php

namespace App\Services;

use App\Models\OperationalObject;
use App\Models\OperationalObjectPhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OperationalObjectPhotoService
{
    public const MAX_PHOTOS = 30;

    public const MAX_BYTES = 10 * 1024 * 1024;

    public const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public function add(
        OperationalObject $object,
        UploadedFile $file,
        ?User $uploader = null,
        ?string $caption = null,
        bool $asCover = false,
    ): OperationalObjectPhoto {
        if ($object->photos()->count() >= self::MAX_PHOTOS) {
            throw new \RuntimeException('This site already has the maximum number of photos ('.self::MAX_PHOTOS.').');
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = Str::uuid().'.'.$extension;
        $filePath = $file->storeAs('site-photos/'.$object->id, $filename, 'private');

        return DB::transaction(function () use ($object, $file, $uploader, $caption, $asCover, $filename, $filePath) {
            $makeCover = $asCover || $object->photos()->count() === 0;

            if ($makeCover) {
                $object->photos()->update(['is_cover' => false]);
            }

            return OperationalObjectPhoto::create([
                'operational_object_id' => $object->id,
                'uploaded_by_user_id' => $uploader?->id,
                'filename' => $filename,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: 'image/jpeg',
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
                'caption' => $caption,
                'sort_order' => (int) $object->photos()->max('sort_order') + 1,
                'is_cover' => $makeCover,
            ]);
        });
    }

    public function setCover(OperationalObject $object, OperationalObjectPhoto $photo): void
    {
        if ($photo->operational_object_id !== $object->id) {
            abort(404);
        }

        DB::transaction(function () use ($object, $photo) {
            $object->photos()->update(['is_cover' => false]);
            $photo->update(['is_cover' => true]);
        });
    }

    public function updateCaption(OperationalObjectPhoto $photo, ?string $caption): OperationalObjectPhoto
    {
        $photo->update(['caption' => $caption]);

        return $photo->fresh();
    }

    public function reorder(OperationalObject $object, array $orderedIds): void
    {
        DB::transaction(function () use ($object, $orderedIds) {
            foreach (array_values($orderedIds) as $index => $photoId) {
                $object->photos()
                    ->where('id', (int) $photoId)
                    ->update(['sort_order' => $index]);
            }
        });
    }

    public function delete(OperationalObjectPhoto $photo): void
    {
        DB::transaction(function () use ($photo) {
            $objectId = $photo->operational_object_id;
            $wasCover = $photo->is_cover;
            $path = $photo->file_path;

            $photo->delete();

            if ($path && Storage::disk('private')->exists($path)) {
                Storage::disk('private')->delete($path);
            }

            if ($wasCover) {
                $next = OperationalObjectPhoto::query()
                    ->where('operational_object_id', $objectId)
                    ->orderBy('sort_order')
                    ->first();

                if ($next) {
                    $next->update(['is_cover' => true]);
                }
            }
        });
    }

    public function deleteAllForObject(OperationalObject $object): void
    {
        $object->loadMissing('photos');

        foreach ($object->photos as $photo) {
            if ($photo->file_path && Storage::disk('private')->exists($photo->file_path)) {
                Storage::disk('private')->delete($photo->file_path);
            }
        }

        $object->photos()->delete();
    }
}
