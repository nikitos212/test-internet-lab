<?php

namespace App\Exception;

class ApiValidationException extends \RuntimeException
{
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Проверьте заполнение формы');
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
