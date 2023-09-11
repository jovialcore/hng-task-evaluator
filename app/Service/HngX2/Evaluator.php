<?php

declare(strict_types=1);

namespace App\Service\HngX2;

use App\Service\Concerns\HandlesAndProvidesData;
use App\Service\Contracts\Evaluator as ContractsEvaluator;
use App\Validator;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\JsonResponse;

class Evaluator implements ContractsEvaluator

{

    use HandlesAndProvidesData {
        data as protected traitData;
        getContent as protected traitGetContent;
    }

    protected  $formData = [
        'name' => 'pesova'
    ];

    protected  $newData = [
        'name' => 'uncle jo'
    ];

    protected  $errors = [];
    public function rules(string $url): array
    {


        return [];
    }
    public function fetch($url): array
    {


        $url = $url[0];

        $formData = [
            'name' => 'Elon Musk'
        ];
        $promises = [
            'url' => $this->http()->getAsync($url)
        ];

        $response = Utils::settle($promises)->wait();

        return json_decode($response['url']['value']->getBody()->getContents(), true);
    }


    public function post($urls): array
    {

        $url = $urls[0];

        $promises = [
            'url' => $this->http()->postAsync($url, [RequestOptions::JSON => $this->formData])
        ];

        $response = Utils::settle($promises)->wait();

        return  json_decode($response['url']['value']->getBody()->getContents(), true);
    }



    public function update($urls): array
    {

        // dd($urls);
        $this->post($urls);
        $id  = $this->fetch($urls)[0]['id'];
        $name  = $this->fetch($urls)[0]['name'];
        $url = $urls[0];


        // check if reading went through // if there is an error, I will have to reshufflw the data i am using 
        if ($name !== $this->formData['name']) {
            $this->errors =  ["I can't find {$name} in your database with this id {$id} "];
        }
        $patchUrl = $url . "/{$id}";

        $promises = [
            'url' => $this->http()->putAsync($patchUrl, [RequestOptions::JSON => $this->newData])
        ];

        $response = Utils::settle($promises)->wait();

        return json_decode($response['url']['value']->getBody()->getContents(), true);
    }

    public function readUpdate($urls): array
    {

        // dd($urls);
        $this->update($urls);
        $id  = $this->fetch($urls)[0]['id'];
        $name  = $this->fetch($urls)[0]['name'];
        $url = $urls[0];


        // check if reading went through // if there is an error, I will have to reshufflw the data i am using 
        if ($name !== $this->newData['name']) {
            $this->errors = ["For your Update request, I can't find {$name} in your database with this id {$id} "];
        }
        $patchUrl = $url . "/{$id}";

        $promise = Utils::settle(
            collect($urls)->mapWithKeys(fn (string $url) => [
                $url =>  $this->http()->deleteAsync($patchUrl, [RequestOptions::JSON => ['id' => $id]]),
            ])->toArray()
        )->wait();

        if (!empty($this->errors)) {
            $promise[$url]['errors'] =  $this->errors;
        }
        return $promise;

        // dd(json_decode($response['url']['value']->getBody()->getContents(), true));
    }

    //check for instances where post, read of put operation fails

    public function messages(): array
    {
        return [
            'in' => 'The :attribute should be in this format : :values',
        ];
    }


    public function csvFilepath(): string
    {
        return PROJECT_ROOT_PATH . '/storage/passed.csv';
    }
}
