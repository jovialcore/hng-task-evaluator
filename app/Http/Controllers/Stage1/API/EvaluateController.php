<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stage1\API;

use App\Validator;
use App\Service\Stage1;
use Illuminate\Http\Request;
use App\Service\EvaluateService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

final class EvaluateController extends Controller
{
    public function __invoke(Request $request, EvaluateService $evaluateService, Validator $validator): JsonResponse
    {
        $urls = $validator->validate($request->all(), [
            'urls' => 'required|array',
            'urls.*' => 'required|url',
        ])['urls'];

        $evaluateService->evaluate(array_unique($urls), new Stage1\Evaluator());

        return new JsonResponse([
            'data' => [
                ...$evaluateService->passedEvaluation(),
                ...$evaluateService->failedEvaluation(),
            ],
        ]);
    }
}
