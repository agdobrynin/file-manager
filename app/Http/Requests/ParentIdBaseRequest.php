<?php

namespace App\Http\Requests;

use App\Models\File;
use Illuminate\Foundation\Http\FormRequest;

class ParentIdBaseRequest extends FormRequest
{
    public File $parentFolder;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($parentFolder = $this->route('parentFolder')) {
            $this->parentFolder = File::findOrFail($parentFolder);

            return $this->parentFolder->isFolder();
        }

        $this->parentFolder = File::rootFolderByUser($this->user());

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
        ];
    }
}
