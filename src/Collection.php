<?php

namespace SC;

class Collection
{
    private $Base;
    private $table;
    private $escapeChar = '';

    # ~

    private $parameters = array();

    private $tableClause;
    private $whereClause;
    private $groupClause;
    private $limitClause;
    private $orderClause;

    /**
     * @param Base $Base
     * @param string $table
     */
    public function __construct(SC $Base, $table)
    {
        $this->Base = $Base;
        $this->table = $table;
        $this->escapeChar = $Base->getEscapeQuote();
        $this->fkEnding = $Base->getFkEnding();
        $this->tableClause = "{$this->escapeChar}{$table}{$this->escapeChar}";
        $this->whereClause = '1';
    }

    #
    # Relationships
    #

    /**
     * @param string $table
     * @param string $foreignKey
     * @return $this
     */
    public function has($table, $foreignKey = null)
    {
        $foreignKey = $foreignKey ?: $this->table.$this->fkEnding;

        $this->tableClause .= " LEFT JOIN {$this->escapeChar}{$table}{$this->escapeChar} ON {$this->escapeChar}{$this->table}{$this->escapeChar}.{$this->escapeChar}id{$this->escapeChar} = {$this->escapeChar}{$table}{$this->escapeChar}.{$this->escapeChar}$foreignKey{$this->escapeChar}";

        return $this;
    }

    /**
     * @param string $table
     * @param string $foreignKey
     * @return $this
     */
    public function belongsTo($table, $foreignKey = null)
    {
        $foreignKey = $foreignKey ?: $table.$this->fkEnding;

        $this->tableClause .= " LEFT JOIN {$this->escapeChar}{$table}{$this->escapeChar} ON {$this->escapeChar}{$this->table}{$this->escapeChar}.{$this->escapeChar}$foreignKey{$this->escapeChar} = {$this->escapeChar}{$table}{$this->escapeChar}.{$this->escapeChar}id{$this->escapeChar}";

        return $this;
    }

    /**
     * @param string $table
     * @return $this
     */
    public function hasAndBelongsTo($table)
    {
        $tables = array($this->table, $table);

        sort($tables);

        $joinTable = join('_', $tables);

        $aKey = $this->table.$this->fkEnding;
        $bKey = $table.$this->fkEnding;

        $this->tableClause .= "
			LEFT JOIN {$this->escapeChar}{$joinTable}{$this->escapeChar} ON {$this->escapeChar}{$this->table}{$this->escapeChar}.{$this->escapeChar}id{$this->escapeChar} = {$this->escapeChar}{$joinTable}{$this->escapeChar}.{$this->escapeChar}$aKey{$this->escapeChar}
			LEFT JOIN {$this->escapeChar}{$table}{$this->escapeChar} ON {$this->escapeChar}{$table}{$this->escapeChar}.{$this->escapeChar}id{$this->escapeChar} = {$this->escapeChar}{$joinTable}{$this->escapeChar}.{$this->escapeChar}$bKey{$this->escapeChar}";

        return $this;
    }

    #
    # Conditions
    #

    /**
     * @param string $condition
     * @param array $values
     * @return $this
     */
    public function where($condition, array $values = array())
    {
        $this->whereClause .= " AND $condition";

        foreach ($values as $value)
        {
            $this->parameters []= $value;
        }

        return $this;
    }

    /**
     * @param string $field
     * @param $value
     * @param bool $reverse
     * @return $this
     */
    public function whereEqual($field, $value, $reverse = false)
    {
        $field = $this->escapeField($field);

        $operator = $reverse ? '!=' : '=';

        $this->whereClause .= " AND $field $operator ?";

        $this->parameters []= $value;

        return $this;
    }

    /**
     * @param string $field
     * @param $value
     * @return $this
     */
    public function whereNotEqual($field, $value)
    {
        $this->whereEqual($field, $value, true);

        return $this;
    }

    /**
     * @param string $field
     * @param array $values
     * @param bool $reverse
     * @return $this
     */
    public function whereIn($field, array $values, $reverse = false)
    {
        $field = $this->escapeField($field);

        $operator = $reverse ? 'NOT IN' : 'IN';

        $this->whereClause .= " AND $field $operator (";

        foreach ($values as $value)
        {
            $this->whereClause .= '?, ';

            $this->parameters []= $value;
        }

        $this->whereClause = substr_replace($this->whereClause, ')', - 2);

        return $this;
    }

    /**
     * @param string $field
     * @param array $values
     * @return $this
     */
    public function whereNotIn($field, array $values)
    {
        $this->whereIn($field, $values, true);

        return $this;
    }

    /**
     * @param string $field
     * @param bool $reverse
     * @return $this
     */
    public function whereNull($field, $reverse = false)
    {
        $field = $this->escapeField($field);

        $operator = $reverse ? 'IS NOT' : 'IS';

        $this->whereClause .= " AND $field $operator NULL";

        return $this;
    }

    /**
     * @param string $field
     * @return $this
     */
    public function whereNotNull($field)
    {
        $this->whereNull($field, true);

        return $this;
    }

    #
    # GROUP BY
    #

    /**
     * @param string $group
     * @return $this
     */
    public function group($group)
    {
        $this->groupClause = $group;

        return $this;
    }

    #
    # LIMIT
    #

    /**
     * @param string $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->limitClause = $limit;

        return $this;
    }

    #
    # ORDER BY
    #

    /**
     * @param string $order
     * @return $this
     */
    public function order($order)
    {
        $this->orderClause = $order;

        return $this;
    }

    /**
     * @param string $field
     * @return $this
     */
    public function orderAsc($field)
    {
        $field = $this->escapeField($field);

        $this->orderClause = $field . ' ASC';

        return $this;
    }

    /**
     * @param string $field
     * @return $this
     */
    public function orderDesc($field)
    {
        $field = $this->escapeField($field);

        $this->orderClause = $field . ' DESC';

        return $this;
    }

    #
    #
    # Actions
    #
    #

    /**
     * @param string $selectExpression
     * @return array
     */
    public function read($selectExpression = null)
    {
        $statement = $this->composeReadStatement($selectExpression);

        $Records = $this->Base->read($statement, $this->parameters);

        return $Records;
    }

    /**
     * @param string $selectExpression
     * @return array
     */
    public function readRecord($selectExpression = null)
    {
        $statement = $this->composeReadStatement($selectExpression);

        $Record = $this->Base->readRecord($statement, $this->parameters);

        return $Record;
    }

    /**
     * @param string $selectExpression
     * @return string
     */
    public function readField($selectExpression = null)
    {
        $statement = $this->composeReadStatement($selectExpression);

        $field = $this->Base->readField($statement, $this->parameters);

        return $field;
    }

    /**
     * @param string $selectExpression
     * @return array
     */
    public function readFields($selectExpression = null)
    {
        $statement = $this->composeReadStatement($selectExpression);

        $fields = $this->Base->readFields($statement, $this->parameters);

        return $fields;
    }

    /**
     * @return int
     */
    public function count()
    {
        $count = (int) $this->readField('COUNT(*)');

        return $count;
    }

    /**
     * @param array $Data
     * @return int
     */
    public function update(array $Data)
    {
        $statement = "UPDATE {$this->escapeChar}{$this->table}{$this->escapeChar} SET ";

        $fields = array_keys($Data);

        foreach ($fields as $field)
        {
            $statement .= "{$this->escapeChar}{$field}{$this->escapeChar} = ?, ";
        }

        $statement = substr_replace($statement, " WHERE $this->whereClause", - 2);

        $this->limitClause and $statement .= " LIMIT $this->limitClause";

        $parameters = array_values($Data);
        $parameters = array_merge($parameters, $this->parameters);

        $impactedRecordCount = $this->Base->update($statement, $parameters);

        return $impactedRecordCount;
    }

    /**
     * @return int
     */
    public function delete()
    {
        $statement = "DELETE FROM $this->tableClause WHERE $this->whereClause";

        $this->orderClause and $statement .= " ORDER BY $this->orderClause";
        $this->limitClause and $statement .= " LIMIT $this->limitClause";

        $impactedRecordCount = $this->Base->update($statement, $this->parameters);

        return $impactedRecordCount;
    }

    #
    # Protected
    #

    /**
     * @param string $selectExpression
     * @return string
     */
    protected function composeReadStatement($selectExpression = null)
    {
        $selectExpression = $selectExpression ?: "{$this->escapeChar}{$this->table}{$this->escapeChar}.*";

        $query = "SELECT $selectExpression FROM $this->tableClause WHERE $this->whereClause";

        $this->groupClause and $query .= " GROUP BY $this->groupClause";
        $this->orderClause and $query .= " ORDER BY $this->orderClause";
        $this->limitClause and $query .= " LIMIT $this->limitClause";

        return $query;
    }

    /**
     * @param string $field
     * @return string
     */
    protected function escapeField($field)
    {
        $field = str_replace('`', '', $field);
        $field = str_replace('.', "{$this->escapeChar}.{$this->escapeChar}", $field);
        $field = $this->escapeChar.$field.$this->escapeChar;

        return $field;
    }
}
