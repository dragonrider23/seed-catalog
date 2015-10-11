<?php
/**
 * Exception class for SC
 */
namespace SC;

class SCException extends \Exception
{
    protected $dbCode;

    public function __construct($message = '', $ansiCode = 0, $dbCode = 0)
    {
        parent::__construct($message, $ansiCode);
        $this->dbCode = $dbCode;
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code} | {$this->dbCode}]: {$this->message}\n";
    }

    public function getDbCode()
    {
        return $this->dbCode;
    }
}
