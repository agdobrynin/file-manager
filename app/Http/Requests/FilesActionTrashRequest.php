<?php

namespace App\Http\Requests;

use App\Models\File;
use Illuminate\Database\Eloquent\Collection;

class FilesActionTrashRequest extends ActionWithAllKeyRequest
{
    /**
     * @var Collection<File>
     */
    public Collection $requestFiles;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $this->requestFiles = new Collection();

        return array_merge(parent::rules(), [
            'ids' => [
                'bail',
                'required_if:' . self::ALL_FILES_KEY . ',false',
                'array',
                function (string $attribute, array $ids, $fail) {
                    $foundFiles = File::fileByOwner($this->user())
                        ->onlyTrashed()
                        ->whereIn('id', $ids)
                        ->get();

                    if ($foundFiles->count() !== count($ids)) {
                        $fail('Some file IDs are not valid.');
                    }

                    $this->requestFiles = $foundFiles;
                }
            ]
        ]);
    }
}
