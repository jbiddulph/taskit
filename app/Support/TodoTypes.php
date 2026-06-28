<?php

namespace App\Support;

class TodoTypes
{
    public static function all(): array
    {
        return Industries::allTypeValues();
    }

    public static function validationRule(): string
    {
        return Industries::validationRule();
    }

    public static function requiredValidationRule(): string
    {
        return Industries::requiredValidationRule();
    }
}
