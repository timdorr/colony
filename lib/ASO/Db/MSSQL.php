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
 * @see ASO_Db_Abstract
 */
require_once 'ASO/Db/Abstract.php';

/**
 * MSSQL database adapter.
 *
 * @category   Colony
 * @package    ASO
 * @copyright  Copyright (c) Army of Bees (www.armyofbees.com)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 */
class ASO_Db_MSSQL extends ASO_Db_Abstract
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

        $this->_connection = @mssql_connect( $this->config['db_host'],
                                             $this->config['db_user'],
                                             $this->config['db_pass'] );

        if ( !$this->_connection )
        {
            throw new ASO_Db_MSSQL_Exception( "MSSQL Error: " . mssql_get_last_message() );
        }

        if ( !mssql_select_db( $this->config['db_name'], $this->_connection ) )
        {
            throw new ASO_Db_MSSQL_Exception( "MSSQL Error: " . mssql_get_last_message() );
        }
    }

    /**
     * Wrapper for mssql_close()
     * @return void
     */
    public function close()
    {
       mssql_close( $this->_connection );
       $this->_connection = null;
    }

    /**
     * Wrapper for mssql_query(). Expected to be called from _query()
     *
     * @param string $query_string The query string to run
     * @param boolean $no_debug Tells the function if it should record the query in the debug variables.
     * @return void
     */
    protected function _query( $query_string = "", $no_debug = false )
    {
        $this->_queryid = mssql_query( $query_string, $this->_connection );

        if( !$this->_queryid )
            throw new ASO_Db_MSSQL_Exception("Could not compete a query to the database. MSSQL error: " . mssql_get_last_message() );
    }

    public function primary_table() {
        return $this->config["db_table"];
    }
    public function users_table() {
        return $this->config["db_table_users"];
    }
    public function users_account_table() {
        return $this->config["db_table_users_account"];
    }

    /**
     * Custom function to work basically like mssql_escape_string() would work
     * @param resource $string the string to escape
     * @return string The escaped string
     */
    public function escape_string( $string ) {
        $string = str_replace( "'", "''", $string );
        return $string;
    }

    /**
     * Wrapper for mssql_num_rows()
     * @param resource $queryid The query ID to use for this operation
     * @return int The number of selected rows
     */
    public function num_rows( $queryid = -1 )
    {
        if( $queryid == -1 )
            $queryid = $this->_queryid;

        return mssql_num_rows( $queryid );
    }

    /**
     * Wrapper for mssql_fetch_array().
     * @param resource $queryid The query ID to use for this operation
     * @return array The data from the current row.
     */
    public function fetch_array( $queryid = -1 )
    {
        if( $queryid == -1 )
            $queryid = $this->_queryid;

        return mssql_fetch_array( $queryid, MSSQL_ASSOC );
    }

    /**
     * Wrapper for mssql_fetch_assoc()
     * @param resource $queryid The query ID to use for this operation
     * @return array The data from the current row
     */
    public function fetch_assoc( $queryid = -1 )
    {
        if( $queryid == -1 )
            $queryid = $this->_queryid;

        return mssql_fetch_assoc( $queryid );
    }

    /**
     * Wrapper for mssql_rows_affected()
     * @param resource $connectid The connection ID to use for this operation
     * @return int The number of affected rows
     */
    public function affected_rows( $queryid = -1 )
    {
        if( $queryid == -1 )
            $queryid = $this->_queryid;

        return mssql_rows_affected( $queryid );
    }

    /**
     * Wrapper for mssql_data_seek()
     * @param resource $queryid The query ID to use for this operation
     * @param integer $row Thw row to seek data to
     * @return resource The query ID
     */
    public function data_seek( $queryid, $row )
    {
        if( $queryid == -1 )
            $queryid = $this->_queryid;

        return mssql_data_seek( $queryid, $row );
    }

    /**
     * Querys the db and gets one row.
     * @param string $query_string The query string to run
     * @param integer $no_debug Tells the function if it should record the query in the debug variables.
     * @return array The 1st row of the query
     */
    public function queryFetch( $query_string = "", $no_debug = 0 ) {

        $this->_queryid = $this->query( $query_string, $no_debug );

        if( $this->num_rows( $this->_queryid ) > 0 ) {
            return $this->fetch_array( $this->_queryid );
        } else {
            return array();
        }
    }

    /**
     * Querys the db and gets all rows.
     * @param string $query_string The query string to run
     * @param integer $no_debug Tells the function if it should record the query in the debug variables.
     * @return array An array of all rows of the query
     */
    public function queryFetchAll( $query_string = "", $no_debug = 0 ) {

        $this->_queryid = $this->query( $query_string, $no_debug );

        if( $this->num_rows( $this->_queryid ) > 0 ) {
            $res = array();
            while( $row = $this->fetch_array( $this->_queryid ) )
                $res[] = $row;
            return $res;
        } else {
            return array();
        }
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
     * Grab all entries from a table.
     * @param string $table The name of the table to pull from
     * @param string $id A WHERE condition to match
     * @return array The items from the table
     */
    public function getAll( $table, $where )
    {
        return $this->queryFetchAll( "SELECT * FROM $table WHERE $where" );
    }

    /**
     * Grab a field from an entry from a table.
     * @param string $table The name of the table to pull from
     * @param string $id A WHERE condition to match
     * @param string $var A field name or number
     * @return $string The field from the item from the table
     */
    public function queryVar( $query, $var = 0 ) {
        $item = $this->queryFetch( $query );
        if ( $this->num_rows() > 0 ) {
            return $item[ $var ];
        } else {
            return NULL;
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
            $value = $this->escape_string( $value );

            $columns .= " $key,";
            $values  .= " '$value',";
        }

        $columns = preg_replace( '/,$/', '', $columns );
        $values  = preg_replace( '/,$/', '', $values );


        return $this->query( "INSERT INTO $table( $columns ) VALUES ( $values )" );
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
            $value = $this->escape_string( $value );
            $setters .= " $key='$value',";
        }

        $setters = preg_replace( '/,$/' , ' ' , $setters );

        return $this->query( "UPDATE $table SET " . $setters . "WHERE $id_field = '$id'" );
    }


    /**
     * Deletes a row of data in a table
     * @param string $table The table to delete from
     * @param string $id_field The field on which to match the row to delete
     * @param mixed $id The id of that field
     * @return resource The ID of the query
     */
    public function delete( $table = "", $id_field, $id )
    {
        return $this->query( "DELETE $table WHERE $id_field = '$id'" );
    }


    /**
     * Wrapper for mssql_insert_id()
     * @return int The id of the last inserted row
     */
    public function insert_id()
    {
        $q = mssql_query("SELECT LAST_INSERT_ID=@@IDENTITY");
        $r = mssql_fetch_assoc($q);
        return $r['LAST_INSERT_ID'];
    }


    /**
     * Querys the db and checsk if one row exists.
     * @param string $query_string The query string to run
     * @param integer $no_debug Tells the function if it should record the query in the debug variables.
     */
    public function queryFetchExists( $query_string = "", $no_debug = 0 ) {
        $this->query( $query_string, $no_debug );
        if( mssql_num_rows( $this->_queryid ) > 0 ) {
            return true;
        } else {
            return false;
        }
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

class ASO_Db_MSSQL_Exception extends ASO_Db_Abstract_Exception
{}
