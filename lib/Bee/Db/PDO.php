<?php
/**
 * Colony
 * Copyright (c) Army of Bees (www.armyofbees.com)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @category   Colony
 * @package    ASO
 * @copyright  Copyright (c) Army of Bees (www.armyofbees.com)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 */
 
/**
 * @see Bee_Db_Abstract
 */
require_once 'Bee/Db/Abstract.php';

/**
 * PDO database adapter.
 *
 * @category   Colony
 * @package    ASO
 * @copyright  Copyright (c) Army of Bees (www.armyofbees.com)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 */
class Bee_Db_PDO extends Bee_Db_Abstract
{
    public function connect()
    {
        //already connected
        //don't make a new connection
        if ( $this->_connection) {
            return;
        }

        $db = $this->config['db_name'];
        $host = $this->config['db_host'];

        try {
            $this->_connection = new PDO("mysql:dbname=$db;host=$host",
        					       $this->config['db_user'],
        					       $this->config['db_pass']);

        	
        	if ($this->config['debug']) {
        	    $this->_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        	} else {
        	    $this->_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        	}
            
        } catch (PDOException $pdoe) {
            $this->_connection = null;
            throw new Bee_Db_PDO_Exception("PDO Error: " . $pdoe->getMessage());
        }
    }

    /** escape a string for query use */
    public function quote($string) {
        $this->connect();
        return $this->_connection->quote($string);
    }

    /** create a prepared statement */
    public function prepare($statement) {
        $this->connect();
        return $this->_connection->prepare($statement);
    }

    public function lastInsertId() {
        $this->connect();
        return $this->_connection->lastInsertId();
    }

    public function insert_id() {
        return $this->lastInsertId();
    }

    public function select($table, $where, $fields = array()) {
        $sqlFields = "*";

        if (!empty($fields)) {
            $sqlFields = "";
            foreach( $fields as $field )
    		{
    		    if (!empty($sqlFields)) {
    		        $sqlFields .= ", ";
    		    }

    		    $sqlFields .= $field;
    		}
        }

        $sql = "SELECT $sqlFields FROM $table WHERE $where";

        $statement = $this->_query($sql);
        $results = $statement->fetchAll();
        
        if(!$results) {
        	return array();
        } else {
        	return $results;
        }
    }
        
    /** run an sql query and return a PDOStatement object */
    public function sqlQuery($query_string)
    {
        return $this->_query($query_string);
    }

    //// backwards compat

    protected function _query($query_string = "", $no_debug = false )
    {
        $this->connect();
        $res = $this->_connection->query($query_string);

        if (empty($res)) {
            $arr = $this->_connection->errorInfo();
            throw new Bee_Db_PDO_Exception("PDO Error: " . $arr[2]);
        }

        return $res;
    }

    public function queryFetch($query) {
        $statement = $this->_query($query);
        $results = $statement->fetch();
        
        if(!$results) {
        	return array();
        } else {
        	return $results;
        }
    }

    public function queryFetchAll($query) {
        $statement = $this->_query($query);
        $results = $statement->fetchAll();
        
        if(!$results) {
        	return array();
        } else {
        	return $results;
        }
    }

    public function get($table, $where) {
        $results = $this->select($table, $where, array());
        if(count($results) < 1) {
        	return array();
        }
        
        return $results[0];
    }

    public function num_rows($query_id = -1) {
        throw new Bee_Db_PDO_Exception("num_rows is incompatible with PDO, use count() instead");
    }

    public function insert($table = "", $data = array()) {
        $this->connect();

        $columns = "";
        $values = "";
        $params = array();

		foreach( $data as $key => $value )
		{
		    if (!empty($columns)) {
		        $columns .= '`, `';
		        $values .= ', ';
		    }

		    $columns .= "$key";
		    $values .= ":$key";
			$params[":$key"] = $value;
		}

		$sql = "INSERT INTO $table( `$columns` ) VALUES ( $values )";

		$stmt = $this->prepare($sql);
		$res = $stmt->execute($params);

		if ($res == FALSE) {
            $arr = $stmt->errorInfo();
            throw new Bee_Db_PDO_Exception("PDO Error: " . $arr[2]);
        }
        
		return $res;
    }

    public function update($table = "", $data = array(), $id_field, $id) {
        $this->connect();
        
        $params = array();
        $setters = "";

		foreach( $data as $key => $value )
		{
		    if (!empty($setters)) {
		        $setters .= ', ';
		    }

		    $param = ":$key";
			$setters .= " $key=$param";
			$params[$param] = $value;
		}

		$params[":$id_field"] = $id;

		$sql = "UPDATE $table SET " . $setters . " WHERE $id_field = :$id_field";
		
		$stmt = $this->prepare($sql);
		$res = $stmt->execute($params);

		if ($res == FALSE) {
            $arr = $stmt->errorInfo();
            throw new Bee_Db_PDO_Exception("PDO Error: " . $arr[2]);
        }
        
		return $res;

    }
}

class Bee_Db_PDO_Exception extends Bee_Db_Abstract_Exception {}
