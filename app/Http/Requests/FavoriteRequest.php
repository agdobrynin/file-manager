<?php

namespace App\Http\Requests;

use App\Models\File;
use Illuminate\Foundation\Http\FormRequest;

class FavoriteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool)$this->user();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                function (string $attribute, int $id, $fail) {
                    if (!File::find($id)?->isOwnedByUser($this->user())) {
                        $fail('Invalid file ID ' . $id . ' for auth user.');
                    }
                }
            ]
        ];
    }
}
