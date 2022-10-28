<?php

namespace App;

use Illuminate\Validation\Factory;
use Illuminate\Container\Container;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Validator as BaseValidator;

final class Validator
{
    protected Factory $validator;

    protected static Validator $instance;

    public function __construct(Container $container, Translator $translator)
    {
        $this->validator = new Factory($translator, $container);
    }

    public static function setInstance(self $validator): void
    {
        self::$instance = $validator;
    }

    public static function getInstance(): self
    {
        return self::$instance;
    }

    public static function make(array $data, array $rules, array $msgs = [], array $attrs = []): BaseValidator
    {
        return self::getInstance()->validator->make($data, $rules, $msgs, $attrs);
    }

    public function validate(array $data, array $rules, array $msgs = [], array $attrs = []): array
    {
        return self::make($data, $rules, $msgs, $attrs)->validate();
    }
}
