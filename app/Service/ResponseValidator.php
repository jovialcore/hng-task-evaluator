<?php

declare(strict_types=1);

namespace App\Service;

use App\Validator;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

final class ResponseValidator
{
    public function validate(array $data, array $rules, array $messages = [], array $attrs = []): array
    {
        $result = ['passed' => true, 'errors' => []];

        try {
            Validator::make($data, $rules, $messages, $attrs)->validate();
        } catch (ValidationException $e) {
            $result['passed'] = false;
            $result['errors'] = Arr::flatten($e->errors());
        }

        return $result;
    }
}
