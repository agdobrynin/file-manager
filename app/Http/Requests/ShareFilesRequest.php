<?php

namespace App\Http\Requests;

use App\Models\User;

class ShareFilesRequest extends FilesActionRequest
{
    public ?User $shareToUser;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'email' => [
                'required',
                'email:rfc,strict',
                function (string $attr, string $email, $fail) {
                    $this->shareToUser = User::firstWhere('email', $email);

                    if ($email === $this->shareToUser?->email) {
                        $fail('Can not share files to yourself.');
                    }
                }
            ],
        ]);
    }
}
