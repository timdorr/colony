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
 * @see ASO_Exception
 */
require_once 'ASO/Exception.php';

/**
 * Abstract SQL database adapter class.
 *
 * @category   ASOworx
 * @package    ASO
 * @copyright  Copyright (c) A Small Orange Software (http://www.asmallorange.com)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 */
abstract class ASO_Db_Abstract
{
    /**
     * The configuration to connect to the database
     * @var array
     */
	protected $_config = array();
	
    /**
     * Database connection
     * @var resource
     */
    protected $_connection = null;

    /**
     * Query resource ID
     * @var resource
     */
	protected $_queryid = null;
	
    /**
     * The contents of the last query
     * @var string
     */
	public $query = "";
	
    /**
     * The count of queries so far
     * @var integer
     */
	public $querycount = 0;

    /**
     * An array of times each query took to process
     * @var array
     */
	public $querytimes = array();
	
    /**
     * An array of previous queries processed by the database
     * @var array
     */
	public $querylist = array();

	/**
	 * Constructor for the class. Just sets some values for other functions.
	 *
	 * @param array $config The configuration to use for connection
	 */
	public function __construct( $config = array() )
	{
        // Verify that configuration is in an array.
        if( !is_array( $config ) )
            throw new ASO_Db_Exception('Configuration must be in an array');
	
		$this->config = $config;
	}
	
	/**
	 * Queries the database. Runs through the sub-class's _query() function.
	 *
	 * @param string $query_string The query string to run
	 * @param boolean $no_debug Tells the function if it should record the query in the debug variables.
	 */
	public function query( $query_string, $no_debug = false )
	{
        // Do not error out, but don't process an empty query.
        if( $query_string == '' )
			return;

		$this->connect();
		
		if( !$no_debug )
		{
            $mtime = microtime();
            $mtime = explode( ' ', $mtime );
            $querystarttime = $mtime[1] + $mtime[0];
		}
		
		$this->_query( $query_string, $no_debug );
        $this->querycount++;
			
		if( !$no_debug )
		{
			$mtime = microtime();
			$mtime = explode( ' ', $mtime );
			$queryendtime = $mtime[1] + $mtime[0];

            $this->querytimes[] = $queryendtime - $querystarttime;
            $this->querylist[]  = $query_string;
        }

        return $this->_queryid;
	}
}

class ASO_Db_Abstract_Exception extends ASO_Exception
{}