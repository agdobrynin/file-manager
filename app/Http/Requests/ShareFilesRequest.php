<?php

namespace App\Http\Requests;

use App\Models\User;
use Closure;

class ShareFilesRequest extends MyFilesActionRequest
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
                function (string $attr, string $email, Closure $fail) {
                    $this->shareToUser = User::firstWhere('email', $email);

                    if ($this->user()->email === $this->shareToUser?->email) {
                        $fail('Can not share files to yourself.');
                    }
                },
            ],
        ]);
    }
}
