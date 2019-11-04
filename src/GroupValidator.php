<?php
namespace Latdev\Validation;


class GroupValidator
{

    private $validators = [];
    private $errors = [];

    /**
     * GroupValidator constructor.
     *
     * @param Validator ...$validators
     */
    public function __construct(Validator ...$validators)
    {
        $this->validators = $validators;
    }

    /**
     * Validate all of present rules
     *
     * @return bool
     */
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

    /**
     * Get array of array of errors
     *
     * @return array|null
     */
    public function getErrors(): ?array
    {
        return $this->errors;
    }

}