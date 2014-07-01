<?php

namespace SarMysql;

class SarMysql
{
    private $conn = '';
    public $host = '';
    public $user = '';
    public $password = '';
    public $database = '';
    private $result;
    private $message = '';

    function __construct($connection = "default")
    {
        $this->conn = $this->getConfig()["sardatabases"]["mysql"][$connection];
        $this->host = $this->conn["host"];
        $this->user = $this->conn["user"];
        $this->password = $this->conn["password"];
        $this->database = $this->conn["database"];
    }


    public function getConfig()
    {
        return include (dirname(__DIR__)) . '/../../../../config/autoload/sarmysql.config.local.php';
    }


    private function Open()
    {
        if(empty($this->host) ||
           empty($this->user) ||
           empty($this->database) 
            ){
            return false;
        }
        $this->conn = mysqli_connect($this->host, $this->user, $this->password);
        mysqli_select_db($this->conn, $this->database);
    }

    public function getMessage()
    {
        return $this->message;
    }

    function close($resultDB = "")
    {
        $this->result = $resultDB;

        if ($resultDB)
            mysqli_free_result($this->result);

        mysqli_close($this->conn);
    }

    function closeResult($resultDB)
    {
        $this->result = $resultDB;

        mysqli_free_result($this->result);
    }

    function closeLink()
    {
        mysqli_close($this->conn);
    }

    function __toString()
    {
        return "Host: " . $this->host . "<br />User: " . $this->user . "<br />Password: " . $this->password . "<br />DbName: " . $this->db_name;
    }

    public function getInsertID()
    {
        return mysql_insert_id($this->conn);
    }


    function refValues($arr)
    {
        //Reference is required for PHP 5.3+
        if (strnatcmp(phpversion(), '5.3') >= 0) {
            $refs = array();
            foreach ($arr as $key => $value)
                $refs[$key] = & $arr[$key];
            return $refs;
        }
        return $arr;
    }

    /**
     * Escape harmful characters which might affect a query.
     *
     * @param string $str The string to escape.
     * @return string The escaped string.
     */
    public function escape($str)
    {
        return $this->_mysqli->real_escape_string($str);
    }

    /**
     * This method is needed for prepared statements. They require
     * the data type of the field to be bound with "i" s", etc.
     * This function takes the input, determines what type it is,
     * and then updates the param_type.
     *
     * @param mixed $item Input to determine the type.
     * @return string The joined parameter types.
     */
    private function _determineType($item)
    {
        switch (gettype($item)) {
            case 'NULL':
            case 'string':
                return 's';
                break;

            case 'integer':
                return 'i';
                break;

            case 'blob':
                return 'b';
                break;

            case 'double':
                return 'd';
                break;
        }
    }


    /**
     * Method attempts to prepare the SQL query
     * and throws an error if there was a problem.
     */
    protected function _prepareQuery()
    {
        if (!$stmt = $this->_mysqli->prepare($this->_query)) {
            trigger_error("Problem preparing query ($this->_query) " . $this->_mysqli->error, E_USER_ERROR);
        }
        return $stmt;
    }

    public function __destruct()
    {
        //$this->_mysqli->close();
    }


    function select($table, $getFieldsArray = array(), $queryEnd = "", $inputArray = array(), $mode = 0, $close = 0, $cache = 1)
    {

        $this->Open();
        if (!$this->conn) {
            $this->message = 'No connection to the database';
            return false;
        } else {
            $inputArray = $this->_getInputArrayType($inputArray);
            $retArray = array();
            $query = "SELECT " . ($cache == 1 ? " SQL_CACHE " : " SQL_NO_CACHE ");
            $i = 0;
            foreach ($getFieldsArray as $entry) {
                $query .= ($i > 0 ? ", " : " ");
                if (is_array($entry)) {
                    $query .= $entry[0] . " as " . $entry[1];
                    $getFieldsArray[$i] = $entry[1];
                } else {
                    $query .= ($mode != 1 ? $entry : "count(" . $entry . ") as " . $entry . " ");
                }
                $i++;
            }

            $fieldNamesArray = $getFieldsArray;
            $query .= " FROM " . $table . " ";
            if ($queryEnd != "") {
                $query .= $queryEnd;
            }
            // HVIS IKKE MODUS 1, LEGGES SORTERING OG LIMIT TIL QUERY
            $i = 1;
            // PREPARERER QUERY FOR KJ�RING
            if ($stmt = mysqli_prepare($this->conn, $query)) {
                /*     OPPRETTER ET ARRAY, BINDROW, MED ARGUMENTENE TIL BIND PARAM (1. ER STATEMENT, 2. ER STRINGEN
                MED TYPENE OG RESTEN ER ALLE VARIABLENE TIL QUERYET) OG RESULTBINDROW HVOR STATEMENTEN BINDES*/
                $bindRow[0] = $resultBindRow[0] = $stmt;
                $bindRow[1] = "";
                if ($inputArray != "" && count($inputArray) > 0 && is_array($inputArray)) {
                    foreach ($inputArray as $entry) {
                        $entry[0] = utf8_decode($entry[0]);
                        $bindRow[1] .= $entry[1];
                        $bindRow[] = & $entry[0];
                    }
                }

                // LEGGER TIL RESULTATENE SOM SKAL HENTES I RESULTBINDROW
                for ($i = 0; $i < count($getFieldsArray); $i++) {
                    $resultBindRow[] = & $getFieldsArray[$i];
                }

                $params = array(); // Create the empty 0 index
                $i = 1;
                foreach ($getFieldsArray as $prop => $val) {
                    $params[0] = $this->_determineType($val);
                    array_push($params, $val);
                }


                // DeBug
//                debug_to_console(utf8_encode($queryEnd));

                // BINDER PARAMETERE
                call_user_func_array("mysqli_stmt_bind_param", $this->refValues($bindRow));

                // KJ�RE QUERYET
                mysqli_stmt_execute($stmt);

                // BUFRER RESULTATET FOR Å KUNNE TELLE TREFF
                mysqli_stmt_store_result($stmt);

                // BINDE RESULTAT-VARIABLENE
                call_user_func_array("mysqli_stmt_bind_result", $resultBindRow);

                // HENTE UT RESULTATENE
                $j = 0;
                #print_r($stmt);
                while (mysqli_stmt_fetch($stmt)) {
                    // HVIS MAN KUN KJ�RER EN OPPTELLING, RETURNERES TALLET.
                    if ($mode == 1) {
                        if ($close) {
                            $this->close();
                            //    $this->close($this->conn);
                        }
                        return $getFieldsArray[0];
                    }


                    // OPPRETTER ET MIDLERTIDIG ARRAY HVOR ALLE TREFFENE LEGGES
                    $tmpArray = array();
                    foreach ($getFieldsArray as $k => $v) {
                        $tmpArray[$fieldNamesArray[$k]] = $v;
                    }
                    if ($mode == 2) {
                        if ($close) {
                            $this->close();
                        }
                        return $tmpArray;
                    }


                    // LEGGER TREFFENE TIL I RETUR-ARRAYET
                    array_push($retArray, $tmpArray);
                }

            } else {
                echo PHP_EOL . mysqli_error($this->conn) . PHP_EOL;
//                echo mysqli_error($this->conn) . "<br>Query:<br>";
                echo $query . PHP_EOL;
            }
            //$this->close();
            return $retArray;
        }
    }

    function insert($query, $inputArray, $closeConnection = false)
    {
        $this->Open();
        if (!$this->conn) {
            $this->message = _("A connection to the database could be established");
            return false;
        } else {
            $inputArray = $this->_getInputArrayType($inputArray);
            // GET READY
            if ($stmt = mysqli_prepare($this->conn, $query)) {

                /*     OPPRETTER ET ARRAY, BINDROW, MED ARGUMENTENE TIL BIND PARAM (1. ER STATEMENT, 2. ER STRINGEN
                    MED TYPENE OG RESTEN ER ALLE VARIABLENE TIL QUERYET)*/
                $bindRow[0] = $stmt;
                $bindRow[1] = "";
                foreach ($inputArray as $entry) {
                    $entry[0] = utf8_decode($entry[0]);
                    $bindRow[] = & $entry[0];
                    $bindRow[1] .= $entry[1];
                }

                // BIND THE PARAMS
                call_user_func_array("mysqli_stmt_bind_param", $this->refValues($bindRow));
                $res = mysqli_stmt_execute($stmt);

                if ($closeConnection) {
                    $this->close();
                }
                if (!$res) {
                    return false;
                }
                return $res;

            } else {
                return mysqli_error($this->conn);
            }
        }
    }


    private function _getInputArrayType($arr)
    {
        $retArray = array();
        if (is_array($arr)) {
            foreach ($arr as $entry) {
                if (!is_array($entry)) {
                    $type = "s";
                    if (is_numeric($entry)) {
                        if (strstr($entry, ".") > 0)
                            $type = "d";
                        elseif (strlen($entry) < 10)
                            $type = "i";
                    }
                    $retArray[] = array($entry, $type);
                } else
                    $retArray[] = $entry;
            }
        }
        return $retArray;
    }

}
