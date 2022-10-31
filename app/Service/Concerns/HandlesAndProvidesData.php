<?php

declare(strict_types=1);

namespace App\Service\Concerns;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Psr7\Response;

trait HandlesAndProvidesData
{
    protected array $content = [];

    protected array $evaluationData = [];

    public function data(Response $response, string $url): array
    {
        $this->content[$url] ??= $this->getContent($response, $url);

        return [
            ...$this->content[$url],
            'status_code' => $response->getStatusCode(),
            'content_type' => strtolower($response->getHeader('Content-Type')[0] ?? ''),
        ];
    }

    public function getContent(Response $response, string $url): array
    {
        if (empty($this->content[$url] ?? null)) {
            $this->content[$url] = (array) json_decode($response->getBody()->getContents(), true) ?? [];
        }

        return $this->content[$url] ?? [];
    }

    public function fetch(array $urls): array
    {
        return Utils::settle(
            collect($urls)->mapWithKeys(fn (string $url) => [$url => $this->http()->getAsync($url)])->toArray()
        )->wait();
    }

    public function getEvaluationData(string $url): array
    {
        return $this->evaluationData[$url];
    }

    protected function http(): Client
    {
        return new Client([
            'timeout' => 5, 'http_errors' => false, 'connect_timeout' => 3,
        ]);
    }
}
