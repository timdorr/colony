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
 * @package    Bee
 * @copyright  Copyright (c) Army of Bees (www.armyofbees.com)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 */
 
/**
 * @see Bee_Session_Abstract
 */
require_once 'Bee/Session/Abstract.php';

/**
 * @see Bee_Db_Propel
 */
require_once 'Bee/Db/Propel.php';

/**
 * @see Bee_Input
 */
require_once 'Bee/Input.php';

/**
 * Session backend using a Propel model
 *
 * @category   Colony
 * @package    Bee
 * @copyright  Copyright (c) Army of Bees (www.armyofbees.com)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 */
class Bee_Session_Propel
{
    /**
     * The session ID
     * @var string
     */
    private $_id = '';

    /**
     * Propel object
     * @var Session
     */
    private $_model = null;

    /**
     * Constructor for the class. Just sets some values for other functions.
     *
     * @param array $config The configuration to use for connection
     */
    public function __construct( &$config = array() )
    {
        // Verify that configuration is in an array.
        if( !is_array( $config ) )
            throw new Bee_Session_Propel_Exception('Configuration must be in an array');

        // Verify that a db object is passed
        if( !isset( $config['db'] ) )
            throw new Bee_Session_Propel_Exception('Configuration is missing database');
        if( !($config['db'] instanceof Bee_Db_Propel) )
            throw new Bee_Session_Propel_Exception('Configuration database is not Bee_Db_Propel');
        
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
        $input =& Bee_Input::filterInput();
        if ( !array_key_exists( 'session', $input ) )
        {
            $this->newSession();
        }
        else
        {
            $this->_id = $input['session'];

            // Grab the session data from the database
            $this->_model = SessionQuery::create()->findOneById( $this->_id );

            if( $this->_model !== null )
            {            
                // Make sure it hasn't timed out
                if( strtotime( $this->_model->getUpdatedAt() ) >= time() - $this->timeout )
                {   
                    setcookie( 'session', $this->_id, time() + $this->timeout, $this->path, $this->domain, FALSE, TRUE );
                }
                else
                {
                    $this->newSession();
                }
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
        // Initialize the model
        $this->_model = new Session();

        // Load the ID and data
        $this->_id = sha1( uniqid( microtime() ) );
        $this->_model->setId( $this->_id );
        $this->_model->setData( serialize( array() ) );

        // Save a cookie
        setcookie( 'session', $this->_id, time() + $this->timeout, $this->path, $this->domain, FALSE, TRUE );
    }

    /**
     * Saves the session into the database
     *
     * @param array $data The data to be stored into the session
     * @return void
     */
    public function saveSession( &$data )
    {
        // Save into the object
        $this->_model->setData( serialize( $this->_data ) );

        // Save the object
        $this->_model->save();

        // Clear out old sessions
		SessionQuery::create()->filterByUpdatedAt( time() - $this->timeout, '<' )->delete();
    }
    
    /**
     * Gets the stored data from the session
     *
     * @return array
     */
    public function getData()
    {
        $this->_loadSession();

        return unserialize( $this->_model->getData() );
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
    	$out .= "\t[id] => ".$this->_id."\n";
    	$out .= "\t[data] => ".str_replace( array( "\n", "    " ), array( "", "" ), print_r( $this->_data, true ) )."\n";
    	$out .= "}\n";
    	$out .= "</pre>";
    	return $out;
    }
    
}

class Bee_Session_Propel_Exception extends Bee_Session_Abstract_Exception
{}
