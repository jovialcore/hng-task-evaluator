<?php

declare(strict_types=1);

namespace App\Service\HngX2;

use App\Service\Concerns\HandlesAndProvidesData;
use App\Service\Contracts\Evaluator as ContractsEvaluator;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;

class Evaluator implements ContractsEvaluator

{

    use HandlesAndProvidesData {
        data as protected traitData;
        getContent as protected traitGetContent;
    }
    public function rules(string $url): array
    {
        

        return [

            'name' => ['required', 'string'],
        ];
    }
    public function fetch($url): array
    {
    
        $url = $url[0];
      
        $formData = [
            'name' => 'Elon Musk'
        ];
        $promises = [
            'url' => $this->http()->postAsync($url, [RequestOptions::JSON => $formData])
        ];

        $response = Utils::settle($promises)->wait();

        dd( json_decode($response['url']['value']->getBody()->getContents(), true));
    }

    // we need to make sure we are validating the right url link too 

    // make a post request to their app

    // by adding it to the body

    // return a fulfilled state

    // if fulfilled state, now make a read request to that endpoint

    public function data(Response $response, string $url): array
    {
        return [];
    }

    public function messages(): array
    {
        return [];
    }
    public function getContent(Response $response, string $url): array
    {

        return [];
    }

    public function getContentForUrl(string $url): array
    {
        return [];
    }

    public function getEvaluationData(string $url): array
    {
        return [];
    }

    public function csvFilepath(): string
    {
        return '';
    }

    public function csvHeaderColumns(): array
    {
        return [];
    }

    public function csvLine(array $item): array
    {
        return [];
    }
}
