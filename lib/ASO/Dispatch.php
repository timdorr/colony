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
 * @see ASO_Display
 */
require_once 'ASO/Display.php';

/**
 * Dispatcher to route HTTP requests into controllers and back out to a display 
 * adapter. 
 *
 * @category   ASOworx
 * @package    ASO
 * @copyright  Copyright (c) A Small Orange Software (http://www.asmallorange.com)
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
    protected $_baseUrl = null;
    
    /**
     * Determines if exceptions should escape the ASO_Core object or be released to 
     * the method caller. 
     * @var boolean
     */
    protected $_throwExceptions = false;
    
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
        $this->config =& $CONFIG;

        $this->_baseUrl = preg_replace( '#index\.php.*#i', '', $_SERVER['PHP_SELF'] );
        
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
     * Starts execution of the ASOworx framework.
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
            if( $this->throwExceptions() )
            {
                throw $e;
            }
            else
            {
                print '<pre>';
                print $e;
                print '</pre>';
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
        $this->_splitURI();
    
        // Check that controller class exists
        if( !file_exists( 'app/controllers/' . $this->action . '.php' ) )
            throw new ASO_Dispatch_Exception( "Controller ($this->action) not found" );

        // Instantiate the action controller
        require_once 'controllers/' . $this->action . '.php';
        $action = ucfirst( $this->action ) . '_Controller';
        $controller = new $action( $this->config );

        // If a method wasn't defined in the URI, grab the default from the controller
        if( $this->method == '' )
            $this->method = $controller->defaultMethod;

        // Check that method call exists
        if( !method_exists( $controller, $this->method ) )
            throw new ASO_Dispatch_Exception( 'Method not found: ' . $action . '::' . $method );

        $controller->{$this->method}( $this->extra );
        
        $controller->completeDispatch();

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
        $baseurl = preg_quote( $this->_baseUrl );
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
                        if( is_numeric( $mapping['extra'] ) )
                            $this->extra = $matches[$mapping['extra']];
                        else
                            $this->extra = $mapping['extra'];
                    
                    }
                    
                    // Don't do any more URI splitting if we've found a match
                    return;
                }
            }
            
        }
        
        $tokens = explode( '/', $method_string );
        
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
}

class ASO_Dispatch_Exception extends ASO_Exception
{}