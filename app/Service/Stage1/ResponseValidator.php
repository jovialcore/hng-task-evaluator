<?php

declare(strict_types=1);

namespace App\Service\Stage1;

use GuzzleHttp\Psr7\Response;
use App\Service\Contracts\ResponseValidator as ResponseValidatorContract;

final class ResponseValidator implements ResponseValidatorContract
{
    public function validate(Response $response, array $validators): array
    {
        // TODO: Implement validate() method.
    }
}
