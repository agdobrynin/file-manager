<?php

namespace App\Http\Requests;

use App\Models\File;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class FavoriteRequest extends FormRequest
{
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
            'ids' => [
                'required',
                'array',
                function (string $attribute, array $ids, $fail) {
                    foreach ($ids as $id) {
                        $file = File::query()->where('id', $id)
                            ->where('created_by', Auth::id())
                            ->first();

                        if (null === $file) {
                            $fail('Invalid file ID ' . $id . ' for auth user.');
                        }
                    }
                }
            ]
        ];
    }
}
