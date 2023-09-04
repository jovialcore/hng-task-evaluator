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
    /**
     * @throws \League\Csv\CannotInsertRecord
     */
    public function __invoke(Request $request, SlackService $slack, EvaluateService $evaluateService)
    {
        $errors = [];
        $stage = intval($request->route('stage', 1));

        // if ($this->stageHasEnded($stage)) {
        //     $slack->sendStageHasEndedMessage($request->get('response_url', ''));

        //     return new Response();
        // }

        $evaluateService->setEvaluator(
            $evaluator = $this->evaluator($stage)
        );

        try {
            $url = $this->validate($request)['url'];
        
            $alreadyEvaluated = $evaluateService->hasAlreadyEvaluatedUrls([$url], $this->csvAdditionalData($request)['headers']);

            if ($alreadyEvaluated) {
                $slack->sendAlreadyEvaluatedMessage($request->get('response_url'), $url);

                return new Response();
            }

            $evaluateService->evaluate([$url], $evaluator);
        } catch (ValidationException $e) {
            $errors = $e->validator->errors()->all();
           
            if ($e->validator->errors()->has('response_url')) {
                return new Response('Nope. We are not doing this', HttpFoundationResponse::HTTP_FORBIDDEN);
            }
        }

        if (empty($errors)) {
            $result = $this->writeToCsv($request, $evaluateService, $stage === 1);

            if ($result === null) {
                $errors[] = 'You already submitted this URL.';
            }
        }

        $this->sendToSlack($slack, $request, $evaluateService, $errors);

     

        return new Response();
    }

    protected function validate(Request $request): array
    {
        $submittedUrl = preg_match('/^(https?:\/\/[^\s]+\/api)\?slack_name=[^&]+&track=(?i)backend$/', $request->get('text'), $matches) ? $matches[0] : 'invalid';

        // dd($submittedUrl);
       
        return Validator::make(
            ['url' => $submittedUrl, 'response_url' => $request->get('response_url')],
            ['url' => 'required|url', 'response_url' => 'required|url|starts_with:https://hooks.slack.com'],
            ['response_url.starts_with' => 'Nice try.'],
        )->validate();
    }

    protected function stageHasEnded(int $stage): bool
    {
//                 return $stage === 1;
            return in_array($stage, [1, 2]);
    }

    protected function csvAdditionalData(Request $request): array
    {
        return [
            'headers' => ['slackProfileUrl'],
            'line' => ['https://hng9.slack.com/team/'.$request->get('user_id')],
        ];
    }

    /**
     * @throws \League\Csv\CannotInsertRecord
     */
    protected function writeToCsv(Request $request, EvaluateService $service, bool $isStageOne): ?bool
    {
        $slackUsername = $request->get('user_name');
        $additionalData = $this->csvAdditionalData($request);

        // Maintain backwards compatibility
        if ($isStageOne) {
            $additionalData['headers'] = ['submitter'];
            $additionalData['line'] = [$slackUsername];
        }

        return $service->writeToCsv($additionalData, ['filterIgnorableErrors' => ! $isStageOne]);
    }

    protected function sendToSlack(SlackService $slack, Request $request, EvaluateService $evaluate, array $errors): void
    {
        $hook = $request->get('response_url');
        $passed = empty($errors) && $evaluate->allSuccessful(filterIgnorableErrors: true);
        $errors = $passed ? [] : [...$evaluate->failedErrors(filterIgnorableErrors: true), ...$errors];

        $slack->sendEvaluationResult($hook, $passed, $errors);
    }
}