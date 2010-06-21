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

/* Catch errors as exceptions */
set_error_handler(create_function('$x, $y', 'throw new Exception($y, $x);'), E_ALL & ~E_NOTICE);

/**
 * @see ASO_Exception
 */
require_once 'ASO/Exception.php';

/**
 * @see ASO_Registry
 */
require_once 'ASO/Registry.php';

/**
 * @see ASO_Input
 */
require_once 'ASO/Input.php';

/**
 * @see ASO_Display
 */
require_once 'ASO/Display.php';

/**
 * Dispatcher to route HTTP requests into controllers and back out to a display
 * adapter.
 *
 * @category   Colony
 * @package    ASO
 * @copyright  Copyright (c) Army of Bees (www.armyofbees.com)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 */
class ASO_Dispatch
{
    /**
     * Application config
     * @var array
     */
    public $config = array();

    /**
     * Base URL
     * @var string
     */
    protected $_baseURL = null;

    /**
     * Determines if exceptions should escape the ASO_Core object or be released to
     * the method caller.
     * @var boolean
     */
    protected $_throwExceptions = false;

    /**
     * Determines if exceptions should be logged.
     * @var boolean
     */
    protected $_logExceptions = false;

    /**
     * Determines if exceptions should be emailed to an administrator.
     * @var boolean
     */
    protected $_emailExceptions = false;

    /**
     * Default action if not specified in HTTP request URI
     * @var string
     */
    protected $_defaultAction = 'index';

    /**
     * Action from HTTP request URI
     * @var string
     */
    public $action = '';

    /**
     * Method from HTTP request URI
     * @var string
     */
    public $method = '';

    /**
     * Extra data from HTTP request URI
     * @var mixed
     */
    public $extra = null;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        global $CONFIG;
        $conf =& ASO_Registry('config');
        $conf = $this->config =& $CONFIG;

        $this->_setupExceptionHandling();

        $this->_baseURL = preg_replace( '#index\.php.*#i', '', $_SERVER['PHP_SELF'] );

        ASO_Display::factory( $this->config['display_backend'] );
    }

    /**
     * Sets the throwExceptions flag and retreives the current status.
     *
     * By default, exceptions are caught by the ASO_Dispatch class. Enabling
     * this flag will allow them to pass back to the caller.
     *
     * @param boolean $flag
     * @return void
     */
    public function throwExceptions( $flag = null )
    {
        if( $flag !== null )
        {
            $this->_throwExceptions = (bool) $flag;
            return $this;
        }

        return $this->_throwExceptions;
    }

    /**
     * Sets the logExceptions flag and retreives the current status.
     *
     * By default, exceptions are caught by the ASO_Dispatch class.
     * Enabling this flag will additionally log them to a file defined
     * by $CONFIG['exception_log'] (in app/Config.php).
     *
     * @param boolean $flag
     * @return void
     */
    public function logExceptions( $flag = null )
    {
        if( $flag !== null )
        {
            $this->_logExceptions = (bool) $flag;
            return $this;
        }

        return $this->_logExceptions;
    }

    /**
     * Sets the emailExceptions flag and retreives the current status.
     *
     * By default, exceptions are caught by the ASO_Dispatch class.
     * Enabling this flag will additionally email them to an administrator
     * (or whoever's email is passed in as the 2nd parameter).
     *
     * @param boolean $flag
     * @return void
     */
    public function emailExceptions( $flag = null, $emailAddress = 'nobody' )
    {
        if( $flag !== null )
        {
            $this->_emailExceptions = (bool) $flag;
            return $this;
        }

        return $this->_emailExceptions;
    }

    /**
     * Starts execution of the Colony framework.
     *
     * @throws ASO_Exception
     * @return void
     */
    public function run()
    {
        try
        {
            $this->_dispatch();
        }
        catch( Exception $e )
        {
            // Log the exception if we're configured to do that
            if( $this->logExceptions() )
            {
                if(!isset( $this->controller )) {
                    require_once('ASO/Controller.php');
                    call_user_func_array( array( new ASO_Controller($this->config), 'logException'), array($e) );
                } else {
                    call_user_func_array( array( $this->controller, 'logException'), array($e) );
                }
            }
            
            // Email the exception if we're configured to do that
            if( $this->emailExceptions() )
            {
                if(!isset( $this->controller )) {
                    require_once('ASO/Controller.php');
                    call_user_func_array( array( new ASO_Controller($this->config), 'emailException'), array($e) );
                } else {
                    call_user_func_array( array( $this->controller, 'emailException'), array($e) );
                }
            }
             
            // Throw the exception if we're configured to do that
            if( $this->throwExceptions() )
            {
                throw $e;
            }
            else
            {
                if( file_exists( 'app/views/error.tpl' ) )
                {
                    $error_message = preg_replace( '/mysql_connect\([^)]+?\)/im', 'mysql_connect()', $e );
                    ASO_Display::display( 'error', array( 'config' => $this->config,
                                                          'error' => $error_message,
                                                          'exception' => $e ) );
                }
                else
                {
                    print '<pre>';
                    print $e;
                    print '</pre>';
                }
            }
        }
    }

    /**
     * Dispatches an HTTP request to its assigned controller
     *
     * @throws ASO_Dispatch_Exception
     * @return void
     */
    protected function _dispatch()
    {
        $this->_loadRouting();
        $this->_splitURI();

        // Check that controller class exists
        if( !file_exists( 'app/controllers/' . $this->action . '.php' ) )
        {
            if( $this->throwExceptions() || !file_exists( 'app/views/404.tpl' ) )
            {
                header( 'HTTP/1.1 404 Not Found' );
                throw new ASO_Dispatch_Exception( "Controller ($this->action) not found" );
            }
            else
            {
                header( 'HTTP/1.1 404 Not Found' );
                ASO_Display::display( '404' );
                return false;
            }
        }

        // Instantiate the action controller
        require_once 'controllers/' . $this->action . '.php';
        $action = ucfirst( $this->action ) . '_Controller';
        $this->controller = $controller = new $action( array_merge( $this->config, array( 'baseURL' => $this->_baseURL ) ) );

        // If a method wasn't defined in the URI, grab the default from the controller
        if( $this->method == '' )
            $this->method = $controller->defaultMethod;

        // Check that method call exists
        if( !method_exists( $controller, $this->method ) )
            throw new ASO_Dispatch_Exception( 'Method not found: ' . $action . '::' . $this->method );

        // Save the local vars to the controller
        $controller->action = $this->action;
        $controller->method = $this->method;
        $controller->extra = $this->extra;
        $controller->_baseURL = $this->_baseURL;

        // Run the method
        if ( method_exists( $controller, "_setup" ) )
            $controller->_setup();
        $controller->{$this->method}( $this->extra );
        $controller->completeDispatch();

        // Save the local vars to the controller
        $controller->action = $this->action;
        $controller->method = $this->method;
        $controller->extra = $this->extra;
        $controller->_baseURL = $this->_baseURL;

        // Display back to the browser
        ASO_Display::display( $this->action . '/' . $this->method, get_object_vars( $controller ) );
    }

    /**
     * Splits the HTTP request URI into action, method, extra data. Applies routing.
     *
     * @return void
     */
    protected function _splitURI()
    {
        // Clean the URI of excess data
        $method_string = str_replace( 'index.php', '', $_SERVER['REQUEST_URI'] );
        $baseurl = preg_quote( $this->_baseURL );
        $method_string = preg_replace( "#^$baseurl#", '', $method_string );
        $method_string = preg_replace( '#\?.*#', '', $method_string );

        // Apply routing
        if( isset( $this->config['routing'] ) )
        {
            foreach( $this->config['routing'] as $pattern => $mapping )
            {
                // Check if the URI matches this routing pattern
                if( preg_match( $pattern, $method_string, $matches ) )
                {
                    if( isset( $mapping['action'] ) )
                    {
                        // If the mapping value is numeric, assign that part of the pattern
                        // Otherwise, it's just the value of the mapping
                        if( is_numeric( $mapping['action'] ) )
                            $this->action = $matches[$mapping['action']];
                        else
                            $this->action = $mapping['action'];

                    }

                    if( isset( $mapping['method'] ) )
                    {
                        if( is_numeric( $mapping['method'] ) )
                            $this->method = $matches[$mapping['method']];
                        else
                            $this->method = $mapping['method'];

                    }

                    if( isset( $mapping['extra'] ) )
                    {
                        if ( is_array( $mapping['extra'] ) ) {
                            $this->extra = array();
                            foreach( $mapping['extra'] as $n ) {
                                $temp = null;
                                if ( is_numeric( $n ) )
                                    $temp = ( array_key_exists( $n, $matches ) ) ? $matches[$n] : null;
                                else
                                    $temp = ( !empty( $n ) ) ? $n : null;
                                if ( !is_null( $temp ) )
                                    $this->extra[] = $temp;
                            }
                            if ( count( $this->extra ) <= 0 )
                                $this->extra = null;
                        } else if ( is_numeric( $mapping['extra'] ) )
                            $this->extra = ( array_key_exists( $mapping['extra'], $matches ) ) ? $matches[$mapping['extra']] : null;
                        else
                            $this->extra = ( !empty( $mapping['extra'] ) ) ? $mapping['extra'] : null;

                    }

                    // Don't do any more URI splitting if we've found a match
                    return;
                }
            }

        }

        //$tokens = explode( '/', $method_string );
        $tokens = array();
        if ( ( $pos = strpos( $method_string, "/" ) ) !== false ) {
            $tokens[0] = substr( $method_string, 0, $pos );
            $method_string = substr( $method_string, $pos + 1 );
            if ( ( $pos = strpos( $method_string, "/" ) ) !== false ) {
                $tokens[1] = substr( $method_string, 0, $pos );
                $method_string = substr( $method_string, $pos + 1 );
                if ( ( $pos = strpos( $method_string, "/" ) ) !== false ) {
                    $tokens[2] = explode( "/", $method_string );
                } else {
                    $tokens[2] = $method_string;
                }
            } else {
                $tokens[1] = $method_string;
            }
        } else {
            $tokens[0] = $method_string;
        }

        // Get the action
        if( empty( $tokens[0] ) )
            $this->action = $this->_defaultAction;
        else
            $this->action = $tokens[0];

        // Get the method
        if( empty( $tokens[1] ) )
            $this->method = '';
        else
            $this->method = $tokens[1];

        // Get the extra data
        if( empty( $tokens[2] ) )
            $this->extra = null;
        else
            $this->extra = $tokens[2];
    }

    /**
     * Loads routing from app/Routing.php  Note: routes were
     * previously stored in app/Config.php but moved so that
     * they could be versioned.
     *
     * @return void
     */
    protected function _loadRouting()
    {
        if( file_exists( './app/Routing.php' ) )
        {
            include( './app/Routing.php' );

            if( isset($ROUTING) )
            {
                global $CONFIG;
                $CONFIG['routing'] = $ROUTING;
            }
        }
    }

    private function _setupExceptionHandling()
    {
        // Exception logging
        if( !isset($this->config['log_exceptions']) || $this->config['log_exceptions'] !== false )
        {
            $this->logExceptions( true );
        }

        // Exception emailing
        if( isset($this->config['email_exceptions']) && $this->config['email_exceptions'] )
        {
            $this->emailExceptions( true );
        }

        // Exception throwing
        if( !isset($this->config['throw_exceptions']) || $this->config['throw_exceptions'] )
        {
            $this->throwExceptions( true );
        }
    }

}

class ASO_Dispatch_Exception extends ASO_Exception
{}
