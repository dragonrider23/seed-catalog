<?php

namespace SC;

use PDO;
use PDOStatement;

class SC
{
    private $PDO; // PDO connection
    public $fkEnding = '_id'; // Ending for FK names
    private static $instance; // SC instance
    public $escapeChar = ''; // Character to escape identifiers

    // Prevent spawning
    private function __construct() {}
    private function __clone() {}
    public function __destruct()
    {
        $this->PDO = null;
        return;
    }

    /**
     * Takes the same parameters as the PDO constructor.
     * @link http://php.net/manual/en/pdo.construct.php
     * @param string $dsn
     * @param string $username [optional]
     * @param string $password [optional]
     * @param array $options [optional]
     */
    public static function connect($type = '', $host = '', $dbname = '', $username = null, $password = null, array $options = array())
    {
        if (!self::$instance) {
            if (!$type && !$host && !$dbname) {
                return false;
            }

            self::$instance = new self();
            $dsn = "{$type}:host={$host};dbname={$dbname}";
            $PDO = new PDO($dsn, $username, $password, $options);

            if (!$PDO) {
                return false;
            }

            self::$instance->PDO = $PDO;

            switch($PDO->getAttribute(PDO::ATTR_DRIVER_NAME)) {
                case 'pgsql':
                case 'sqlsrv':
                case 'dblib':
                case 'mssql':
                case 'sybase':
                case 'firebird':
                    self::$instance->escapeChar = '"';
                case 'mysql':
                case 'sqlite':
                case 'sqlite2':
                default:
                    self::$instance->escapeChar = '`';
            }
        }

        return self::$instance;
    }

    /**
     * @param string $statement
     * @param array $parameters
     * @return array
     */
    public function read($statement, array $parameters = array())
    {
        $PDOStatement = $this->execute($statement, $parameters);

        $Records = $PDOStatement->fetchAll(PDO::FETCH_ASSOC);

        return $Records;
    }

    /**
     * @param string $statement
     * @param array $parameters
     * @return array
     */
    public function readField($statement, array $parameters = array())
    {
        $PDOStatement = $this->execute($statement, $parameters);

        $Record = $PDOStatement->fetch(PDO::FETCH_COLUMN);

        if ($Record === false) {
            return;
        }

        return $Record;
    }

    /**
     * @param string $statement
     * @param array $parameters
     * @return array
     */
    public function readFields($statement, array $parameters = array())
    {
        $PDOStatement = $this->execute($statement, $parameters);

        $fields = $PDOStatement->fetchAll(PDO::FETCH_COLUMN);

        return $fields;
    }

    /**
     * @param string $statement
     * @param array $parameters
     * @return array
     */
    public function readRecord($statement, array $parameters = array())
    {
        $PDOStatement = $this->execute($statement, $parameters);

        $Record = $PDOStatement->fetch(PDO::FETCH_ASSOC);

        if ($Record === false) {
            return;
        }

        return $Record;
    }

    /**
     * @param string $table
     * @param int $id
     * @return array
     */
    public function readItem($table, $id)
    {
        $Record = $this->find($table)
            ->whereEqual('id', $id)
            ->readRecord();

        return $Record;
    }

    /**
     * @param string $statement
     * @param array $parameters
     * @return int
     */
    public function update($statement, array $parameters = array())
    {
        $PDOStatement = $this->execute($statement, $parameters);

        $impactedRecordCount = $PDOStatement->rowCount();

        return $impactedRecordCount;
    }

    /**
     * @param string $table
     * @param int $id
     * @param array $Data
     * @return int
     */
    public function updateItem($table, $id, array $Data)
    {
        $impactedRecordCount = $this->find($table)
            ->whereEqual('id', $id)
            ->update($Data);

        return $impactedRecordCount;
    }

    /**
     * @param string $table
     * @param array $Data
     * @return int
     */
    public function createItem($table, array $Data)
    {
        $statement = "INSERT INTO {$this->escapeChar}{$table}{$this->escapeChar}";
        $fields = '';
        $values = '';

        $parameters = array();

        foreach ($Data as $name => $value) {
            $fields .= "{$this->escapeChar}$name{$this->escapeChar}, ";
            $values .= '?, ';
            $parameters []= $value;
        }

        $fields = rtrim($fields, ', ');
        $values = rtrim($values, ', ');
        $statement = "{$statement} ({$fields}) VALUES ({$values})";

        $this->execute($statement, $parameters);

        $lastId = $this->lastId();

        return $lastId;
    }

    /**
     * @param string $table
     * @param int $id
     * @return int
     */
    public function deleteItem($table, $id)
    {
        $impactedRecordCount = $this->find($table)
            ->whereEqual('id', $id)
            ->delete();

        return $impactedRecordCount;
    }

    /**
     * @param string $table
     * @return Collection
     */
    public function find($table)
    {
        $Collection = new Collection($this, $table);

        return $Collection;
    }

    /**
     * @param string $statement
     * @param array $parameters
     * @return PDOStatement
     * @throws Exception
     */
    public function execute($statement, array $parameters = array())
    {
        $PDOStatement = $this->PDO->prepare($statement);

        $successful = $PDOStatement->execute($parameters);

        if (!$successful) {
            $errorInfo = $PDOStatement->errorInfo();
            $errorCode = $PDOStatement->errorCode();
            throw new SCException($errorInfo[2].': '.$statement, $errorCode);
        }

        return $PDOStatement;
    }

    /**
     * @return int
     */
    public function lastId()
    {
        $lastInsertId = (int) $this->PDO->lastInsertId();

        return $lastInsertId;
    }

    /**
     * @return PDO
     */
    public function pdo()
    {
        $PDO = $this->PDO;

        return $PDO;
    }

    public function getEscapeQuote()
    {
        return $this->escapeChar;
    }
}
