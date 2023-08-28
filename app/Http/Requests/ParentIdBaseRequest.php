<?php

namespace App\Http\Requests;

use App\Models\File;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ParentIdBaseRequest extends FormRequest
{
    public File|null $parent;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($userId = Auth::id()) {

            $this->parent = File::query()
                ->where('id', $this->input('parent_id'))
                ->first()
                ?: File::query()
                    ->whereIsRoot()
                    ->where('created_by', $userId)->first();

            return $this->parent->isOwnedByUserId($userId);
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists(File::class, 'id')
                    ->where(static function (Builder $builder) {
                        return $builder->where('is_folder', '=', true)
                            ->where('created_by', '=', Auth::id());
                    })
            ]
        ];
    }
}
