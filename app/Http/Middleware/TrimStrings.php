<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;

class TrimStrings extends TransformsRequest
{
    protected static array $skipCallbacks = [];

    protected array $except = [];

    public function handle($request, Closure $next)
    {
        foreach (static::$skipCallbacks as $callback) {
            if ($callback($request)) {
                return $next($request);
            }
        }

        return parent::handle($request, $next);
    }

    protected function transform($key, $value)
    {
        if (in_array($key, $this->except, true) || ! is_string($value)) {
            return $value;
        }

        return preg_replace('~^[\s﻿]+|[\s﻿]+$~u', '', $value) ?? trim($value);
    }

    public static function skipWhen(Closure $callback)
    {
        static::$skipCallbacks[] = $callback;
    }
}
