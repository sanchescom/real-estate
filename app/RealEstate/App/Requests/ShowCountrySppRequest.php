<?php

declare(strict_types=1);

namespace App\RealEstate\App\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ShowCountrySppRequest extends FormRequest
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
            'filter' => ['nullable', 'array'],
            'filter.type' => ['nullable', 'string', Rule::in(['nominal', 'real'])],
            'filter.metric' => ['nullable', 'string', Rule::in(['index', 'yoy'])],
            'filter.from' => ['nullable', 'string', 'regex:/^\d{4}-Q[1-4]$/'],
            'filter.to' => ['nullable', 'string', 'regex:/^\d{4}-Q[1-4]$/'],
            'sort' => ['nullable', 'string', Rule::in(['period', '-period', 'value', '-value'])],
            'page' => ['nullable', 'array'],
            'page.offset' => ['nullable', 'integer', 'min:0'],
            'page.limit' => ['nullable', 'integer', 'min:1', 'max:500'],
            'fmt' => ['nullable', 'string', Rule::in(['json', 'csv'])],
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
