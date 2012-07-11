<?
/**
 * mysql class
 *
 */
class aaymysql
{
    /**
     * DB connection resource
     *
     * @var resource
     */
    private $db;

    /**
     * Table columns
     *
     * @var array
     */
    private $items;


    /**
     * Class reports
     *
     * @var string
     */
    private $logs;

    /**
     * Mysql resource
     *
     * @var resource
     */
    private $resource;

    /*
     * Last query
     *
     * @var string
     */
    private $lastquery;

    /**
     * Mysql return values
     *
     * @var array
     */
    private $response;

    /**
     * Automated insert order id
     *
     * @var string
     */
    public $orderid;

    /**
     * Mysql query
     *
     * @var string
     */
    var $query;

    /**
     * class builder
     *
     * @param resource $db
     *
     */
    function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Item filter
     *
     * @param string $value
     * @return string
     */
    function filter($value)
    {
        if (get_magic_quotes_gpc()) {
            $value = stripslashes($value);
        }
        $value = mysql_real_escape_string($value);
        return $value;
    }

    /*
     * Item collector
     * @param string $column
     * @param string $value
     * @param bool $filtering
     */
    function item($column, $value, $filtering = true)
    {
        if ($filtering)
            $value = $this->filter($value);

        $this->items[$column] = $value;
    }


    /**
     * Item collector from forms
     * @param string $column
     * @param string $value
     * @param bool $post
     */
    function form($column, $value = "", $post = true)
    {
        if ($value == "")
            $value = $column;

        $value = $post ? $_POST[$value] : $_GET[$value];

        $this->item($column, $value, true);
    }

    /**
     * Class logger
     */
    private function logger()
    {
        $k = count($this->logs);

        $this->logs[$k]['query'] = $this->query;
        $this->logs[$k]['error'] = mysql_error($this->db);
        $this->logs[$k]['affected'] = mysql_affected_rows($this->db);
    }

    /**
     * Class item cleaner
     *
     */
    public function clean_items()
    {
        unset($this->items);
    }


    /**
     * Sql query
     *
     * @param string $query
     * @return resource
     */
    private function query($query)
    {
        $this->lastquery = $query;
        $resource = mysql_query($query, $this->db);
        $this->logger();
        return $resource;
    }

    /**
     * Sql select query
     *
     * @param string $table
     * @param string $columns
     * @param string $where
     */
    public function select($table, $columns = "*", $where = "")
    {
        $tmp = "select $columns from $table $where";
        $this->resource = $this->query($tmp);
    }

    /**
     * Sql limit
     * @param $start
     * @param $limit
     * @return int
     */
    public function limit($start, $limit)
    {
        $mt = $this->num_rows();
        $ts = (int)($mt / $limit);
        $mt % $limit ? $ts++ : "";
        $this->resource = $this->query($this->lastquery . " limit $start, $limit");
        return $ts;
    }


    /**
     * Sql fetch
     *
     * @return array
     */
    public function fetch()
    {
        $this->response = mysql_fetch_assoc($this->resource);
        return $this->response;
    }

    /**
     * Sql fetch array
     * @return array
     */
    public function fetch_array()
    {
        $ret = array();
        while ($val = $this->fetch()) {
            $ret[] = $val;
        }
        return $ret;
    }


    /**
     * Sql get value
     *
     * @param string $column
     * @return string
     */
    public function get($column)
    {
        $return = "";
        if ($this->response) {
            if (array_key_exists($column, $this->response))
                $return = $this->response[$column];
        }

        return $return;
    }


    /**
     * Get records count
     *
     * @param string $table
     * @return integer
     */
    public function num_rows($table = "")
    {
        $count = 0;
        if ($this->resource) {
            $count = mysql_num_rows($this->resource);
        } else {
            if ($table != "") {
                $this->query("SHOW TABLE STATUS LIKE '$table'");
                if ($this->fetch())
                    $count = $this->get('Rows');

            }
        }
        return $count;
    }


    /**
     * Insert items to table
     *
     * @param string $table
     * @return integer
     */
    public function insert($table)
    {
        function alter_quote(&$item1, $key)
        {
            $item1 = "'$item1'";
        }

        $c = array_keys($this->items);
        array_walk($c, 'alter_quote');
        $v = array_values($this->items);
        array_walk($v, 'alter_quote');

        $this->clean_items();

        $columns = implode(', ', $c);
        $values = implode(', ', $v);

        $query = "insert into $table ($columns) values ($values)";
        $this->query($query);
        $id = mysql_insert_id($this->db);
        if ($this->orderid != "") {
            $this->item($this->orderid, $id);
            $this->update($table, $id);
        }

        return $id;

    }

    /**
     * Update table from items
     *
     * @param string $table
     * @param int|string $id
     * @return int
     */
    function update($table, $id)
    {
        $return = 0;
        if ($id) {
            function alter_quote(&$item1, $key)
            {
                $item1 = "'$item1'";
            }

            $c = array_keys($this->items);
            array_walk($c, 'alter_quote');
            $v = array_values($this->items);
            array_walk($v, 'alter_quote');

            $seta = array();

            foreach ($c as $key => $column)
                $seta[] = "$column = " . $v[$key];

            $set = implode(", ", $seta);
            $this->clean_items();

            if (is_numeric($id))
                $where = " where id=" . $id;
            else
                $where = " " . $id;

            $query = "update $table set $set $where";
            $this->query($query);
            $return = mysql_affected_rows($this->db);
        }
        return $return;
    }

    /**
     * delete from table
     *
     * @param string $table
     * @param int|string $id
     * @return int
     */
    function delete($table, $id)
    {
        $return = 0;
        if ($id) {
            if (is_numeric($id))
                $where = "where id=" . $id;
            else
                $where = $id;

            $query = "delete from $table $where";
            $this->query($query);
            $return = mysql_affected_rows($this->db);
        }
        return $return;
    }
}

?>