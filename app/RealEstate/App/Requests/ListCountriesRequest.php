<?php

declare(strict_types=1);

namespace App\RealEstate\App\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListCountriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'array'],
            'page.offset' => ['nullable', 'integer', 'min:0'],
            'page.limit' => ['nullable', 'integer', 'min:1', 'max:500'],
            'sort' => ['nullable', 'string', Rule::in(['code', '-code', 'name', '-name'])],
            'fmt' => ['nullable', 'string', Rule::in(['json', 'csv'])],
        ];
    }
}
