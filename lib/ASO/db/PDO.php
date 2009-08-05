<?php

require_once 'ASO/Db/Abstract.php';

class ASO_Db_PDO extends ASO_Db_Abstract
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
            throw new ASO_Db_PDO_Exception("PDO Error: " . $pdoe->getMessage());
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
        return $statement->fetchAll();
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
            throw new ASO_Db_PDO_Exception("PDO Error: " . $arr[2]);
        }

        return $res;
    }

    public function queryFetch($query) {
        $statement = $this->_query($query);
        return $statement->fetch();
    }

    public function queryFetchAll($query) {
        $statement = $this->_query($query);
        return $statement->fetchAll();
    }

    public function get($table, $where) {
        return $this->select($table, $where, array());
    }

    public function insert($table = "", $data = array()) {
        $this->connect();

        $columns = "";
        $values = "";
        $params = array();

		foreach( $data as $key => $value )
		{
		    if (!empty($columns)) {
		        $columns .= ', ';
		        $values .= ', ';
		    }

		    $columns .= "$key";
		    $values .= ":$key";
			$params[":$key"] = $value;
		}

		$sql = "INSERT INTO $table( $columns ) VALUES ( $values )";

		$stmt = $this->prepare($sql);
		$res = $stmt->execute($params);

		if ($res == FALSE) {
            $arr = $statement->errorInfo();
            throw new ASO_Db_PDO_Exception("PDO Error: " . $arr[2]);
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
            $arr = $statement->errorInfo();
            throw new ASO_Db_PDO_Exception("PDO Error: " . $arr[2]);
        }
        
		return $res;

    }
}

class ASO_Db_PDO_Exception extends ASO_Db_Abstract_Exception {}
