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
 * LDAP database adapter.
 *
 * @category   ASOworx
 * @package    ASO
 * @copyright  Copyright (c) A Small Orange Software (http://www.asmallorange.com)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 */
class ASO_Db_LDAP extends ASO_Db_Abstract
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
	 * Wrapper for ldap_unbind()
	 * @return void
	 */
	public function close()
	{
		if ( !@ldap_unbind( $this->_connection ) )
			throw new ASO_Db_LDAP_Exception( "Could not unbind from LDAP server." );		
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
	 * Wrapper for mysql_query(). Expected to be called from _query()
	 *
	 * @param string $query_string The query string to run
	 * @param boolean $no_debug Tells the function if it should record the query in the debug variables.
	 * @return void
	 */
	protected function _query( $query_string = "", $no_debug = false )
	{
/*
		$this->_queryid = @mysql_query( $query_string, $this->_connection );		
		if( mysql_errno() != 0 )
            throw new ASO_Db_LDAP_Exception("Could not compete a query to the database." );
*/
	}














	public function search( $base = "", $filter = "", $fields = array(), $count = 0, $sort = "" ) {
		if ( !$this->_connection ) $this->connect();
		
		$mtime = microtime();
		$mtime = explode( ' ', $mtime );
		$querystarttime = $mtime[1] + $mtime[0];
		
/*
echo "Base: $base<br />";
echo "Filter: $filter<br />";
echo "Fields: <pre>".print_r( $fields, true )."</pre><br />";
echo "Count: $count<br />";
echo "Sort: $sort<br />";
exit();
*/

		$results = @ldap_search( $this->_connection, $base, $filter, $fields, 0, $count );

		if ( !$results ) {
			$this->error( "<b>Notes:</b><br>\n
						   Could not complete a query to the database!<br>\n
						   <br>\n
						   <b>Query:</b><br \>\nSearch( \"{$base}\", \"{$filter}\", \"".print_r( $fields, true )."\", \"{$count}\", \"{$sort}\" )<br>\n<br>\n
						   <b>Error:</b><br \>\n<b>#".ldap_errno( $this->_connection ).":</b> ".ldap_error( $this->_connection )."<br>\n"
						);
		}
		
		$mtime = microtime();
		$mtime = explode( ' ', $mtime );
		$queryendtime = $mtime[1] + $mtime[0];
		
		$this->querycount++;
		if( $no_debug != 1 ) {
			$this->querytimes[] = $queryendtime - $querystarttime;
			$this->querylist[]  = "Search( \"{$base}\", \"{$filter}\", \"".print_r( $fields, true )."\", \"{$count}\", \"{$sort}\" )";
		}
		

//		if ( $sort != "" )
//			@ldap_sort( $this->_connection, $results, $sort );

		$entries = @ldap_get_entries( $this->_connection, $results );

		@ldap_free_result( $results );
		
		unset( $entries["count"] );
		foreach ( $entries as $i => $item ) {		
			unset( $entries[ $i ]["count"] );
			foreach ( $item as $key => $val ) {
				if ( is_int( $key ) && array_key_exists( $key, $item ) ) {
					unset( $entries[ $i ][ $key ] );
				} else if ( is_array( $val ) && $val["count"] == 1 ) {
					if ( strncmp( $val[ 0 ], "{CRYPT}", 7 ) == 0 )
						$entries[ $i ][ $key ] = substr( $val[ 0 ], 7 );
					else
						$entries[ $i ][ $key ] = $val[ 0 ];
				} else if ( is_array( $val ) && $val["count"] > 1 ) {
					unset( $entries[ $i ][ $key ][ "count" ] );
				}
			}
		}
		
		if ( $sort != "" ) {
			$GLOBALS["sort_key"] = $sort;
			usort( $entries, array($this, "mysort") );
		}
		
		return $entries;
	}
	
		function mysort( $a, $b ) {
			$global_sort_key = $GLOBALS["sort_key"];
			if ( $global_sort_key == "dn" ) {
				$vala = strrev( $a[ $global_sort_key ] );
				$valb = strrev( $b[ $global_sort_key ] );
			} else if ( $global_sort_key == "dc" ) {
				$vala = implode( ".", array_reverse( explode( ".", $a[ $global_sort_key ] ) ) );
				$valb = implode( ".", array_reverse( explode( ".", $b[ $global_sort_key ] ) ) );
			} else {
				$vala = $a[ $global_sort_key ];
				$valb = $b[ $global_sort_key ];
			}
			if ( $vala == $valb ) return 0;
			return ( $vala > $valb ) ? 1 : -1;
		}

	
	public function searchlevel( $base = "", $filter = "", $fields = array(), $count = 0, $sort = "" ) {
		if ( !$this->_connection ) $this->connect();
		
		$mtime = microtime();
		$mtime = explode( ' ', $mtime );
		$querystarttime = $mtime[1] + $mtime[0];

		$results = @ldap_list( $this->_connection, $base, $filter, $fields, 0, $count );

		if ( !$results ) {
			$this->error( "<b>Notes:</b><br>\n
						   Could not complete a query to the database!<br>\n
						   <br>\n
						   <b>Query:</b><br \>\nSearchLevel( \"{$base}\", \"{$filter}\", \"".print_r( $fields, true )."\", \"{$count}\", \"{$sort}\" )<br>\n<br>\n
						   <b>Error:</b><br \>\n<b>#".ldap_errno( $this->_connection ).":</b> ".ldap_error( $this->_connection )."<br>\n"
						);
		}
		
		$mtime = microtime();
		$mtime = explode( ' ', $mtime );
		$queryendtime = $mtime[1] + $mtime[0];
		
		$this->querycount++;
		if( $no_debug != 1 ) {
			$this->querytimes[] = $queryendtime - $querystarttime;
			$this->querylist[]  = "SearchLevel( \"{$base}\", \"{$filter}\", \"".print_r( $fields, true )."\", \"{$count}\", \"{$sort}\" )";
		}
		

		if ( $sort != "" )
			@ldap_sort( $this->_connection, $results, $sort );

		$entries = @ldap_get_entries( $this->_connection, $results );
		
		@ldap_free_result( $results );
		
		unset( $entries["count"] );
		foreach ( $entries as $i => $item ) {		
			unset( $entries[ $i ]["count"] );
			foreach ( $item as $key => $val ) {
				if ( is_int( $key ) && array_key_exists( $key, $item ) ) {
					unset( $entries[ $i ][ $key ] );
				} else if ( is_array( $val ) && $val["count"] == 1 ) {
					if ( strncmp( $val[ 0 ], "{CRYPT}", 7 ) == 0 )
						$entries[ $i ][ $key ] = substr( $val[ 0 ], 7 );
					else
						$entries[ $i ][ $key ] = $val[ 0 ];
				} else if ( is_array( $val ) && $val["count"] > 1 ) {
					unset( $entries[ $i ][ $key ][ "count" ] );
				}
			}
		}
		
		return $entries;
	}
	
	public function searchbase( $base = "", $filter = "", $fields = array(), $count = 0, $sort = "" ) {
		if ( !$this->_connection ) $this->connect();
		
		$mtime = microtime();
		$mtime = explode( ' ', $mtime );
		$querystarttime = $mtime[1] + $mtime[0];

		$results = @ldap_read( $this->_connection, $base, $filter, $fields, 0, $count );

		if ( !$results ) {
			$this->error( "<b>Notes:</b><br>\n
						   Could not complete a query to the database!<br>\n
						   <br>\n
						   <b>Query:</b><br \>\nSearchBase( \"{$base}\", \"{$filter}\", \"".print_r( $fields, true )."\", \"{$count}\", \"{$sort}\" )<br>\n<br>\n
						   <b>Error:</b><br \>\n<b>#".ldap_errno( $this->_connection ).":</b> ".ldap_error( $this->_connection )."<br>\n"
						);
		}
		
		$mtime = microtime();
		$mtime = explode( ' ', $mtime );
		$queryendtime = $mtime[1] + $mtime[0];
		
		$this->querycount++;
		if( $no_debug != 1 ) {
			$this->querytimes[] = $queryendtime - $querystarttime;
			$this->querylist[]  = "SearchBase( \"{$base}\", \"{$filter}\", \"".print_r( $fields, true )."\", \"{$count}\", \"{$sort}\" )";
		}
		

		if ( $sort != "" )
			@ldap_sort( $this->_connection, $results, $sort );

		$entries = @ldap_get_entries( $this->_connection, $results );
		
		@ldap_free_result( $results );
		
		unset( $entries["count"] );
		foreach ( $entries as $i => $item ) {		
			unset( $entries[ $i ]["count"] );
			foreach ( $item as $key => $val ) {
				if ( is_int( $key ) && array_key_exists( $key, $item ) ) {
					unset( $entries[ $i ][ $key ] );
				} else if ( is_array( $val ) && $val["count"] == 1 ) {
					if ( strncmp( $val[ 0 ], "{CRYPT}", 7 ) == 0 )
						$entries[ $i ][ $key ] = substr( $val[ 0 ], 7 );
					else
						$entries[ $i ][ $key ] = $val[ 0 ];
				} else if ( is_array( $val ) && $val["count"] > 1 ) {
					unset( $entries[ $i ][ $key ][ "count" ] );
				}
			}
		}
		
		return $entries;
	}






	public function add( $dn, $entry ) {
		if ( !$this->_connection ) $this->connect();
		
		$mtime = microtime();
		$mtime = explode( ' ', $mtime );
		$querystarttime = $mtime[1] + $mtime[0];

		$status = @ldap_add( $this->_connection, $dn, $entry );
		
		if ( !$status ) {
			$this->error( "<b>Notes:</b><br>\n
						   Could not complete a query to the database!<br>\n
						   <br>\n
						   <b>Query:</b><br \>\nAdd( \"{$dn}\", \"".print_r($entry,true)."\" )<br>\n<br>\n
						   <b>Error:</b><br \>\n<b>#".ldap_errno( $this->_connection ).":</b> ".ldap_error( $this->_connection )."<br>\n"
						);
		}
		
		$mtime = microtime();
		$mtime = explode( ' ', $mtime );
		$queryendtime = $mtime[1] + $mtime[0];
		
		$this->querycount++;
		if( $no_debug != 1 ) {
			$this->querytimes[] = $queryendtime - $querystarttime;
			$this->querylist[]  = "Add( \"{$dn}\", \"".print_r($entry,true)."\" )";
		}
	}
	
	public function modify( $dn, $entry ) {
		if ( !$this->_connection ) $this->connect();
		
		$mtime = microtime();
		$mtime = explode( ' ', $mtime );
		$querystarttime = $mtime[1] + $mtime[0];

		$status = @ldap_modify( $this->_connection, $dn, $entry );
		
		if ( !$status ) {
			$this->error( "<b>Notes:</b><br>\n
						   Could not complete a query to the database!<br>\n
						   <br>\n
						   <b>Query:</b><br \>\nModify( \"{$dn}\", \"".print_r($entry,true)."\")<br>\n<br>\n
						   <b>Error:</b><br \>\n<b>#".ldap_errno( $this->_connection ).":</b> ".ldap_error( $this->_connection )."<br>\n"
						);
		}
		
		$mtime = microtime();
		$mtime = explode( ' ', $mtime );
		$queryendtime = $mtime[1] + $mtime[0];
		
		$this->querycount++;
		if( $no_debug != 1 ) {
			$this->querytimes[] = $queryendtime - $querystarttime;
			$this->querylist[]  = "Modify( \"{$dn}\", \"".print_r($entry,true)."\")";
		}
	}


	public function delete( $dn, $recursive = true ) {
		if ( !$this->_connection ) $this->connect();
		
		$mtime = microtime();
		$mtime = explode( ' ', $mtime );
		$querystarttime = $mtime[1] + $mtime[0];
		
		$status = $this->delete_helper( $dn, $recursive );
		
		if ( !$status ) {
			$this->error( "<b>Notes:</b><br>\n
						   Could not complete a query to the database!<br>\n
						   <br>\n
						   <b>Query:</b><br \>\nDelete( \"{$dn}\" )<br>\n<br>\n
						   <b>Error:</b><br \>\n<b>#".ldap_errno( $this->_connection ).":</b> ".ldap_error( $this->_connection )."<br>\n"
						);
		}
		
		$mtime = microtime();
		$mtime = explode( ' ', $mtime );
		$queryendtime = $mtime[1] + $mtime[0];
		
		$this->querycount++;
		if( $no_debug != 1 ) {
			$this->querytimes[] = $queryendtime - $querystarttime;
			$this->querylist[]  = "Delete( \"{$dn}\" )";
		}
	}
	
	private function delete_helper( $dn, $recursive = true ) {
//echo "Start DN [{$dn}]<br />";
		if ( $recursive == false ) {
			return @ldap_delete( $this->_connection, $dn );
		} else {
			$sr = @ldap_list( $this->_connection, $dn, "ObjectClass=*", array("") );
			$info = @ldap_get_entries( $this->_connection, $sr );
			if ( $info["count"] > 0 ) {
				unset( $info["count"] );
				foreach ( $info as $i => $row ) {
					$result = $this->delete_helper( $row['dn'], $recursive );
					if ( !$result )
						return $result;			
				}
			}
//echo "Deleting DN [{$dn}]<br />";
			return @ldap_delete( $this->_connection, $dn );
		}
	}

	public function rename( $old_dn, $new_dn ) {
		if ( !$this->_connection ) $this->connect();
		
		if ( $old_dn == $new_dn ) return;
		
		$mtime = microtime();
		$mtime = explode( ' ', $mtime );
		$querystarttime = $mtime[1] + $mtime[0];
		
		$arg1 = $old_dn;
		$arg2 = substr( $new_dn, 0, strpos( $new_dn, "," ) );
		$arg3 = substr( $new_dn, strpos( $new_dn, "," ) + 1 );

		//$status = @ldap_rename( $this->_connection, $arg1, $arg2, $arg3, true );
		$status = $this->rename_helper( $arg1, $arg2, $arg3 );

		if ( !$status ) {
			$this->error( "<b>Notes:</b><br>\n
						   Could not complete a query to the database!<br>\n
						   <br>\n
						   <b>Query:</b><br \>\nRename( \"{$arg1}\", \"{$arg2}\", \"{$arg3}\" )<br>\n<br>\n
						   <b>Error:</b><br \>\n<b>#".ldap_errno( $this->_connection ).":</b> ".ldap_error( $this->_connection )."<br>\n"
						);
		}
		
		$mtime = microtime();
		$mtime = explode( ' ', $mtime );
		$queryendtime = $mtime[1] + $mtime[0];
		
		$this->querycount++;
		if( $no_debug != 1 ) {
			$this->querytimes[] = $queryendtime - $querystarttime;
			$this->querylist[]  = "Rename( \"{$arg1}\", \"{$arg2}\", \"{$arg3}\" )";
		}
	}
	
	private function rename_helper( $arg1, $arg2, $arg3 ) {
//echo "=== Arg1: {$arg1}, Arg2: {$arg2}, Arg3: {$arg3}<br />";
	
		if ( @ldap_rename( $this->_connection, $arg1, $arg2, $arg3, true ) )
			return true;
		
		// Copy Base Record to new location
		$item = $this->searchbase( $arg1, "objectClass=*" );
		$item = $item[0];
		unset( $item["dn"] );
		$t = substr( $arg1, 0, strpos( $arg1, "," ) );
		$p = explode( "=", $t );
		if ( $t != $arg2 ) {
			$item[ $p[0] ] = str_replace( $p[0]."=", "", $arg2 );
		}
//echo "+++ Add $arg2,$arg3 (<pre>".print_r( $item, true )."</pre>)<br />";
		$this->add( $arg2.",".$arg3, $item );

		// Get Children Entries
		$sr = @ldap_list( $this->_connection, $arg1, "ObjectClass=*", array("") );
		$info = @ldap_get_entries( $this->_connection, $sr );
		unset( $info["count"] );

		foreach ( $info as $i => $row ) {
			$newdn = str_ireplace( $arg1, $arg2.",".$arg3, $row["dn"] );
			
			$newarg1 = $row["dn"];
			$newarg2 = substr( $newdn, 0, strpos( $newdn, "," ) );
			$newarg3 = substr( $newdn, strpos( $newdn, "," ) + 1 );
			
			$status = $this->rename_helper( $newarg1, $newarg2, $newarg3 );
			if ( !$status )
				return false;
		}
		
//echo "--- Delete $arg1<br />";
		$this->delete( $arg1 );
		
		return true;
	}



	function getByDN( $dn ) {
		if ( ( $pos = strpos( $dn, "," ) ) === false )
			return NULL;
			
		$filter = substr( $dn, 0, $pos );
		$base = substr( $dn, $pos + 1 );

		$u = $this->search( $base, $filter, array(), 1 );		
		
		if ( count( $u ) != 1 )
			return NULL;
	
		return $u[0];
	}

	function compare( $dn, $attr, $val = NULL ) {
		if ( !$this->_connection ) $this->connect();
		
		$mtime = microtime();
		$mtime = explode( ' ', $mtime );
		$querystarttime = $mtime[1] + $mtime[0];
		
		if ( $val == NULL && is_array( $attr ) ) {
			$status = true;
			foreach ( $attr as $n => $v ) {
				//if ( is_array( $v ) && count( $v ) == 1 ) $v = $v[0];
				$ts = @ldap_compare( $this->_connection, $dn, $n, $v );
				if ( $ts === true ) {
					continue;
				} else if ( $ts === false ) {
					$status = false;
					break;
				} else {
					$status = false;
					break;
				}
			}
		} else {
			if ( is_array( $val ) && count( $val ) == 1 ) $val = $val[0];
			$status = @ldap_compare( $this->_connection, $dn, $attr, $val );
		}
		
		if ( $status < 0 ) {
			$this->error( "<b>Notes:</b><br>\n
						   Could not complete a query to the database!<br>\n
						   <br>\n
						   <b>Query:</b><br \>\nCompare( \"{$dn}\", \"{$attr}\", \"{$val}\" )<br>\n<br>\n
						   <b>Error:</b><br \>\n<b>#".ldap_errno( $this->_connection ).":</b> ".ldap_error( $this->_connection )."<br>\n"
						);
		}
		
		$mtime = microtime();
		$mtime = explode( ' ', $mtime );
		$queryendtime = $mtime[1] + $mtime[0];
		
		$this->querycount++;
		if( $no_debug != 1 ) {
			$this->querytimes[] = $queryendtime - $querystarttime;
			$this->querylist[]  = "Rename( \"{$arg1}\", \"{$arg2}\", \"{$arg3}\" )";
		}
		
		return $status;
	}

	public function createFilter( $filters = array() ) {
		$filter_list = "";
		foreach ( $filters as $k => $v ) {
			if ( eregi( "\|\|", $k ) && eregi( "\|\|", $v ) ) {		
				$partsK = explode( "||", $k );
				$partsV = explode( "||", $v );
				if ( count( $partsK ) != count( $partsV ) ) continue;
				$filter_list .= "(|";
				foreach ( $partsK as $xK => $pK ) {
					$pV = $partsV[ $xK ];
					if ( $pK[0] == "!" )
						$filter_list .= "(!(".substr( $pK, 1 )."={$pV}))";
					else
						$filter_list .= "({$pK}={$pV})";
				}
				$filter_list .= ")";
			} else
			
		
			if ( eregi( "\|\|", $v ) ) {
				$parts = explode( "||", $v );
				$filter_list .= "(|";
				foreach ( $parts as $p ) {
					$p = trim( $p );
					if ( $k[0] == "!" )
						$filter_list .= "(!(".substr( $k, 1 )."={$p}))";
					else
						$filter_list .= "({$k}={$p})";
					//$filter_list .= "({$k}={$p})";
				}
				$filter_list .= ")";
			} else if ( eregi( "&amp;&amp;", $v ) ) {
				$parts = explode( "&amp;&amp;", $v );
				$filter_list .= "(&";
				foreach ( $parts as $p ) {
					$p = trim( $p );
					if ( $k[0] == "!" )
						$filter_list .= "(!(".substr( $k, 1 )."={$p}))";
					else
						$filter_list .= "({$k}={$p})";
					//$filter_list .= "({$k}={$p})";
				}
				$filter_list .= ")";
			} else {
				if ( $k[0] == "!" )
					$filter_list .= "(!(".substr( $k, 1 )."={$v}))";
				else
					$filter_list .= "({$k}={$v})";
			}
		}

		return $filter_list;
	}

	public function prepareFields( $fields, $type ) {
		foreach ( $fields as $n => $v ) {
			if ( $n == "userpassword" ) {
				if ( $v != "" ) {
					$fields[ $n ] = "{CRYPT}".crypt( $v );
				} else {
					if ( $type == "add" )
						unset( $fields[ $n ] );
					else if ( $type == "modify" )
						unset( $fields[ $n ] );
				}
			} else if ( $v == "" ) {
				if ( $type == "add" )
					unset( $fields[ $n ] );
				else if ( $type == "modify" )
					$fields[ $n ] = array();
			} else if ( is_array( $v ) ) {
				sort( $fields[ $n ] );
			}
		}
		return $fields;
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

class ASO_Db_LDAP_Exception extends ASO_Db_Abstract_Exception
{}