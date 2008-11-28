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
 * @see ASO_Session
 */
require_once 'ASO/Session.php';

/**
 * @see ASO_Exception
 */
require_once 'ASO/Exception.php';

/**
 * Error handling and assignment. Takes in errors for specific identifiers and
 * stores them in groups. This way a single form element, for example, can display
 * multiple errors with it's input.
 *
 * @category   ASOworx
 * @package    ASO
 * @copyright  Copyright (c) A Small Orange Software (http://www.asmallorange.com)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 */
class ASO_Error 
{
    /**
     * Errors stored by this error handler
     * @var array
     */
    private static $_errors = array();
    
    /**
     * Controller reference
     * @var ASO_Controller 
     */
    protected $_controller = null;

    /** 
     * Constructor
     *
     * @param array $controller The referenced controller
     */
    public function __construct( &$controller )
    {
        $this->_controller =& $controller;
        
        // Load errors from the session object
        $sess =& ASO_Registry('sess');
        self::$_errors = $sess['ASO_Error'];
        
        // Clear the session data for subsequent page loads
        unset( $sess['ASO_Error'] );
    }

    /**
     * Adds an error for the user to a group
     * 
     * @param string $id The identifier of this group of errors
     * @param string $message The error message
     */ 
    public function add( $id, $message )
    {
        if( !isset( self::$_errors[$id] ) )
            self::$_errors[$id] = array();
            
        self::$_errors[$id][] = $message;
    }
    
    /**
     * Looks up the errors in a group by ID
     * 
     * @param string $id The identifier of this error
     * @return array Collection of error messages
     */ 
    public function lookup( $id )
    {
        return self::$_errors[$id];
    }
    
    /**
     * Looks up the errors in a group by ID
     * 
     * @param string $id The identifier of this error
     * @return array Collection of error messages
     */ 
    public function getAll()
    {
        return self::$_errors;
    }
    
    /**
     * Sends the browser to the page with errors, passed via session
     * 
     * @param string $location Where to send the user if there are errors
     */ 
    public function trap( $location )
    {
        if( count( self::$_errors ) > 0 )
        {
            // Store the errors into the session for the following page load and redirect
            $sess =& ASO_Registry('sess');
            $sess['ASO_Error'] = self::$_errors;
            $this->_controller->redirect( $location );
        }
    }
    
    /**
     * Smarty function to format errors for easy reference in templates. 
     * Uses erroritem.tpl, if available.
     *
     * Takes in a "id" parameter with the ID of the error group to return. 
     * If "id" is empty or undefied, all errors are returned.
     * 
     * @param string $params Parameters passed to the Smarty function
     * @param Smarty $smarty Reference to the Smarty object calling this function
     */ 
    public static function formatError( $params, &$smarty )
    {
        if( empty( $params['id'] ) )
        {
            $errors = array();
            foreach( self::$_errors as $errorgroup )
                foreach( $errorgroup as $err )
                    $errors[] = $err;
        }
        else
            $errors = self::$_errors[$params['id']];

        $smarty->assign( array( 'erroritems' => $errors ) );

        if( file_exists( 'app/views/erroritem.tpl' ) )
            return $smarty->fetch( 'erroritem.tpl' );
        else
        {
            $output = "<ul>\n";
            foreach( $errors as $err )
                $output .= "<li>$err</li>\n";
            $output .= "</ul>";
            
            return $output;
        }
    }
}

class ASO_Error_Exception extends ASO_Exception
{}