<?php
namespace Latdev\Validation;


class GroupValidator
{

    private $validators = [];
    private $errors = [];

    public function __construct(Validator ...$validators)
    {
        $this->validators = $validators;
    }

    public function validate(): bool
    {
        $valid = true;
        foreach ($this->validators as $validator) {
            if (!$validator->validate()) {
                $valid = false;
                $this->errors[$validator->getName()] = $validator->getErrors();
            }
        }
        return $valid;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }

}