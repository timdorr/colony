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
 * None database adapter.
 *
 * @category   ASOworx
 * @package    ASO
 * @copyright  Copyright (c) A Small Orange Software (http://www.asmallorange.com)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 */
class ASO_Db_None extends ASO_Db_Abstract
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
	public function connect() {
        if ( $this->_connection )
            return;

		$this->_connection = @ldap_connect( $this->config['db_host'], 389 );

		if ( !$this->_connection )
			throw new ASO_Db_LDAP_Exception( "Could not connect to LDAP server." );

		if ( !@ldap_set_option( $this->_connection, LDAP_OPT_PROTOCOL_VERSION, 3 ) )
    		throw new ASO_Db_LDAP_Exception( "Failed to set LDAP protocol version to 3" );

		if ( !@ldap_bind( $this->_connection, $this->config['db_user'], $this->config['db_pass'] ) )
			throw new ASO_Db_LDAP_Exception( "Could not bind to LDAP server with authentication." );
			

			
	}

	/**
	 * Wrapper for close connection command
	 * @return void
	 */
	public function close()
	{
	   $this->_connection = null;
	}



	/**
	 * Abstract functions to prevent errors in missing functions from usual DB setups
	 **/
	public function insert() { }
	public function get() { }
	public function num_rows() { }
	public function update() { }





	/**
	 * Wrapper for basic query command
	 *
	 * @param string $query_string The query string to run
	 * @param boolean $no_debug Tells the function if it should record the query in the debug variables.
	 * @return void
	 */
	protected function _query( $query_string = "", $no_debug = false ) { }













    
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

class ASO_Db_None_Exception extends ASO_Db_Abstract_Exception
{}