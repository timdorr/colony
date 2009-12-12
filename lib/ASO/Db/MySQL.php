<?php
/**
 * ASOworx
 * Copyright (c) A Small Orange Software (http://www.asmallorange.com)
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
 * @category   ASOworx
 * @package    ASO
 * @copyright  Copyright (c) A Small Orange Software (http://www.asmallorange.com)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * @see ASO_Db_Abstract
 */
require_once 'ASO/Db/Abstract.php';

/**
 * MySQL database adapter.
 *
 * @category   ASOworx
 * @package    ASO
 * @copyright  Copyright (c) A Small Orange Software (http://www.asmallorange.com)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 */
class ASO_Db_MySQL extends ASO_Db_Abstract
{
	/**
	 * Connects to the defined server and selects the defined database. 
	 *
	 * Do not use directly. query() will connect the database if it has not been
	 * connected already. This method allows the database layer to be instantiated
	 * on every page without neccessarily requiring a database connection on each 
	 * page.
	 *
	 * @return void
	 */
	public function connect()
	{
        if( $this->_connection )
            return;
	
		$this->_connection = @mysql_connect( $this->config['db_host'], 
		                                     $this->config['db_user'], 
		                                     $this->config['db_pass'] );

		if ( !$this->_connection )
		{
			throw new ASO_Db_MySQL_Exception( "MySQL Error: " . mysql_error() );
		}

		if ( !mysql_select_db( $this->config['db_name'], $this->_connection ) )
        {
			throw new ASO_Db_MySQL_Exception( "MySQL Error: " . mysql_error() );
        }
	}

	/**
	 * Wrapper for mysql_close()
	 * @return void
	 */
	public function close()
	{
	   mysql_close( $this->_connection );
	   $this->_connection = null;
	}

	/**
	 * Wrapper for mysql_query(). Expected to be called from _query()
	 *
	 * @param string $query_string The query string to run
	 * @param boolean $no_debug Tells the function if it should record the query in the debug variables.
	 * @return void
	 */
	protected function _query( $query_string = "", $no_debug = false )
	{
		$this->_queryid = @mysql_query( $query_string, $this->_connection );
		
		if( mysql_errno() != 0 )
            throw new ASO_Db_MySQL_Exception("Could not compete a query to the database. MySQL error: " . mysql_error() );
	}

	/**
	 * Querys the db and gets one row.
	 * @param string $query_string The query string to run
	 * @param integer $no_debug Tells the function if it should record the query in the debug variables.
	 * @return array The 1st row of the query
	 */
	public function queryFetch( $query_string = "", $no_debug = 0 )
	{
		$this->query( $query_string, $no_debug );
		
		if( mysql_num_rows( $this->_queryid ) > 0 )
		{
			return $this->fetch_array( $this->_queryid );
		}
		else
		{
			return array();
		}
	}
	
	/**
	 * Querys the db and gets all rows.
	 * @param string $query_string The query string to run
	 * @param integer $no_debug Tells the function if it should record the query in the debug variables.
	 * @return array An array of all rows of the query
	 */
    public function queryFetchAll( $query_string = "", $no_debug = 0 )
    {
        $this->query( $query_string, $no_debug );
        
		if( mysql_num_rows( $this->_queryid ) > 0 )
		{
			$res = array();
			while( $row = $this->fetch_assoc( $this->_queryid ) )
				$res[] = $row;
			return $res;
		}
		else
		{
			return array();
		}
    }
	
	/**
	 * Inserts an array of data into a table
	 * @param string $table The table to insert into
	 * @param array $data The data to insert with keys as field names
	 * @return resource The ID of the query
	 */
	public function insert( $table = "", $data = array() )
	{   
		$columns = '';
		$values = '';
		
		foreach( $data as $key => $value )
		{
            $value = mysql_escape_string( $value );

			$columns .= " `$key`,";
			$values  .= " '$value',";
		}
		
		$columns = preg_replace( '/,$/', '', $columns );
		$values  = preg_replace( '/,$/', '', $values );
		
        return $this->query( "INSERT INTO $table( $columns ) VALUES ( $values )" );
	}
	
	/**
	 * Replaces an array of data in a table or creates a new row
	 * @param string $table The table to replace info
	 * @param array $data The data to replace with keys as field names
	 * @return resource The ID of the query
	 */
	public function replace( $table = "", $data = array() )
	{   
		$columns = '';
		$values = '';
		
		foreach( $data as $key => $value )
		{
            $value = mysql_escape_string( $value );

			$columns .= " `$key`,";
			$values  .= " '$value',";
		}
		
		$columns = preg_replace( '/,$/', '', $columns );
		$values  = preg_replace( '/,$/', '', $values );
		
        return $this->query( "REPLACE INTO $table( $columns ) VALUES ( $values )" );
	}
	
	/**
	 * Updates a row of data in a table
	 * @param string $table The table to update
	 * @param array $data The data to update to with keys as field names
	 * @param string $id_field The field on which to match the row to update
	 * @param mixed $id The id of that field
	 * @return resource The ID of the query
	 */
	public function update( $table = "", $data = array(), $id_field, $id )
	{   
        $setters = '';
		foreach( $data as $key => $value )
		{
            $value = mysql_escape_string( $value );
			$setters .= " `$key`='$value',";
		}
		
		$setters = preg_replace( '/,$/' , ' ' , $setters );
			    
		return $this->query( "UPDATE $table SET " . $setters . "WHERE `$id_field` = '$id'" );
	}
	
	/**
	 * Wrapper for mysql_fetch_array().
	 * @param resource $queryid The query ID to use for this operation
	 * @return array The data from the current row.
	 */
	public function fetch_array( $queryid = -1 )
	{
		if( $queryid == -1 )
			$queryid = $this->_queryid;

		return mysql_fetch_array( $queryid );
	}

	/**
	 * Wrapper for mysql_fetch_assoc()
	 * @param resource $queryid The query ID to use for this operation
	 * @return array The data from the current row
	 */
	public function fetch_assoc( $queryid = -1 )
	{
		if( $queryid == -1 )
			$queryid = $this->_queryid;

		return mysql_fetch_assoc( $queryid );
	}

	/**
	 * Wrapper for mysql_data_seek()
	 * @param resource $queryid The query ID to use for this operation
	 * @param integer $row Thw row to seek data to
	 * @return resource The query ID
	 */
	public function data_seek( $queryid, $row )
	{
		if( $queryid == -1 )
			$queryid = $this->_queryid;

		return mysql_data_seek( $queryid, $row );
	}
	
	/**
	 * Wrapper for mysql_num_rows()
	 * @param resource $queryid The query ID to use for this operation
	 * @return int The number of selected rows
	 */
	public function num_rows( $queryid = -1 )
	{
		if( $queryid == -1 )
			$queryid = $this->_queryid;
			
		return mysql_num_rows( $queryid );
	}

	/**
	 * Wrapper for mysql_affected_rows()
	 * @param resource $connectid The connection ID to use for this operation
	 * @return int The number of affected rows
	 */
	public function affected_rows( $queryid = -1 )
	{
		if( $queryid == -1 )
			$queryid = $this->_queryid;

		return mysql_affected_rows( $queryid );
	}

	/**
	 * Wrapper for mysql_insert_id()
	 * @return int The id of the last inserted row
	 */
	public function insert_id()
	{
		return mysql_insert_id( $this->_connection );
	}

	/**
	 * Grab all the data from a table. Makes for easy coding. 
	 * @param string $table The name of the table to pull from
	 * @param int $limit The number of rows to limit the results to
	 * @param int $offset The offset of the row to start from
	 * @return array The data from the table
	 */
    public function getAll( $table, $limit = 0, $offset = 0 )
    {
        //--------------------------------
        // Check if we're using a limit
        // The offset can be 0, since 
        // it's equivalent with or without
        //--------------------------------
        
        $sqllimit = '';
        if( $limit > 0 )
		{
			$offset = intval( $offset ) < 0 ? 0 : intval( $offset );
			
            $sqllimit = "LIMIT $offset, $limit";
		}
        
        //-----------------------------
        // Do some serious data suction
        //-----------------------------
        
        $res = array();
        $this->query( "SELECT * FROM $table $sqllimit" );
        while( $row = $this->fetch_array() )
            $res[] = $row;
            
        return $res;
    }

	/**
	 * Paginate data from the table.
	 * @param string $table The name of the table to pull from
	 * @param int $limit The number of rows per page
	 * @param int $page The page we're on right now
	 * @return array The data from the table and the total pages there are
	 */
    public function getByPage( $table, $limit = 0, $page = 0, $where = '' )
    {
		$where = empty( $where ) ? '' : "WHERE $where";
	
		//-------------------------------------------------------------------
		// I realize these lines may be a bit confusing, so let me explain...
		// Since data starts at 0, you have to subtract 1 from the page 
		// count. But you have to add one when dividing to get the total
		// number of pages. Confusing? Yes, but it works.
		//-------------------------------------------------------------------
	
		$count = array_pop( $this->queryFetch( "SELECT COUNT(*) FROM $table $where" ) );
	
		return array( 'data'  => $this->getAll( $table." ".$where, $limit, ( $page - 1 ) * $limit ),
					  'count' => $count,
					  'pages' => round( $count / $limit ) + 1 );
	}
    
	/**
	 * Grab an entry from a table.
	 * @param string $table The name of the table to pull from
	 * @param string $id A WHERE condition to match
	 * @return array The item from the table
	 */
    public function get( $table, $where )
    {
        return $this->queryFetch( "SELECT * FROM $table WHERE $where" );
    }
    
	/**
	 * Grab the enumeration/set values from a table for a specific field.
	 * @param string $table The name of the table to pull from
	 * @param string $field The field to pull from
	 * @return array The values that make up the enumeration
	 */
	function enum_values( $table, $field ) {
		$row = $this->queryFetch( "SHOW COLUMNS FROM `{$table}` LIKE '{$field}'" );
		preg_match_all( "/'(.*?)'/" , $row["Type"], $enum_array );
		$enum_fields = $enum_array[1];
		return $enum_fields;
	}
	
	/**
	 * Grab the unique values from a table for a specific field.
	 * @param string $table The name of the table to pull from
	 * @param string $field The field to pull from
	 * @return array The unique values that make up the field
	 */
	function unique_values( $table, $field ) {
		$res = $this->queryFetchAll( "SELECT DISTINCT {$field} FROM {$table} WHERE {$field} IS NOT NULL ORDER BY {$field}" );
		$return = array();
		foreach ( $res as $r ) {
			$return[] = $r[ $field ];
		}
		return $return;
	}
    
    /**
     * Returns the string representation of the object
     *
     * @return string
     */
    public function __toString() {
    	$out = "<pre>";
    	$out .= "Database Object\n";
    	$out .= "{\n";
    	$out .= "\t[connected] => ".( $this->_connection == null ? "false" : "true" )."\n";
    	
    	$out .= "\t[last_query] => ".$this->query."\n";
    	$out .= "\t[querycount] => ".$this->querycount."\n";
    	$out .= "\t[querytimes] => ".str_replace( array( "\n", "    " ), array( "", "" ), print_r( $this->querytimes, true ) )."\n";
    	$out .= "\t[querylist] => ".str_replace( array( "\n", "    " ), array( "", "" ), print_r( $this->querylist, true ) )."\n";

    	$out .= "}\n";
    	$out .= "</pre>";
    	return $out;
    }

}

class ASO_Db_MySQL_Exception extends ASO_Db_Abstract_Exception
{}