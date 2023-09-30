<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FilePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, File $file): bool
    {
        return $file->isOwnedByUser($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, File $parent): bool
    {
        return $parent->isOwnedByUser($user) && $parent->isFolder();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, File $file): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, File $file): bool
    {
        return $file->isOwnedByUser($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, File $file): Response
    {
        if (!$file->isOwnedByUser($user)) {
            return Response::deny('You are not owner file ' . $file->path);
        }

        // Check exist file
        // TODO may be diff ability for force restore? like as "restoreForce"
        if (File::query()->where('path', $file->path)->first()) {
            return Response::deny('File ' . $file->name . ' already exists on your disk at path ' . $file->path);
        }

        return Response::allow();
    }
}
