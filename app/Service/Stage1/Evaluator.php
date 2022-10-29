<?php

declare(strict_types=1);

namespace App\Service\Stage1;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Psr7\Response;
use App\Service\Contracts\Evaluator as EvaluatorContract;

final class Evaluator implements EvaluatorContract
{
    protected array $content = [];

    public function data(Response $response): array
    {
        $this->content = $this->getContent($response);

        return [
            ...$this->content,
            'status_code' => $response->getStatusCode(),
            'content_type' => strtolower($response->getHeader('Content-Type')[0] ?? ''),
        ];
    }

    public function rules(): array
    {
        return [
            // API response field validation
            'bio' => ['required', 'string'],
            'age' => ['required', 'integer'],
            'backend' => ['required', 'boolean'],
            'slackUsername' => ['required', 'string'],

            // Server response header validation
            'status_code' => ['required', 'integer', 'in:200'],
            'content_type' => ['required', 'string', 'in:application/json'],
        ];
    }

    public function messages(): array
    {
        return [
            'in' => 'The :attribute must be one of the following types: :values',
        ];
    }

    public function getContent(Response $response): array
    {
        if (empty($this->content)) {
            $this->content = (array) json_decode($response->getBody()->getContents(), true) ?? [];
        }

        return $this->content ?? [];
    }

    public function fetch(array $urls): array
    {
        return Utils::settle(
            collect($urls)->mapWithKeys(fn (string $url) => [$url => $this->http()->getAsync($url)])->toArray()
        )->wait();
    }

    protected function http(): Client
    {
        return new Client([
            'timeout' => 5, 'http_errors' => false, 'connect_timeout' => 3,
        ]);
    }
}
