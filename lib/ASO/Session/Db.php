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
 * @see ASO_Session_Abstract
 */
require_once 'ASO/Session/Abstract.php';

/**
 * @see ASO_Db_Abstract
 */
require_once 'ASO/Db/Abstract.php';

/**
 * @see ASO_Input
 */
require_once 'ASO/Input.php';

/**
 * Session backend using an ASO_Db database
 *
 * @category   ASOworx
 * @package    ASO
 * @copyright  Copyright (c) A Small Orange Software (http://www.asmallorange.com)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 */
class ASO_Session_Db
{
    /**
     * Database object
     * @var ASO_Db_Abstract
     */
    private $_db = null;

    /**
     * Constructor for the class. Just sets some values for other functions.
     *
     * @param array $config The configuration to use for connection
     */
    public function __construct( &$config = array() )
    {
        // Verify that configuration is in an array.
        if( !is_array( $config ) )
            throw new ASO_Session_Db_Exception('Configuration must be in an array');

        // Verify that a db object is passed
        if( !isset( $config['db'] ) )
            throw new ASO_Session_Db_Exception('Configuration is missing db');
        if( !($config['db'] instanceof ASO_Db_Abstract) )
            throw new ASO_Session_Db_Exception('Configuration is not ASO_Db_Abstract');
    
        $this->_db =& $config['db'];
        
        $this->timeout = $config['session_timeout'];
        $this->domain = $config['session_domain'];
        $this->path = $config['session_path'];
    }
    
    /**
     * Loads the session from the database
     *
     * @return void
     */
    private function _loadSession()
    {
        // Check if we've got an existing session stored
        $input =& ASO_Input::filterInput();
        if ( !array_key_exists( 'session', $input ) )
        {
            $this->newSession();
        }
        else
        {
            $this->_id = $input['session'];

            // Grab the session data from the database
            $result = $this->_db->get( 'session', "session_id = '{$this->_id}'" );

            if( $this->_db->num_rows() == 1 )
            {
                $this->_data = unserialize( $result['data'] );
                $this->_time = $result['time'];
                
                setcookie( 'session', $this->_id, time() + $this->timeout, $this->path, $this->domain, FALSE, TRUE );
            }
            else
            {
                $this->newSession();
            }
        }
    }

    /**
     * Creates a new empty session
     *
     * @return void
     */
    public function newSession()
    {
        $this->_id = sha1( uniqid( microtime() ) );
		$this->_time = time();
        $this->_data = array();

        setcookie( 'session', $this->_id, time() + $this->timeout, $this->path, $this->domain, FALSE, TRUE );

        $this->_db->insert( 'session',
                            array( 'session_id' => $this->_id,
                                   'data' => serialize( array() ),
                                   'time' => time() ) );
    }

    /**
     * Saves the session into the database
     *
     * @param array $data The data to be stored into the session
     * @return void
     */
    public function saveSession( &$data )
    {
        $this->_data = $data;

        $this->_db->update( 'session',
                            array( 'data' => serialize( $data ),
                                   'time' => time() ),
                            'session_id',
                            $this->_id );

        // Clear out old sessions
		$this->_db->query( "DELETE FROM session WHERE time < " . ( time() - $this->config['session_timeout'] ) );
    }
    
    /**
     * Gets the stored data from the session
     *
     * @return array
     */
    public function getData()
    {
        $this->_loadSession();

        return $this->_data;
    }

    /**
     * Gets the stored time from the session
     *
     * @return int
     */    
    public function getTime()
    {
        $this->_loadSession();

        return $this->_time;
    }

    /**
     * Returns the string representation of the object
     *
     * @return string
     */
    public function __toString() {
        //$this->_loadSession();
    	$out = "";
    	$out .= "<pre>";
    	$out .= "Session Object\n";
    	$out .= "{\n";
    	$out .= "\t[session_id] => ".$this->_id."\n";
    	$out .= "\t[time] => ".date( DATE_RFC822, $this->_time )."\n";
    	$out .= "\t[data] => ".str_replace( array( "\n", "    " ), array( "", "" ), print_r( $this->_data, true ) )."\n";
    	$out .= "}\n";
    	$out .= "</pre>";
    	return $out;
    }
    
}

class ASO_Session_Db_Exception extends ASO_Session_Abstract_Exception
{}