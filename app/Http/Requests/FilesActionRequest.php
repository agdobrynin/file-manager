<?php

namespace App\Http\Requests;

use App\Models\File;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Validator;

class FilesActionRequest extends ParentIdBaseRequest
{
    private const ALL_FILES_KEY = 'all';
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
        return [
            self::ALL_FILES_KEY => 'nullable|boolean',
            'ids' => [
                'required_if:' . self::ALL_FILES_KEY . ',null,false',
                'array',
                function (string $attribute, array $ids, $fail) {
                    $foundFiles = File::query()->where('created_by', $this->user()->id)
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

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->input(self::ALL_FILES_KEY) && $this->parentFolder === null) {
                    $validator->errors()->add(
                        'all',
                        'When parameter "' . self::ALL_FILES_KEY . '" is true route parameter "parentFolder" is required.'
                    );
                }
            }
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            self::ALL_FILES_KEY => filter_var($this->{self::ALL_FILES_KEY}, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
        ]);
    }
}
