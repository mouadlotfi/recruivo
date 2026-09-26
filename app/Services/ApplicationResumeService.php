<?php

namespace App\Services;

use App\Models\Application;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Ownership of the resume files attached to applications.
 *
 * An application must own its resume. The candidate's profile resume is a mutable
 * document they are expected to replace, and the profile flows replace it by
 * deleting the old file and storing the new one under a new name - so an
 * application that merely points at the profile's path is either served the wrong
 * document or served nothing at all.
 */
class ApplicationResumeService
{
    /**
     * Copy a profile resume into the application-owned folder.
     *
     * Falls back to the given path when the file is not on the disk, so a profile
     * whose resume was already lost behaves exactly as it does today instead of
     * blocking the application.
     */
    public function copyForApplication(?string $path): ?string
    {
        if (! $path) {
            return $path;
        }

        $disk = Storage::disk('private');

        if (! $disk->exists($path)) {
            return $path;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $copy = config('filesystems.application_resumes').'/'.Str::uuid()
            .($extension !== '' ? '.'.$extension : '');

        $disk->copy($path, $copy);

        return $copy;
    }

    /**
     * Delete a private-disk file only when no application still points at it.
     *
     * Applications submitted before copy-on-apply existed share the profile's path,
     * and a copy can fall back to the original path, so the profile flows must not
     * delete a file an application still relies on.
     */
    public function deleteIfUnreferenced(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (Application::where('resume_path', $path)->exists()) {
            return;
        }

        Storage::disk('private')->delete($path);
    }
}
