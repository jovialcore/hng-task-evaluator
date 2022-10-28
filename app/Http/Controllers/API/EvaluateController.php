<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Validator;
use App\Service\Stage1;
use Illuminate\Http\Request;
use App\Service\EvaluateService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Service\Contracts\Evaluator;

final class EvaluateController extends Controller
{
    private function evaluator(): Evaluator
    {
        return new Stage1\Evaluator();
    }

    public function __invoke(Request $request, EvaluateService $evaluateService, Validator $validator): JsonResponse
    {
        $urls = $validator->validate($request->all(), [
            'urls' => 'required|array|max:1',
            'urls.*' => 'required|url',
        ])['urls'];

        $evaluateService->evaluate(array_unique($urls), $this->evaluator());

        $results = $evaluateService->passedEvaluation()->merge($evaluateService->failedEvaluation())->toArray();

        return new JsonResponse(['data' => [...$results]]);
    }
}
