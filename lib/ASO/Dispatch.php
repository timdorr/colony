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
     * Default action if not specified in HTTP request
     * @var string
     */
    protected $_defaultAction = 'index';
    
    /**
     * Default method if not specified in HTTP request
     * @var string
     */
    protected $_defaultMethod = 'main';
    
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
        // Check that controller class exists
        if( !file_exists( 'app/controllers/' . $this->_getAction() . '.php' ) )
            throw new ASO_Dispatch_Exception( "Controller not found" );
            
        require_once 'controllers/' . $this->_getAction() . '.php';
        $action = ucfirst( $this->_getAction() ) . '_Controller';
        $controller = new $action( $this->config );
        
        $this->_defaultMethod = $controller->defaultMethod;
        $method = $this->_getMethod();

        // Check that method call exists
        if( !method_exists( $controller, $method ) )
            throw new ASO_Dispatch_Exception( 'Method not found: ' . $action . '::' . $method );
            
        $controller->{$method}( $this->_getExtra() );
        
        $controller->completeDispatch();

        ASO_Display::display( $this->_getAction() . '/' . $method, get_object_vars( $controller ) );
        
    }
    
    /**
     * Returns the action from the HTTP request URI
     * 
     * @return string The action requested
     */
    protected function _getAction()
    {
        $method_string = str_replace( 'index.php', '', $_SERVER['REQUEST_URI'] );
        $baseurl = preg_quote( $this->_baseUrl );
        $method_string = preg_replace( "#^$baseurl#", '', $method_string );
        $method_string = preg_replace( '#\?.*#', '', $method_string );
        $tokens = explode( '/', $method_string );

        if( empty( $tokens[0] ) )
            return $this->_defaultAction;
        else
            return $tokens[0];
    }
    
    /**
     * Returns the method from the HTTP request URI
     * 
     * @return string The method requested
     */
    protected function _getMethod()
    {
        $method_string = str_replace( 'index.php', '', $_SERVER['REQUEST_URI'] );
        $baseurl = preg_quote( $this->_baseUrl );
        $method_string = preg_replace( "#^$baseurl#", '', $method_string );
        $method_string = preg_replace( '#\?.*#', '', $method_string );
        $tokens = explode( '/', $method_string );

        if( empty( $tokens[1] ) )
            return $this->_defaultMethod;
        else
            return $tokens[1];
    }
    
    /**
     * Returns any extra data from the HTTP request URI
     * 
     * @return string The extra data requested
     */
    protected function _getExtra()
    {
        $method_string = str_replace( 'index.php', '', $_SERVER['REQUEST_URI'] );
        $baseurl = preg_quote( $this->_baseUrl );
        $method_string = preg_replace( "#^$baseurl#", '', $method_string );
        $method_string = preg_replace( '#\?.*#', '', $method_string );
        $tokens = explode( '/', $method_string );

        if( empty( $tokens[2] ) )
            return null;
        else
            return $tokens[2];
    }
}

class ASO_Dispatch_Exception extends ASO_Exception
{}