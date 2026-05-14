<?php

declare(strict_types=1);

namespace App\RealEstate\App\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListDppSeriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:5', 'regex:/^[A-Z0-9]{2,5}$/'],
        ];
    }

    /** @return array<string, mixed> */
    #[\Override]
    public function validationData(): array
    {
        /** @var array<string, mixed> $parent */
        $parent = parent::validationData();

        return array_merge($parent, [
            'code' => $this->route('code'),
        ]);
    }
}
