<?php

namespace App\Service\Contracts;

use GuzzleHttp\Psr7\Response;

interface ResponseValidator
{
    public function validate(Response $response, array $validators): array;
}
