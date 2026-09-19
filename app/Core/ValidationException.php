<?php

declare(strict_types=1);

namespace App\Core;

class ValidationException extends \RuntimeException
{
    public function __construct(public readonly Validator $validator)
    {
        parent::__construct('The submitted data is invalid.');
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->validator->errors();
    }
}
