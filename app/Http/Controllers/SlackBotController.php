<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Validator;
use Illuminate\Http\Request;
use App\Service\SlackService;
use Illuminate\Http\Response;
use App\Service\EvaluateService;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

final class SlackBotController extends Controller
{
    public function __invoke(Request $request, SlackService $slack, EvaluateService $evaluateService)
    {
        $errors = [];
        $evaluator = $this->evaluator(intval($request->route('stage', 1)));

        try {
            $data = $this->validate($request);
            $evaluateService->evaluate([$data['url']], $evaluator);
        } catch (ValidationException $e) {
            $errors = $e->validator->errors()->all();

            if ($e->validator->errors()->has('response_url')) {
                return new Response('Nope. We are not doing this', HttpFoundationResponse::HTTP_FORBIDDEN);
            }
        }

        $this->writeToCsv($evaluator->csvFilePath(), $request, $evaluateService, $errors);
        $this->sendToSlack($slack, $request, $evaluateService, $errors);

        return new Response();
    }

    protected function validate(Request $request): array
    {
        $submittedUrl = preg_match('/https?:\/\/[^\s]+/', $request->get('text'), $matches) ? $matches[0] : 'invalid';

        return Validator::make(
            ['url' => $submittedUrl, 'response_url' => $request->get('response_url')],
            ['url' => 'required|url', 'response_url' => 'required|url|starts_with:https://hooks.slack.com'],
            ['response_url.starts_with' => 'Nice try.'],
        )->validate();
    }

    protected function writeToCsv(string $path, Request $request, EvaluateService $evaluateService, array $errors): void
    {
        if (empty($errors)) {
            $submitter = $request->get('user_name');
            $evaluateService->writeToCsv($path, $submitter);
        }
    }

    protected function sendToSlack(SlackService $slack, Request $request, EvaluateService $evaluate, array $errors): void
    {
        $hook = $request->get('response_url');
        $passed = empty($errors) && $evaluate->allSuccessful();
        $errors = $passed ? [] : [...$evaluate->failedErrors(), ...$errors];

        $slack->sendEvaluationResult($hook, $passed, $errors);
    }
}
