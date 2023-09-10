<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Validator;
use Illuminate\Http\Request;
use App\Service\EvaluateService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

final class EvaluateController extends Controller
{
    public function __invoke(Request $request, EvaluateService $evaluateService, Validator $validator): JsonResponse
    {
        $stage = intval($request->route('stage', 1));
        
        $urls = $validator->validate($request->all(), [
            'urls' => 'required|array|max:1',
            'urls.*' => 'required|url',
        ])['urls'];

            dd($this->evaluator($stage)->read($urls)); // hngx stage 3

       // $evaluateService->evaluate(array_unique($urls), $this->evaluator($stage));

        $results = $evaluateService->passedEvaluation()->merge($evaluateService->failedEvaluation())->toArray();

        return new JsonResponse(['data' => [...$results]]);
    }
}
