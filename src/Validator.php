<?php
namespace Latdev\Validation;

class Validator
{

    private $name = 'field';
    private $value;
    private $rules = [];
    private $errors = [];
    private $precheck = null;
    private $valid = null;

    public function __construct($name, &$value, $default=null)
    {
        $this->name = $name;
        $this->value = (string)( $value ) ?? $default;
    }

    public static function then(Validator $precheck): Validator
    {
        $value = $precheck->getValue();
        $return = new Validator($precheck->getName(), $value);
        $return->precheck = $precheck;
        return $return;
    }

    public function required(?string $message=null)
    {
        $this->emptyMessage($message, "This field cannot be empty");
        $this->rules[] = new Rule(Rule::REQUIRED, $message);
        return $this;
    }

    public function equal($value, ?string $message=null, bool $strict=true)
    {
        $this->emptyMessage($message, "This field must be equal $value");
        $this->rules[] = new Rule(Rule::EQUAL, $message, ['value' => $value, 'strict' => $strict]);
        return $this;
    }

    public function compare(Validator $with, ?string $message=null, bool $strict=true)
    {
        $this->emptyMessage($message, "This field must be same with $with->name");
        $this->rules[] = new Rule(Rule::COMPARE, $message, ['with' => $with, 'strict' => $strict]);
        return $this;
    }

    public function checked(?string $message=null)
    {
        $this->emptyMessage($message, "Must be checked");
        $this->rules[] = new Rule(Rule::CHECKED, $message);
        return $this;
    }

    public function custom(callable $callback, ?string $message=null, ...$params)
    {
        $this->emptyMessage($message, "Not bypass custom rule");
        $this->rules[] = new Rule(Rule::CUSTOM, $message, ['callback' => $callback, 'params' => $params]);
        return $this;
    }

    public function minimumLength(int $len, ?string $message = null)
    {
        $this->emptyMessage($message, "Must be at last $len characters long");
        $this->rules[] = new Rule(Rule::MINLENGTH, $message, ['len' => $len]);
        return $this;
    }

    public function maximumLength(int $len, ?string $message=null)
    {
        $this->emptyMessage($message, "Must be at most $len characters long");
        $this->rules[] = new Rule(Rule::MAXLENGTH, $message, ['len' => $len]);
        return $this;
    }

    public function alpha(?string $message=null)
    {
        $this->emptyMessage($message, "Only alphabetic characters allowed");
        $this->rules[] = new Rule(Rule::ALPHA, $message);
        return $this;
    }

    public function validMail(?string $message=null)
    {
        $this->emptyMessage($message, "Must be an valid email address");
        $this->rules[] = new Rule(Rule::MAIL_REGEX, $message);
        return $this;
    }

    public function isNumber(?string $message=null)
    {
        $this->emptyMessage($message, "Must be an number");
        $this->rules[] = new Rule(Rule::NUMERIC, $message);
        return $this;
    }

    public function isPositiveNumber(?string $message=null)
    {
        $this->emptyMessage($message, "Must be an positive number");
        $this->rules[] = new Rule(Rule::POSITIVE_INT, $message);
        return $this;
    }

    public function minimumInt(int $min, ?string $message = null)
    {
        $this->emptyMessage($message, "Must be greater or equal $min");
        $this->rules[] = new Rule(Rule::MIN_INT, $message, ['min' => $min]);
        return $this;
    }

    public function maximumInt(int $max, ?string $message = null)
    {
        $this->emptyMessage($message, "Must be less or equal $max");
        $this->rules[] = new Rule(Rule::MAX_INT, $message, ['max' => $max]);
        return $this;
    }

    public function validate(): bool
    {
        $this->errors = [];
        $this->valid = true;

        if ($this->precheck instanceof Validator) {
            if ( ! $this->precheck->validate()) {
                $this->errors = $this->precheck->getErrors();
                return $this->valid = false;
            }
        }

        $error = function(Rule $rule) {
            $this->errors[] = $rule->getMessage();
            $this->valid = false;
        };

        /** @var Rule $rule */
        foreach ($this->rules as $rule) {
            switch ($rule->type) {

                case Rule::REQUIRED:
                    if (empty($this->value)) {
                        $error($rule);
                    }
                    break;

                case Rule::EQUAL: // [value, strict]
                    if ($rule->strict) {
                        if ($this->value !== $rule->value) {
                            $error($rule);
                        }
                    } else {
                        if ($this->value != $rule->value) {
                            $error($rule);
                        }
                    }
                    break;

                case Rule::COMPARE: // [with, strict]
                    if ($rule->strict) {
                        if ($this->value !== $rule->with->getValue()) {
                            $error($rule);
                        }
                    } else {
                        if ($this->value != $rule->with->getValue()) {
                            $error($rule);
                        }
                    }
                    break;

                case Rule::CHECKED:
                    $check = filter_var($this->value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($check === false || $check === null) {
                        $error($rule);
                    }
                    break;

                case Rule::CUSTOM: // [callback, params]
                    if (is_callable($rule->callback)) {

                        if (!call_user_func_array($rule->callback, array_merge([$this->value], $rule->params))) {
                            $error($rule);
                        }
                    }
                    break;

                case Rule::MINLENGTH: // [len]
                    if (strlen($this->value) < $rule->len) {
                        $error($rule);
                    }
                    break;

                case Rule::MAXLENGTH: // [len]
                    if (strlen($this->value) > $rule->len) {
                        $error($rule);
                    }
                    break;

                case Rule::ALPHA:
                    if (ctype_alpha($this->value) === false) {
                        $error($rule);
                    }
                    break;

                case Rule::MAIL_REGEX:
                    if (filter_var($this->value, FILTER_VALIDATE_EMAIL) === false) {
                        $error($rule);
                    }
                    break;

                case Rule::NUMERIC:
                    if (filter_var($this->value, FILTER_VALIDATE_INT) === false) {
                        $error($rule);
                    }
                    break;

                case Rule::POSITIVE_INT:
                    if ( ! filter_var($this->value, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]])) {
                        $error($rule);
                    }
                    break;

                case Rule::MIN_INT:
                    if ( ! filter_var($this->value, FILTER_VALIDATE_INT, ["options" => ["min_range" => $rule->min]])) {
                        $error($rule);
                    }
                    break;

                case Rule::MAX_INT:
                    if ( ! filter_var($this->value, FILTER_VALIDATE_INT, ["options" => ["max_range" => $rule->max]])) {
                        $error($rule);
                    }
                    break;

                case Rule::IS_NOT_FLOAT:
                case Rule::IS_FLOAT:
                case Rule::IS_DATE:
                case Rule::IS_TIME:
                case Rule::IS_LONGTIME:
                case Rule::IS_DATETIME:
                case Rule::IS_LONGDATE:
                    $error(new Rule(0, 'Sorry rule validator for 0x' . dechex($rule->type)) . ' not yet implemented');
                    break;

                default:
                    $error(new Rule(0, 'Ivalid rule type!'));
                    break;

            }
        }

        return $this->valid;
    }


    /*
    const IS_NOT_FLOAT  = 0x4002; // todo : represent this
    const IS_FLOAT      = 0x4003; // todo : represent this


    const IS_DATE       = 0x5051; // todo : represent this DD-MM-YYYY
    const IS_TIME       = 0x5052; // todo : represent this HH:MM
    const IS_LONGTIME   = 0x5053; // todo : represent this HH:MM:SS
    const IS_DATETIME   = 0x5054; // todo : represent this DD-MM-YYYY HH:mm
    const IS_LONGDATE   = 0x5055; // todo : represent this DD-MM-YYYY HH:mm:ss
    */




    public function getErrors()
    {
        return $this->errors;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }

    private function emptyMessage(&$message, $set)
    {
        if ($message === null) {
            $message = $set;
        }
    }

}