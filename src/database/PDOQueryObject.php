<?php declare(strict_types=1);
namespace Restless\Database;
use Restless\Core\OpenObject;
use Closure;
use PDO;
use PDOStatement;
use Exception;

/**
 * Represents a query object
*/
class PDOQueryObject
{
    const SELECT   = 1;
    const INSERT   = 2;
    const UPDATE   = 3;
    const DELETE   = 4;
    const ADHOC    = 5;

    private $connection;
    private $table;
    private $alias;
    private $type;
    private $handler;

    private $fields;
    private $join;
    private $data;
    private $where;
    private $group;
    private $having;
    private $order;
    private $limit;
    private $parms;
    private $types;

    /**
     * Creates a new instance of this class.
     *
     * @param PDO $connection
     * @param string $table (for self::ADHOC) is command string like 'SHOW TABLE STATUS'
     * @param string|null $alias
     * @param int $type
     * @param Closure $handler
     */
    public function __construct(PDO $connection, string $table, string|null $alias, int $type, Closure $handler)
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->alias = $alias ?? 'T1';
        $this->type = $type;
        $this->handler = $handler;
        $this->parms = [];
        $this->types = [];
        $this->join = [];
    }

    /**
     * Adds fields to a SELECT
     *
     * @param string|null $fields
     *
     * @return PDOQueryObject
     * @throws Exception
     */
    public function fields(string|null $fields): self
    {
        if ($this->type != self::SELECT && $this->type != self::INSERT)
        {
            throw new Exception('Type must be select or insert [fields]');
        }
        if ($fields)
        {
            if ($this->fields)
            {
                $this->fields .= ',';
            }
            $this->fields .= $fields;
        }
        return $this;
    }

    /**
     * When $this->type is SELECT, adds a JOIN
     *
     * @param string $table The name of the table to join
     * @param string|null $alias The alias for the joined table. If null, will assign 'T2' (for first join), 'T3' (for 2nd join), etc.
     * @param string $on How the tables are joined, ex: 'T1.catid=T2.id'
     * @param string $type The type of join (default LEFT)
     *
     * @return PDOQueryObject
     * @throws Exception
     */
    public function join(string $table, string|null $alias, string $on, string $type = 'LEFT'): self
    {
        if ($this->type != self::SELECT)
        {
            throw new Exception('Type must be select [join]');
        }

        $alias = $alias ?? sprintf('T%s', count($this->join)+2);
        $this->join[] = (object)['table' => $table, 'alias' => $alias, 'type' => $type, 'on' => $on];
        return $this;
    }

    /**
     * Used with INSERT or UPDATE to provide the data
     *
     * @param string|array|OpenObject $data
     *
     *  This method accepts a string like 'color=?,size=?', an associative array
     *  like ['color' => 'blue','size' => 512], or a OpenObject like $obj->color, $obj->size.
     *  When using INSERT, you must pass either an array or a OpenObject; a string won't work.
     *
     *  When passing an array or a OpenObject, the keys will be extracted, the '?' tokens created,
     *  and the parms adjusted from the values of the array or OpenObject. You don't need to add the
     *  values via the $this->parms(...$parms) method. The only values you need to add via $this->parms()
     *  are those used in $this->where()
     *
     *  Examples:
     *    $this->update('mytable')->data($myArray)->where('id=?')->parms($id)->execute();
     *    $this->insert('mytable')->data($myArray)->execute(); (no ->where() or ->parms() needed)
     *
     *  When using UPDATE, a call to $this->where() is required. This is a
     *  sanity/safety check to prevent accidentally updating the entire table.
     *  If you really do want to update the entire table, you need to express
     *  it explicitly with $this->where('1=1').
     *
     * @return $this
     * @throws Exception
     */
    public function data(string|array|OpenObject $data): self
    {
        if ($this->type != self::INSERT && $this->type != self::UPDATE)
        {
            throw new Exception('Type must be insert or update [data]');
        }
        $this->data = $data;
        return $this;
    }

    /**
     * Adds a WHERE condition
     *
     * @param string $where
     *
     * @return $this
     */
    public function where(string $where): self
    {
        $this->where = $where;
        return $this;
    }

    /**
     * Adds a GROUP BY clause
     *
     * @param string $group
     *
     * @return $this
     * @throws Exception
     */
    public function groupBy(string $group): self
    {
        if ($this->type != self::SELECT)
        {
            throw new Exception('Type must be select [group]');
        }
        $this->group = $group;
        return $this;
    }

    /**
     * Adds a HAVING clause
     *
     * @param string $having
     *
     * @return $this
     */
    public function having(string $having): self
    {
        $this->having = $having;
        return $this;
    }

    /**
     * Adds an ORDER BY
     * @param string $order
     *
     * @return $this
     */
    public function orderBy(string $order): self
    {
        $this->order = $order;
        return $this;
    }

    /**
     * Adds a LIMIT specification
     *
     * @param string $limit. Default is '1'
     *
     * @return $this
     */
    public function limit(string $limit = '1'): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Adds parameters to used for token substitution
     *
     * @param mixed ...$parms
     *
     * @return $this
     */
    public function parms(mixed ...$parms): self
    {
        $this->parms = $parms;
        return $this;
    }

    /**
     * Adds type that correspond to the parameters
     *
     * @param mixed ...$types
     *
     * @return $this
     */
    public function types(mixed ...$types): self
    {
        $this->types = $types;
        return $this;
    }

    /**
     * Gets the constructed SQL statement plus arguments. Debugging aide
     *
     * @return string
     * @throws Exception
     */
    public function sql(): string
    {
        $sql = $this->buildSql();
        $parms = '';
        $pos = 1;
        foreach ($this->parms as $p)
        {
            $parms .= sprintf('p%s->%s ', $pos, $p);
            $pos++;
        }
        return sprintf('%s [%s]', $sql, $parms);
    }

    /**
     * Gets a OpenObject
     *
     *  This method calls $this->execute()->fetchAll() and
     *  then constructs a OpenObject if the returned row
     *  count is one. Otherwise, it returns an empty OpenObject
     *
     *  This method differs from $this->execute()->fetchObject('OpenObject')
     *  in that the returned row count must be one.
     */
    public function fetchOpenObject(): OpenObject
    {
        $rows = $this->execute()->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) == 1)
        {
            return new OpenObject($rows[0]);
        }
        return OpenObject::EmptyRecord();
    }

    /**
     * Executes the SQL command constructed by the various other methods of this class
     *
     * @todo Types not implemented
     *
     * @return PDOStatement
     */
    public function execute(): PDOStatement
    {
        try
        {
            $sql = $this->buildSql();
            $statement = $this->connection->prepare($sql);
            if (count($this->types))
            {
                // TODO not yet implemented
            }
            else
            {
                $statement->execute($this->parms);
            }
            return $statement;
        }
        catch (Exception $e)
        {
            $method = $this->handler;
            $method($e->getMessage());
        }
    }

    private function buildSql(): string
    {
        $sql = '';
        switch ($this->type)
        {
            case self::SELECT:
                $sql = sprintf('SELECT %s FROM %s AS %s %s', $this->fields, $this->table, $this->alias, $this->getJoin());
                if ($this->where)  $sql .= sprintf(' WHERE %s', $this->where);
                if ($this->group)  $sql .= sprintf(' GROUP BY %s', $this->group);
                if ($this->having) $sql .= sprintf(' HAVING %s', $this->having);
                if ($this->order)  $sql .= sprintf(' ORDER BY %s', $this->order);
                if ($this->limit)  $sql .= sprintf(' LIMIT %s', $this->limit);
                return trim($sql);

            case self::INSERT:
                /* sanity check */
                if (!$this->data) throw new Exception('Insert must be used with data()');
                $d = $this->getInsertData();
                $sql = sprintf('INSERT INTO %s (%s) VALUES(%s)', $this->table, $d->fields, $d->tokens);
                return trim($sql);

            case self::UPDATE:
                /* sanity check */
                if (!$this->data) throw new Exception('Update must be used with data()');
                /* safety check */
                if (!$this->where) throw new Exception('Update must be used with where()');
                $sql = sprintf('UPDATE %s SET %s', $this->table, $this->getUpdateData());
                $sql .= sprintf(' WHERE %s', $this->where);
                if ($this->limit) $sql .= sprintf(' LIMIT %s', $this->limit);
                return trim($sql);

            case self::DELETE:
                /* safety check. no unconditional delete by mistake */
                if (!$this->where) throw new Exception('Delete must be used with where()');
                $sql = sprintf('DELETE FROM %s WHERE %s', $this->table, $this->where);
                if ($this->limit) $sql .= sprintf(' LIMIT %s', $this->limit);
                return trim($sql);

            case self::ADHOC:
                /* $table parm to constructor is actually the entire command. Ex: 'SHOW TABLE STATUS' */
                return trim($this->table);

            default:
                throw new Exception('Operation not implemented');
        }
    }

    private function getJoin() : string|null
    {
        if (count($this->join) == 0)
        {
            return null;
        }

        $text = '';
        foreach ($this->join as $j)
        {
            $text .= sprintf('%s JOIN %s AS %s ON (%s) ', $j->type, $j->table, $j->alias, $j->on);
        }
        return $text;
    }

    private function getInsertData() : OpenObject
    {
        if ($this->data instanceof OpenObject)
        {
            $this->data = $this->data->toArray();
        }
        if (!is_array($this->data))
        {
            throw new Exception('Insert data must be array or OpenObject');
        }

        $tokens = implode(',', array_fill(0, count($this->data), '?'));

        $fields = '';
        $max = count($this->data);
        $keys = array_keys($this->data);
        /* values must be unshifted in reverse order */
        $vals = array_reverse(array_values($this->data));

        for ($k = 0; $k < $max; $k++)
        {
            $fields .= sprintf('`%s`,', $keys[$k]);
            array_unshift($this->parms, $vals[$k]);
        }
        $fields = substr($fields, 0, -1);

        return new OpenObject(['fields' => $fields, 'tokens' => $tokens]);
    }

    private function getUpdateData()
    {
        if ($this->data instanceof OpenObject)
        {
            $this->data = $this->data->toArray();
        }
        if (is_array($this->data))
        {
            $text = '';
            $max = count($this->data);
            $keys = array_keys($this->data);
            // /* values must be unshifted in reverse order */
            $vals = array_reverse(array_values($this->data));

            for ($k = 0; $k < $max; $k++)
            {
                $text .= sprintf('`%s`=?,', $keys[$k]);
                array_unshift($this->parms, $vals[$k]);
            }
            $text = substr($text, 0, -1);
            return $text;
        }
        return $this->data;
    }
}
?>