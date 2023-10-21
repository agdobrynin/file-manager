<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class MyFilesActionRequest extends FilesActionRequest
{
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->parentFolder === null && $this->input(self::ALL_FILES_KEY)) {
                    $validator->errors()->add(
                        self::ALL_FILES_KEY,
                        'When parameter "' . self::ALL_FILES_KEY . '" is true route parameter "parentFolder" is required.'
                    );
                }
            }
        ];
    }
}
