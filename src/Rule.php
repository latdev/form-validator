<?php
namespace Latdev\Validation;

use stdClass;

class Rule extends stdClass
{

    const REQUIRED      = 0x1000;
    const EQUAL         = 0x1001;
    const COMPARE       = 0x1002;
    const CHECKED       = 0x1005;
    const CUSTOM        = 0x1100;

    const MINLENGTH     = 0x2001;
    const MAXLENGTH     = 0x2002;
    const ALPHA         = 0x2003;
    const MAIL_REGEX    = 0x2010;
    const NUMERIC       = 0x4000;
    const INTEGER       = 0x4001;
    const FLOAT         = 0x4002;

    const POSITIVE_INT  = 0x4005;
    const POSITIVE_FLOAT= 0x4006;

    const INTEGER_RANGE = 0x4010;
    const INTEGER_MIN   = 0x4011;
    const INTEGER_MAX   = 0x4012;




    const IS_DATE       = 0x5051; // todo : represent this DD-MM-YYYY
    const IS_TIME       = 0x5052; // todo : represent this HH:MM
    const IS_LONGTIME   = 0x5053; // todo : represent this HH:MM:SS
    const IS_DATETIME   = 0x5054; // todo : represent this DD-MM-YYYY HH:mm
    const IS_LONGDATE   = 0x5055; // todo : represent this DD-MM-YYYY HH:mm:ss


    public $type;
    public $message;

    /**
     * Rule constructor.
     *
     * @param int $type             Rule type constant
     * @param string $message       Message given when rule fails
     * @param array|null $params    Other parameters for specific rules
     */
    function __construct(int $type, string $message, ?array $params=null)
    {
        $this->type = $type;
        $this->message = $message;
        if ($params !== null) {
            foreach ($params as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * Rule type
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Rule message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}