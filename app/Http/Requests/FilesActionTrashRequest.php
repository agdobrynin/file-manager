<?php

namespace App\Http\Requests;

use App\Models\File;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

class FilesActionTrashRequest extends FormRequest
{
    private const ALL_FILES_KEY = 'all';
    /**
     * @var Collection<File>
     */
    public Collection $requestFiles;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            self::ALL_FILES_KEY => 'nullable|boolean',
            'ids' => [
                'required_if:' . self::ALL_FILES_KEY . ',null,false',
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
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            self::ALL_FILES_KEY => filter_var($this->{self::ALL_FILES_KEY}, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
        ]);
    }
}
