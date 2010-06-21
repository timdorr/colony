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
 * @see Bee_Input
 */
require_once 'Bee/Input.php';

/**
 * @see Bee_Db
 */
require_once 'Bee/Db.php';

/**
 * @see Bee_Session
 */
require_once 'Bee/Session.php';

/**
 * @see Bee_Error
 */
require_once 'Bee/Error.php';

/**
 * @see Bee_Exception
 */
require_once 'Bee/Exception.php';

/**
 * Controller parent class. 
 *
 * @category   Colony
 * @package    Bee
 * @copyright  Copyright (c) Army of Bees (www.armyofbees.com)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 */
class Bee_Controller
{
    /**
     * Application config
     * @var array
     */
    protected $config = array();

    /**
     * Processed input from Bee_Input::filter_input()
     * @var array
     */
    protected $input = array();
    
    /**
     * Database connection
     * @var Bee_Db_Astract
     */
    protected $db = array();
    
    /**
     * Current session data
     * @var array 
     */
    public $sess = array();
    
    /**
     * Error storage
     * @var Bee_Error
     */
    protected $error = null;
    
    /**
     * Session object
     * @var Bee_Session_Abstract 
     */
    protected $_session = null;

    /**
     * The base URL from the dispatcher
     * @var string
     */
    protected $baseURL = '';
    
    /**
     * The default method to run if none is specified
     * @var string
     */
    public $defaultMethod = 'main';

    /** 
     * Constructor
     *
     * Sets up the controller environment at the same time, including input filtering
     * and session access.
     *
     * @param array $config System configuration for setting up environment
     */
    public function __construct( $config )
    {
        // Verify that controller config is in an array.
        if( !is_array( $config ) )
            throw new Bee_Controller_Exception('Controller configuraion must be in an array');

        $this->_setEnvironment();

        $this->baseURL = $config['baseURL'];
        $this->config = $config;

        $input =& Bee_Registry('input');
        $input = $this->input =& Bee_Input::filterInput();

        $db =& Bee_Registry('db');
        $db = $this->db = Bee_Db::factory( $config['db_type'], $config );

        $this->_session = Bee_Session::factory( $config['session_type'],
                                                array( 'db' => &$this->db,
                                                       'session_timeout' => $config['session_timeout'],
                                                       'session_domain' => $config['session_domain'],
                                                       'session_path' => $config['session_path'] ) );
        $sess =& Bee_Registry('sess');
        $sess = $this->_session->getData();
        $this->sess =& $sess;

        $error =& Bee_Registry('error');
        $error = $this->error = new Bee_Error( $this );
    }
    
    /**
     * Runs any completion tasks after the controller event is dispatched
     * 
     * @return void
     */
    public function completeDispatch()
    {
        $this->errors = $this->error->getAll();
        $this->_session->saveSession( $this->sess );
    }
    
    
    /**
     * Redirects to another location.
     * 
     * @param string $location The location to redirect to
     * @return void
     */
    public function redirect( $location ) 
    {
        $this->_session->saveSession( $this->sess );

		header( "Location: {$this->baseURL}$location" );
		exit();
    }

    /**
     * Ensures a stable execution environment
     * 
     * @return void
     */
    protected function _setEnvironment()
    {
        error_reporting( E_ALL ^ E_NOTICE );
        if( version_compare( PHP_VERSION, '5.3.0', '<' ) )
            set_magic_quotes_runtime( 0 );
    }
    
    /**
     * Default setup function
     * 
     * @return void
     */
    public function _setup() { }
    
    /**
     * Default callback for logging runtime exceptions.
     * 
     * Override this method in a subclass of Bee_Controller
     * to log this error to someplace other than
     * var/log/exceptions.log.
     * 
     * @param Exception $e The exception that was thrown at runtime
     */ 
    public function logException( $e )
    {
        $date = date( 'r' );
        $postVars = print_r($_POST, true);
        $sessionVars = print_r($this->sess, true);
        $serverVars = print_r($_SERVER, true);
        $message = <<<EXCEPTIONLOG
----------
[Colony] Uncaught Exception:
{$date}
{$e->getMessage()} ({$e->getFile()}:{$e->getLine()})
{$e->getTraceAsString()}

Post: {$postVars}

Session: {$sessionVars}

Server: {$serverVars}
EXCEPTIONLOG;

        $file = 'var/log/exceptions.log';
        if( file_exists( $file ) && is_writable($file) )
        {
            error_log($message, 3, $file);
        }
    }
    
    /**
     * Default callback for emailing runtime exceptions.
     * 
     * Override this method in a subclass of Bee_Controller
     * to change the default behavior.
     * 
     * @param Exception $e The exception that was thrown at runtime
     */ 
    public function emailException( $e )
    {
        if( !isset($this->config['email_exceptions_address']) )
        {
            throw new Bee_Controller_Exception( "Exception emailing turned on but no address specified.  Please specify \$CONFIG['email_exceptions_address'] in app/Config.php" );
        }
        
        $to = $from = $this->config['email_exceptions_address'];
        $subject = "[Colony] Uncaught Exception";

        $date = date( 'r' );
        $postVars = print_r($_POST, true);
        $sessionVars = print_r($this->sess, true);
        $serverVars = print_r($_SERVER, true);
        $message = <<<EMAILMESSAGE
An uncaught exception occurred; here are the details:
{$date}

{$e->getMessage()} ({$e->getFile()}:{$e->getLine()})
{$e->getTraceAsString()}

Post: {$postVars}

Session: {$sessionVars}

Server: {$serverVars}
EMAILMESSAGE;
    
        mail($to, $subject, $message, "From: {$from}\r\rX-Mailer: PHP/" . phpversion());
    }
    
}

class Bee_Controller_Exception extends Bee_Exception
{}
