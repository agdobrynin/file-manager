<?php

declare(strict_types=1);

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
        return array_merge(parent::rules(), [
            self::ALL_FILES_KEY => 'required|boolean',
            'ids' => [
                'bail',
                'required_if:'.self::ALL_FILES_KEY.',false',
                'array',
                function (string $attribute, array $ids, $fail): void {
                    $this->requestFiles = new Collection();

                    if ($ids) {
                        $foundFiles = File::fileByOwner($this->user())
                            ->whereIn('id', $ids)
                            ->get();

                        if ($foundFiles->count() !== count($ids)) {
                            $fail('Some file IDs are not valid.');
                        }

                        $this->requestFiles = $foundFiles;
                    }
                },
            ],
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            self::ALL_FILES_KEY => filter_var($this->{self::ALL_FILES_KEY}, FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
