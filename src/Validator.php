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

    /**
     * Validator constructor.
     *
     * @param string $name  field cannonical `name`
     * @param mixed $value  field possible holder like $_POST['name']
     * @param null $default value if nothing present
     */
    public function __construct($name, &$value, $default=null)
    {
        $this->name = $name;
        $this->value = (string)( $value ) ?? $default;
    }

    /**
     * Validator constructor. allows you to run validator chains
     *
     * @param Validator $precheck   Chain item before
     * @return Validator            new validator instance
     */
    public static function after(Validator $precheck): Validator
    {
        $value = $precheck->getValue();
        $return = new Validator($precheck->getName(), $value);
        $return->precheck = $precheck;
        return $return;
    }

    /**
     * Checks required field
     *
     * @param string|null $message
     * @return $this
     */
    public function required(?string $message=null)
    {
        $this->emptyMessage($message, "This field cannot be empty");
        $this->rules[] = new Rule(Rule::REQUIRED, $message);
        return $this;
    }

    /**
     * Check is value equals some other   like : ... $_POST['csrf'] ... ->equals($_SESSION['csrf'])
     *
     * @param mixed $value
     * @param string|null $message
     * @param bool $strict
     * @return $this
     */
    public function equal($value, ?string $message=null, bool $strict=true)
    {
        $this->emptyMessage($message, "This field must be equal $value");
        $this->rules[] = new Rule(Rule::EQUAL, $message, ['value' => $value, 'strict' => $strict]);
        return $this;
    }

    /**
     * Check is value equals some other validator value, like password == password_repeat
     *
     * @param Validator $with
     * @param string|null $message
     * @param bool $strict
     * @return $this
     */
    public function compare(Validator $with, ?string $message=null, bool $strict=true)
    {
        $this->emptyMessage($message, "This field must be same with $with->name");
        $this->rules[] = new Rule(Rule::COMPARE, $message, ['with' => $with, 'strict' => $strict]);
        return $this;
    }

    /**
     * Checks is checkbox checked?
     *
     * @param string|null $message
     * @return $this
     */
    public function checked(?string $message=null)
    {
        $this->emptyMessage($message, "Must be checked");
        $this->rules[] = new Rule(Rule::CHECKED, $message);
        return $this;
    }

    /**
     * Custom validation rule
     *
     * @param callable      $callback        validation ( function($value): bool {} )
     * @param string|null   $message
     * @param mixed         ...$params
     * @return $this
     */
    public function custom(callable $callback, ?string $message=null, ...$params)
    {
        $this->emptyMessage($message, "Not bypass custom rule");
        $this->rules[] = new Rule(Rule::CUSTOM, $message, ['callback' => $callback, 'params' => $params]);
        return $this;
    }

    /**
     * Validates string for minimum chars
     *
     * @param int $len
     * @param string|null $message
     * @return $this
     */
    public function minimumLength(int $len, ?string $message = null)
    {
        $this->emptyMessage($message, "Must be at last $len characters long");
        $this->rules[] = new Rule(Rule::MINLENGTH, $message, ['len' => $len]);
        return $this;
    }

    /**
     * Validate string for maximum chars
     *
     * @param int $len
     * @param string|null $message
     * @return $this
     */
    public function maximumLength(int $len, ?string $message=null)
    {
        $this->emptyMessage($message, "Must be at most $len characters long");
        $this->rules[] = new Rule(Rule::MAXLENGTH, $message, ['len' => $len]);
        return $this;
    }

    /**
     * Validate string for /^[a-z]?%/i
     *
     * @param string|null $message
     * @return $this
     */
    public function alpha(?string $message=null)
    {
        $this->emptyMessage($message, "Only alphabetic characters allowed");
        $this->rules[] = new Rule(Rule::ALPHA, $message);
        return $this;
    }

    /**
     * Validate string for valid email address
     *
     * @param string|null $message
     * @return $this
     */
    public function validMail(?string $message=null)
    {
        $this->emptyMessage($message, "Must be an valid email address");
        $this->rules[] = new Rule(Rule::MAIL_REGEX, $message);
        return $this;
    }

    /**
     * Validates value for valid integer or float
     *
     * @param string|null $message
     * @return $this
     */
    public function isNumeric(?string $message=null)
    {
        $this->emptyMessage($message, "Must be an number or float");
        $this->rules[] = new Rule(Rule::NUMERIC, $message);
        return $this;
    }

    /**
     * Validates value for valid integer
     *
     * @param string|null $message
     * @return $this
     */
    public function isInt(?string $message=null)
    {
        $this->emptyMessage($message, "Must be an number");
        $this->rules[] = new Rule(Rule::INTEGER, $message);
        return $this;
    }

    /**
     * Validates value for valid float
     *
     * @param string|null $message
     * @return $this
     */
    public function isFloat(?string $message=null)
    {
        $this->emptyMessage($message, "Must be an floating number");
        $this->rules[] = new Rule(Rule::FLOAT, $message);
        return $this;
    }

    /**
     * Validates value for positive value   1..MAX_INT
     *
     * @param string|null $message
     * @return $this
     */
    public function isPositiveInteger(?string $message=null)
    {
        $this->emptyMessage($message, 'Must be an positive number');
        $this->rules[] = new Rule(Rule::POSITIVE_INT, $message);
        return $this;
    }

    /**
     * Validates value for positive floating value > 0
     *
     * @param string|null $message
     * @return $this
     */
    public function isPositiveFloat(?string $message=null)
    {
        $this->emptyMessage($message, 'Must be positive float');
        $this->rules[] = new Rule(Rule::POSITIVE_FLOAT, $message);
        return $this;
    }

    /**
     * Validates is value between specific range
     *
     * @param int $min
     * @param int $max
     * @param string|null $message
     * @return $this
     */
    public function rangeInt(int $min, int $max, ?string $message=null)
    {
        $this->emptyMessage($message, "Must be in range between $min and $max");
        $this->rules[] = new Rule(Rule::INTEGER_RANGE, $message, ['min' => $min, 'max' => $max]);
        return $this;
    }

    /**
     * Validates numbers and floats for value not less than
     *
     * @param int $min
     * @param string|null $message
     * @return $this
     */
    public function minimumInt(int $min, ?string $message = null)
    {
        $this->emptyMessage($message, "Must be greater or equal $min");
        $this->rules[] = new Rule(Rule::INTEGER_MIN, $message, ['min' => $min]);
        return $this;
    }

    /**
     * Validates numbers and floats for value not greater than
     *
     * @param int $max
     * @param string|null $message
     * @return $this
     */
    public function maximumInt(int $max, ?string $message = null)
    {
        $this->emptyMessage($message, "Must be less or equal $max");
        $this->rules[] = new Rule(Rule::INTEGER_MAX, $message, ['max' => $max]);
        return $this;
    }

    /**
     * Main validation function
     *
     * @return bool
     */
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
                    if ( ! is_numeric($this->value)) {
                        $error($rule);
                    }
                    break;

                case Rule::INTEGER:
                    if ( filter_var($this->value, FILTER_VALIDATE_INT) === false ) {
                        $error($rule);
                    }
                    break;

                case Rule::FLOAT:
                    if ( filter_var($this->value, FILTER_VALIDATE_FLOAT) === false ) {
                        $error($rule);
                    }
                    break;

                case Rule::POSITIVE_INT:
                    if ((int) $this->value != $this->value || $this->value < 1) {
                        $error($rule);
                    }
                    break;

                case Rule::POSITIVE_FLOAT:
                    if ( ! ((float) $this->value == $this->value && $this->value > 0)) {
                        $error($rule);
                    }
                    break;

                case Rule::INTEGER_RANGE: // [min , max]
                    $options = [ "min_range" => $rule->min, "max_range" => $rule->max ];
                    if ( filter_var($this->value, FILTER_VALIDATE_INT, ["options" => $options]) === false ) {
                        $error($rule);
                    }
                    break;

                case Rule::INTEGER_MIN: // [min]
                    $options = [ "min_range" => $rule->min ];
                    if ( filter_var($this->value, FILTER_VALIDATE_INT, ["options" => $options]) === false ) {
                        $error($rule);
                    }
                    break;

                case Rule::INTEGER_MAX: // [max]
                    $options = [ "max_range" => $rule->max ];
                    if ( filter_var($this->value, FILTER_VALIDATE_INT, ["options" => $options]) === false ) {
                        $error($rule);
                    }
                    break;

                case Rule::IS_DATE:
                case Rule::IS_TIME:
                case Rule::IS_LONGTIME:
                case Rule::IS_DATETIME:
                case Rule::IS_LONGDATE:
                    $error(new Rule(0, 'Sorry rule not yet implemented'));
                    break;

                default:
                    $error(new Rule(0, 'Ivalid rule type!'));
                    break;

            }
        }

        return $this->valid;
    }


    /**
     * Returns error list
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Returns name given in constructor
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * returns value
     *
     * @return string|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Little helper for message null check
     *
     * @param $message
     * @param $set
     */
    private function emptyMessage(&$message, $set)
    {
        if ($message === null) {
            $message = $set;
        }
    }

}