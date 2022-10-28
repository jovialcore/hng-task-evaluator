<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;

final class SlackBotController extends Controller
{
    public function __invoke(Request $request)
    {
        error_log(json_encode($request->all()));

        return 'ok';
    }
}
