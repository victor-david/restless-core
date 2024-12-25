<?php declare(strict_types=1);
namespace Restless\Database;

/**
 * Represents a data table.
 *
 * @abstract
 */
abstract class DataTable extends DataObject
{
    private const OP_NOT_IMPLEMENTED = 'Operation not implemented';

    /**
     * Name of the table
     *
     * @var string
     */
    protected $table;

    /**
     * Additional fields specification.
     *
     * @var string
     */
    protected $fields;

    /**
     * Where condition
     *
     * @var string
     */
    protected $where;

    /**
     * Query parms
     *
     * @var array
     */
    protected $parms;

    /**
     * Order by specification
     *
     * @var string
     */
    protected $orderBy;

    /**
     * Max for selection, default 1
     *
     * @var int
     */
    protected $max;

    /**
     * Primary key name, default: id
     *
     * @var string
     */
    protected $primaryKey;

    /**
     * Default id for selectById(), default: id
     *
     * @var string
     */
    protected $defaultId;

    /**
     * Selection acount after a select op, before select op is -1
     *
     * @var int
     */
    protected $selectCount;

    /**
     * @var array
     */
    public $data = [];

    protected function __construct(CoreDatabasePdo $db)
    {
        parent::__construct($db);
        $this->parms = [];
        $this->max = 1;
        $this->primaryKey = 'id';
        $this->defaultId = 'id';
        $this->data = [];
        $this->selectCount = -1;
    }

    /**
     * Selects a single record by an id.
     *
     * This method is a shortcut for where(..)->parms(..)->max(1)->select()
     * Derived classes can set $this->defaultId to specify which field is used
     * by default when calling this method.
     *
     * @param mixed $id
     * @param string $field (default null uses $this->defaultId)
     *
     * @return static
     */
    public function selectById($id, ?string $field = null): static
    {
        $field = $field ?: $this->defaultId;
        return $this->where("$field=?")->parms($id)->max(1)->select();
    }

    /**
     * Sets a single additional fields specification.
     * Unlike the underlying PDO fields specifier, this method
     * is not additive; multiple calls replace the previous specification.
     *
     * @param mixed $value
     *
     * @return static
     */
    public function fields($value): static
    {
        if ($value)
        {
            $this->fields = $value;
        }
        return $this;
    }

    /**
     * Sets the where condition.
     *
     * @param string|null $value
     *
     * @return static
     */
    public function where($value): static
    {
        $this->where = $value;
        return $this;
    }

    /**
     * Adds parameters for token substitution
     *
     * @param mixed ...$parms
     *
     * @return static
     */
    public function parms(...$parms): static
    {
        $this->parms = $parms;
        return $this;
    }

    /**
     * Sets the order by
     *
     * @param string $value
     *
     * @return static
     */
    public function orderBy(string $value): static
    {
        $this->orderBy = $value;
        return $this;
    }

    /**
     * Sets the maximum number of results
     *
     * @param int $value
     *
     * @return static
     */
    public function max(int $value): static
    {
        $this->max = $value;
        return $this;
    }

    /**
     * Populates the specified properties with their assigned values.
     *
     * @param array|object $values An associative array or an object that contains what to populate.
     *
     * @return static
     */
    public function setProperties($values): static
    {
        if (is_array($values))
        {
            $values = (object)$values;
        }
        if (is_object($values))
        {
            $this->populate($values);
        }
        return $this;
    }

    /**
     * Gets the count of selected objects. Possible return values are:
     *
     * -1 select not executed;
     *  0 selected executed with zero results;
     *  1 select specified max of 1 and got it, values are in the object properties;
     *  greater than 1 values are in $this->data.
     *
     * @return int
     */
    public function getSelectCount(): int
    {
        return (int)$this->selectCount;
    }

    /**
     * Gets a boolean value that indicates if this is an object
     * that has been found and populated. Checks for the presence
     * of $this->primaryKey
     *
     * @return bool
     */
    public function isSelectedObject(): bool
    {
        $id = $this->primaryKey;
        if (property_exists($this, $id))
        {
            return (isset($this->$id) && !empty($this->$id));
        }
        return false;
    }

    /**
     * Throws an exception if this object is considered empty, that is if
     * $this->getSelectCount() does not equal one.
     *
     * @param string|null $msg The message to throw if empty, or null for default.
     *
     * @return static
     */
    public final function throwIfEmpty(?string $msg = null): static
    {
        if ($this->getSelectCount() != 1)
        {
            if (!$msg)
            {
                $msg = 'Data returned an empty result (item does not exist)';
            }
            throw new \Exception($msg);
        }
        return $this;
    }

    /**
     * Helper method for clarity, returns $this->max(1)->select()
     *
     * @return static
     */
    public function selectSingle(): static
    {
        return $this->max(1)->select();
    }

    /**
     * Helper method for clarify, returns $this->max($max)->select()
     *
     * @param int $max (default zero, no max)
     *
     * @return static
     */
    public function selectMultiple(int $max = 0): static
    {
        return $this->max($max)->select();
    }

    /* Derived classes override these methods depending on which ops they support */
    public function select():self {throw new \Exception(self::OP_NOT_IMPLEMENTED);}
    public function insert():self {throw new \Exception(self::OP_NOT_IMPLEMENTED);}
    public function update(array $fields = []):self {throw new \Exception(self::OP_NOT_IMPLEMENTED);}
    public function delete():self {throw new \Exception(self::OP_NOT_IMPLEMENTED);}


    /**
     * Begins a select operation.
     *
     * @param string $table
     * @param string|null $alias
     *
     * @return \Restless\Database\PDOQueryObject
     */
    protected function selectp(string $alias = null): PDOQueryObject
    {
        return $this->db->selectFrom($this->table, $alias);
    }

    /**
     * Begins an insert operation.
     *
     * @return \Restless\Database\PDOQueryObject
     */
    protected function insertp(): PDOQueryObject
    {
        return $this->db->insert($this->table);
    }

    /**
     * Begins an update operation.
     *
     * @return \Restless\Database\PDOQueryObject
     */
    protected function updatep(): PDOQueryObject
    {
        return $this->db->update($this->table);
    }

    /**
     * Begins a delete operation.
     *
     * @return \Restless\Database\PDOQueryObject
     */
    protected function deletep(): PDOQueryObject
    {
        return $this->db->delete($this->table);
    }

    /**
     * Begins an ad hoc operation.
     *
     * @param string $sql
     *
     * @return \Restless\Database\PDOQueryObject
     */
    protected function adhoc(string $sql): PDOQueryObject
    {
        return $this->db->adhoc($sql);
    }

    /**
     * Processes the pdo statement.
     *
     * Examines $this->max to determine whether to populate instance properties directly (single select)
     * orto populate $this->data (array, multiple select) - and sets $this->selectCount accordingly.
     *
     * @param \PDOStatement $statement
     */
    protected function processPdoStatement(\PDOStatement $statement)
    {
        if ($this->max == 1)
        {
            $this->populate($statement->fetchObject());
        }
        else
        {
            $this->data = $statement->fetchAll(\PDO::FETCH_OBJ);
            $this->selectCount = count($this->data);
        }
    }

    /**
     * Populates object properties.
     *
     * Override (call parent) if you need property name transformations.
     *
     * @param mixed $obj
     *
     * @return static
     */
    protected function populate($obj): static
    {
        $this->selectCount = 0;
        /* if select statement got no records, $obj is not an object */
        if (is_object($obj))
        {
            foreach ($obj as $property => $value)
            {
                if (property_exists($this, $property))
                {
                    $this->$property = $value;
                }
            }
            $this->selectCount = 1;
        }
        return $this;
    }

    /**
     * Gets an associative array suitable for updating based on the provider field names.
     * Used by derived classes to create an array in their update() or insert() methods.
     *
     * @param array $fields
     *
     * @return array
     */
    protected final function getUpdateData(array $fields): array
    {
        $data = [];
        foreach ($fields as $prop)
        {
            if (property_exists($this, $prop))
            {
                $data[$prop] = $this->$prop;
            }
        }
        return $data;
    }

    /**
     * Establishes a default where condition of 'id=?' with the specified id
     * if no where condition has been defined. If a where condition has already
     * been defined, this method does nothing.
     */
    protected final function setDefaultWhereIf($id)
    {
        if (!$this->where)
        {
            $this->where('id=?')->parms($id);
        }
    }

    /**
     * Throws an exception if any of the specified properties evaluate to false.
     *
     * If all specified properties pass evaluation, returns an array of the specified
     * properties with their corresponding values.
     *
     * @param array $props
     * @param array|null $messages
     *
     * @return array
     * @throws \Exception
     */
    protected final function createFilteredOrThrow(array $props, $messages = null) : array
    {
        $idx = 0;
        $result = [];
        foreach ($props as $prop)
        {
            if (!$this->evaluateProperty($prop))
            {
                $msg = "Property [$prop] is missing";
                if (is_array($messages) && !empty($messages[$idx]))
                {
                    $msg = $messages[$idx];
                }
                throw new \Exception($msg);
            }
            $result[$prop] = $this->$prop;
            $idx++;
        }
        return $result;
    }

    /**
     * Gets a truthy value that indicates if the specified property is considered valid.
     * Returns 1 if the property exists and is set; otherwise zero.
     * Override if you need other logic.
     *
     * @param mixed $name
     *
     * @return int 0|1
     */
    protected function evaluateProperty($name): int
    {
        return property_exists($this, $name) && isset($this->$name) ? 1 : 0;
    }

    /**
     * Throws an exception if $this->selectCount == -1, indicating
     * that a select has not yet been performed
     *
     * @throws \Exception
     */
    protected function throwIfNotSelected()
    {
        if ($this->getSelectCount() == -1)
        {
            throw new \Exception($this->getClass() . ': not selected');
        }
    }
}
?>