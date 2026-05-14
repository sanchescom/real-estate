<?php

declare(strict_types=1);

namespace App\RealEstate\App\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ShowCountryDppRequest extends FormRequest
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
            'filter.area' => ['nullable', 'string', 'max:2'],
            'filter.property_type' => ['nullable', 'string', 'max:2'],
            'filter.vintage' => ['nullable', 'string', 'max:1'],
            'filter.freq' => ['nullable', 'string', Rule::in(['Q', 'A', 'M', 'H'])],
            'filter.from' => ['nullable', 'string', 'max:10'],
            'filter.to' => [
                'nullable', 'string', 'max:10',
                function (string $attr, mixed $val, \Closure $fail): void {
                    $from = $this->input('filter.from');
                    if (is_string($from) && is_string($val) && $val < $from) {
                        $fail('filter.to must be >= filter.from.');
                    }
                },
            ],
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
