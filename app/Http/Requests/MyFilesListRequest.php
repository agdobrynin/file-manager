<?php

namespace App\Http\Requests;

class MyFilesListRequest extends ParentIdBaseRequest
{
    protected const ONLY_FAVORITES_QUERY_PARAM = 'onlyFavorites';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'search' => 'nullable|string',
            self::ONLY_FAVORITES_QUERY_PARAM => 'nullable|boolean',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            self::ONLY_FAVORITES_QUERY_PARAM => filter_var($this->{self::ONLY_FAVORITES_QUERY_PARAM}, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
        ]);
    }
}
