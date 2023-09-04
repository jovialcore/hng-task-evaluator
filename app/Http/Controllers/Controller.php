<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Service\Stage1;
use App\Service\Stage2;
use InvalidArgumentException;
use App\Service\Contracts\Evaluator;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    
    protected function evaluator(int $stage): Evaluator
    {
        return match ($stage) {
            1 => new Stage1\Evaluator(),
            2 => new Stage2\Evaluator(
                (new Stage2\TestDataSample())->random(),
                (new Stage2\TestDataSample(bonus: true))->random()
            ),
            
            default => throw new InvalidArgumentException('Invalid stage'),
        };
    }
}
