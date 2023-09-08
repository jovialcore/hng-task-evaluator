<?php

declare(strict_types=1);

namespace App\Service\HngX2;

use App\Service\Concerns\HandlesAndProvidesData;
use GuzzleHttp\Promise\Utils;

class Evaluator
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
    public function post($url)
    {

        $formData = [
            'name' => 'Elon Musk'
        ];
        $promises = [
            'url' => $this->http()->postAsync($url, $formData)
        ];

        $response = Utils::settle($promises)->wait();

        dd($response['url']);
    }

    // make a post request to their app

    // by adding it to the body

    // return a fulfilled state

    // if fulfilled state, now make a read request to that endpoint



}
