<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use GuzzleHttp\RequestOptions;

final class SlackBotController extends Controller
{
    public function __invoke(Request $request, Client $client)
    {
        $responseUrl = $request->get('response_url');

        $successful = true;
        $errors = [];

        $client->post($responseUrl, [
            RequestOptions::JSON => $successful ? $this->successMessage() : $this->failureMessage($errors),
        ]);

        return '';
    }

    protected function successMessage(): array
    {
        return [
            'response_type' => 'ephemeral',
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'emoji' => true,
                        'type' => 'plain_text',
                        'text' => '✅ Your task was validated and submitted! Congrats!',
                    ],
                ],
            ],
        ];
    }

    protected function failureMessage(array $errors): array
    {
        return [
            'response_type' => 'ephemeral',
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'emoji' => true,
                        'type' => 'plain_text',
                        'text' => '❌ Your task verification failed. Sorry.',
                    ],
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => implode("\n", array_map(static fn (string $error) => "- {$error}", $errors)),
                    ],
                ],
            ],
        ];
    }
}
