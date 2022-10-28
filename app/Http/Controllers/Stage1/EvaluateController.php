<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stage1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class EvaluateController extends Controller
{
    public function __invoke(Request $request)
    {
        return 'Hello';
    }
}
