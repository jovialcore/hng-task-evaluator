<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stage1;

use App\Service\Stage1\EvaluateService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class EvaluateController extends Controller
{
    public function __invoke(EvaluateService $evaluateService, Request $request)
    {
        $evaluateService->evaluate($this->urls());

        dd($evaluateService->passedEvaluation(), $evaluateService->failedEvaluation());
        return 'Hello';
    }

    /**
     * @return array<int, string>
     */
    protected function urls(): array
    {
        return [
            "https://633dd6017e19b1782916d0d5.mockapi.io/about-me/1",
            "https://6355ac32483f5d2df3b86de1.mockapi.io/about-fail2/1",
            "https://633dd6017e19b1782916d0d5.mockapi.io/about-me/2",
//            'https://www.google.com',
//            'https://www.yahoo.com',
//            'http://www.bing.com',
//            'ht://invalid',
//            'https://yafgugabjagugccz-not-found.com'
        ];
    }
}
