<?php

namespace App\Http\Requests;

use App\Models\File;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Collection;

class FilesActionRequest extends ParentIdBaseRequest
{
    protected const ALL_FILES_KEY = 'all';
    /**
     * @var Collection<File>
     */
    public Collection $requestFiles;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $this->requestFiles = new Collection();

        return [
            self::ALL_FILES_KEY => 'required|boolean',
            'ids' => [
                'required_if:' . self::ALL_FILES_KEY . ',false',
                'array',
                'min:1',
                function (string $attribute, array $ids, $fail) {
                    $foundFiles = File::fileByOwner($this->user())
                        ->whereIn('id', $ids)
                        ->get();

                    if ($foundFiles->count() !== count($ids)) {
                        $fail('Some file IDs are not valid.');
                    }

                    $this->requestFiles = $foundFiles;
                }
            ]
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            self::ALL_FILES_KEY => filter_var($this->{self::ALL_FILES_KEY}, FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
