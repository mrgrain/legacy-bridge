<?php
namespace Frogsystem\Legacy\Bridge\Services;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;

/**
 * Class Database
 * This class provides several methods for database-access
 * @package Frogsystem\Legacy\Services
 * @version  0.5
 * @author   Sweil, Satans Kruemelmonster, Thoronador
 */
class Database
{
    /**
     * the connection-resource
     * @var \PDO
     */
    private $sql;

    /**
     * Table-Prefix (see inc_login.php)
     * @var mixed
     */
    private $pref;

    /**
     * the selected Database
     * @var mixed
     */
    private $db;
    /**
     * possible SQL errors
     * @var string
     */
    private $error;
    /**
     * result-resource of a query
     * @var bool
     */
    private $result = false;

    /**
     * the built query string
     * @var
     */
    private $query;


    /**
     * connects the database, saves SQL-Connection, database-name and prefix
     * @param ConnectionResolverInterface $db
     * @throws \ErrorException
     */
    public function __construct(ConnectionResolverInterface $db)
    {
        try {
            /** @var Connection $connection */
            $connection = $db->connection();
            $this->sql = $connection->getPdo();
            $this->db = $connection->getConfig('database');
            $this->pref = $connection->getConfig('prefix');
        } catch (\Exception $e) {
            $this->sql = false;
            $this->error = $e->getMessage();
            Throw new \ErrorException('No Connection to Database: ' . $e->getMessage());
        }
    }

    /**
     * Destructor closes SQL-Connection
     */
    public function __destruct()
    {
        $this->sql = NULL; //destroy PDO instance
    }

    /**
     * save sql data
     * @param $VAL
     * @return mixed
     */
    public function escape($VAL)
    {
        // save data
        if (is_numeric($VAL)) {
            if (floatval($VAL) == intval($VAL)) {
                $VAL = intval($VAL);
                settype($VAL, 'integer');
            } else {
                $VAL = floatval($VAL);
                settype($VAL, 'float');
            }
        } else {
            // \PDO::quote() also adds colon before and after the string, so we
            //  have to remove it to keep it compatible with old style.
            $VAL = substr($this->sql->quote(strval($VAL), \PDO::PARAM_STR), 1, -1);
        }

        return $VAL;
    }

    /**
     * run any query
     * @param $query_string
     * @return bool|\PDOStatement
     * @throws \ErrorException
     */
    public function doQuery($query_string)
    {
        $this->query = str_replace('{..pref..}', $this->pref, $query_string); // replace {..pref..}
        $this->result = $this->sql->query($this->query); // execute query

        if ($this->result !== false) {
            unset($this->error);
            return $this->result;
        } else {
            $this->error[0] = $this->sql->errorCode(); // error number
            $this->error[1] = $this->sql->errorInfo(); // error text
            $this->error[2] = $this->query;            // query causing the error
            Throw new \ErrorException('SQL Error: [' . $this->error[0] . '] ' . $this->error[1] . "\n" . $this->error[2]);
        }
    }

    /**
     * build options
     * @param $options
     * @return string
     */
    private function opt($options)
    {
        // init array
        $return = array();

        // set options
        if (isset($options['W']) && !empty($options['W']))
            $return[] = 'WHERE ' . $options['W'];

        if (isset($options['O']) && !empty($options['O']))
            $return[] = 'ORDER BY ' . $options['O'];

        if (isset($options['G']) && !empty($options['G']))
            $return[] = 'GROUP BY ' . $options['G'];

        if (isset($options['H']) && !empty($options['H']))
            $return[] = 'HAVING ' . $options['H'];

        if (isset($options['L']) && !empty($options['L']))
            $return[] = 'LIMIT ' . $options['L'];

        // return as string
        return ' ' . implode(' ', $return);
    }

    /**
     * build a select expression
     * Example:
     * array (
     *    array (
     *        'COL' => 'fieldname',
     *        'FROM' => 'tablealias',
     *        'AS' => 'colalias',
     *        'FUNC' => 'count',
     *        'ARG' => "last_name,', ',first_name"
     *      'MATH' => '+'
     *      'LEFT' => array (...)
     *      'RIGHT' => array (...)
     *    )
     * );
     *
     * => make := Funktion die String baut
     * => S = make(LEFT) + MATH + make(RIGHT) END;// if MATH, LEFT
     * => S = FUNC(ARG)// if FUNC && ARG
     * => S = COL // if COL == * && !FUNC && ARG
     * => S = `COL` // if !FUNC && ARG
     * => S = FROM.S  // if FROM
     * => S = FUNC(S) // if FUNC
     * => S = S AS "AS" END;// if AS
     *
     * @param $col
     * @return string
     */
    private function select_expr($col)
    {
        initstr($cols_list);
        if (isset($col['MATH'], $col['LEFT'], $col['RIGHT'])) {
            $cols_list = '(' . $this->select_expr($col['LEFT']) . ') ' . $col['MATH'] . ' (' . $this->select_expr($col['RIGHT']) . ')';
        } else {

            //=> S = FUNC(ARG)// if FUNC && ARG
            if (isset($col['FUNC'], $col['ARG'])) {
                $cols_list = $col['FUNC'] . '(' . $col['ARG'] . ')';

            } else {
                //=> S = COL || `COL`
                $cols_list = $col['COL'] == '*' ? $col['COL'] : '`' . $col['COL'] . '`';
                //=> S = FROM.S  // if FROM
                $cols_list = isset($col['FROM']) ? $col['FROM'] . '.' . $cols_list : $cols_list;
                //=> S = FUNC(S) // if FUNC
                $cols_list = isset($col['FUNC']) ? $col['FUNC'] . '(' . $cols_list . ')' : $cols_list;
            }

            //=> S = S AS "AS" // if AS
            $cols_list = isset($col['AS']) ? $cols_list . " AS '" . $col['AS'] . "'" : $cols_list;
        }
        return $cols_list;
    }

    /**
     * execute SELECT
     * @param $table
     * @param $cols
     * @param array $options
     * @param bool $distinct
     * @param bool $total_rows
     * @return bool|\PDOStatement
     * @throws \ErrorException
     */
    private function select($table, $cols, $options = array(), $distinct = false, $total_rows = false)
    {
        // empty cols
        if (empty($cols))
            Throw new \ErrorException('SQL Error: Can\'t select nothing.');
        if (is_array($table) && count($table) <= 0)
            Throw new \ErrorException('SQL Error: Can\'t select from nothing.');


        // Table list
        if (is_array($table)) {
            // Computes to FROM `table1` ONE, `table2` TWO ...
            $table_list = array();
            foreach ($table as $as => $name) {
                $table_list[] = '`' . $this->pref . $name . '` ' . $as;
            }
            $table_list = implode(', ', $table_list);
        } else {
            $table_list = '`' . $this->pref . $table . '`';
        }

        // prepare data
        if (is_array($cols)) {
            $cols_list = array();
            foreach ($cols as $col) {
                if (is_array($col)) {
                    $cols_list[] = $this->select_expr($col);
                } else {
                    $cols_list[] = '`' . $col . '`';
                }
            }

            $cols_list = implode(', ', $cols_list);

            // error
        } elseif ($cols == '*') {
            $cols_list = $cols;
        } else {
            Throw new \ErrorException('SQL Error: Cannot select because of bad data.');
        }

        // DISTINCT or not
        $select = ($distinct) ? 'SELECT DISTINCT ' : 'SELECT ';
        // Total Rows?
        $select .= ($total_rows) ? ' SQL_CALC_FOUND_ROWS' : '';

        // build query string...
        $qrystr = $select . $cols_list . ' FROM ' . $table_list . $this->opt($options);

        try {
            return $this->doQuery($qrystr); // ... and execute
        } catch (\Exception $e) {
            print_d($e->getMessage());
            return null;
        }
    }

    /**
     * get data from database
     * @param $table
     * @param $cols
     * @param array $options
     * @param bool $distinct
     * @param bool $total_rows
     * @return array
     * @throws \ErrorException
     */
    public function get($table, $cols, $options = array(), $distinct = false, $total_rows = false)
    {
        // Get Data
        $rows = array();
        $result = $this->select($table, $cols, $options, $distinct, $total_rows);
        while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }
        $num = count($rows);

        // Total rows?
        if ($total_rows) {
            $result = $this->doQuery('SELECT FOUND_ROWS()');
            list ($total_rows) = $result->fetch(\PDO::FETCH_NUM);
        } else {
            $total_rows = $num;
        }

        // Return the result
        return array(
            'data' => $rows,
            'num' => $num,
            'num_all' => $total_rows,
        );
    }

    /**
     * only a single data row
     * @param $table
     * @param $cols
     * @param array $options
     * @param int $start
     * @return array
     */
    public function getRow($table, $cols, $options = array(), $start = 0)
    {
        // Set Limit
        $options['L'] = $start . ",1";

        // Get Result
        $result = $this->get($table, $cols, $options);
        if (count($result['data']) >= 1) {
            return $result['data'][0];
        } else {
            return array();
        }
    }

    /**
     * only a single data field
     * @param $table
     * @param $field
     * @param array $options
     * @param int $start
     * @return bool
     */
    public function getField($table, $field, $options = array(), $start = 0)
    {
        // Get Result
        $result = $this->getRow($table, array($field), $options, $start);
        if (count($result) >= 1)
            return array_shift($result);
        else
            return false;
    }

    /**
     * single field by Id
     * @param $table
     * @param $field
     * @param $id
     * @param string $id_col
     * @return bool
     * @throws \Exception
     */
    public function getFieldById($table, $field, $id, $id_col = 'id')
    {
        $options = array('W' => '`' . $id_col . "`='" . $this->escape($id) . "'");

        try {
            return $this->getField($table, $field, $options);
        } catch (\Exception $e) {
            Throw $e;
        }
    }

    /**
     * Saving to DB by Id
     * existing entry => update, else => insert
     * @param $table
     * @param $data
     * @param string $id
     * @param bool $auto_id
     * @return mixed|string
     * @throws \Exception
     */
    public function save($table, $data, $id = 'id', $auto_id = true)
    {
        // Update
        if (isset($data[$id]) && $this->getFieldById($table, $id, $this->escape($data[$id]), $id) !== false) {
            try {
                return $this->updateById($table, $data, $id);
            } catch (\Exception $e) {
                throw $e;
            }

            // Insert
        } else {
            try {
                if ($auto_id) {
                    unset($data[$id]);
                }
                return $this->insertId($table, $data);
            } catch (\Exception $e) {
                throw $e;
            }
        }
    }

    /**
     * insert statement
     * @param $table
     * @param $data
     * @return bool|\PDOStatement
     * @throws \ErrorException
     */
    public function insert($table, $data)
    {
        // empty data
        if (empty($data))
            Throw new \ErrorException("SQL Error: Can't insert empty data.");

        // prepare data
        $cols = ($values = array());
        foreach ($data as $column => $value) {
            $cols[] = '`' . $column . '`';
            $values[] = "'" . $this->escape($value) . "'";
        }

        // build query string...
        $query_string = 'INSERT INTO `' . $this->getPrefix() . $table . '` (' . implode(',', $cols) . ') VALUES (' . implode(',', $values) . ')';

        return $this->doQuery($query_string); // ... and execute
    }

    /**
     * insert with returning auto increment value
     * @param $table
     * @param $data
     * @return string
     * @throws \Exception
     */
    public function insertId($table, $data)
    {
        try {
            $this->insert($table, $data);
            return $this->sql->lastInsertId();
        } catch (\Exception $e) {
            Throw $e;
        }
    }

    /**
     * update statement
     * @param $table
     * @param $data
     * @param array $options
     * @return bool|\PDOStatement
     * @throws \ErrorException
     */
    public function update($table, $data, $options = array())
    {
        // empty data
        if (empty($data))
            Throw new \ErrorException("SQL Error: Can't update empty data.");

        // prepare data
        $list = array();
        foreach ($data as $column => $value) {
            $list[] = '`' . $column . "` = '" . $this->escape($value) . "'";
        }

        // build query string...
        $query_string = 'UPDATE `' . $this->pref . $table . '` SET ' . implode(',', $list) . $this->opt($options);

        try {
            return $this->doQuery($query_string); // ... and execute
        } catch (\Exception $e) {
            print_r($e->getMessage());
            return null;
        }
    }

    /**
     * update by Id
     * @param $table
     * @param $data
     * @param string $id
     * @return mixed
     * @throws \ErrorException
     * @throws \Exception
     */
    public function updateById($table, $data, $id = "id")
    {
        // check for id
        if (!isset($data[$id]))
            Throw new \ErrorException("SQL Error: Can't update because of bad data.");

        $options = array('W' => '`' . $id . "`='" . $this->escape($data[$id]) . "'");

        try {
            $this->update($table, $data, $options);
            return $data[$id];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * delete statement
     * @param $table
     * @param array $options
     * @return bool|int
     */
    public function delete($table, $options = array())
    {
        $query_string = 'DELETE FROM `' . $this->pref . $table . '`' . $this->opt($options);

        try {
            $stmt = $this->doQuery($query_string);
            return $stmt->rowCount();
        } catch (\Exception $e) {
            print_r($e->getMessage());
            return false;
        }
    }

    /**
     * delete statement
     * @param $table
     * @param $id
     * @param string $id_col
     * @return bool|int
     */
    public function deleteById($table, $id, $id_col = 'id')
    {
        $options = array(
            'W' => '`' . $id_col . "`='" . $this->escape($id) . "'",
            'L' => '1',
        );
        return $this->delete($table, $options);
    }

    /**
     * print or return last error
     * @param bool $return
     * @return bool|string
     */
    public function error($return = false)
    {
        if (!empty($this->error)) {
            if ($return) {
                return $this->error;
            }
            var_dump($this->error);
            return null;
        }
        return false;
    }

    /**
     * print or return last query
     * @param bool $return
     * @return bool
     */
    public function last($return = false)
    {
        if (!empty($this->query)) {
            if ($return) {
                return $this->query;
            }
            var_dump($this->query);
            return null;
        }
        return false;
    }

    /**
     * return sql-resource
     * @return bool|\PDO
     * @throws \ErrorException
     */
    public function conn()
    {
        if ($this->sql !== false) {
            return $this->sql;
        }
        throw new \ErrorException('Lost Connection to Database');
    }

    /**
     * return prefix
     * @return mixed
     */
    public function getPrefix()
    {
        return $this->pref;
    }

    /**
     * return database name
     * @return mixed
     */
    public function getDatabaseName()
    {
        return $this->db;
    }
}

