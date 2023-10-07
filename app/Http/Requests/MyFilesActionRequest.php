<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class MyFilesActionRequest extends FilesActionRequest
{
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->input(self::ALL_FILES_KEY) && $this->parentFolder === null) {
                    $validator->errors()->add(
                        self::ALL_FILES_KEY,
                        'When parameter "' . self::ALL_FILES_KEY . '" is true route parameter "parentFolder" is required.'
                    );
                }
            }
        ];
    }
}
