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

/**
 * @see ASO_Exception
 */
require_once 'ASO/Exception.php';

/**
 * Display adapter to show output from the controller through a view.
 *
 * @category   Colony
 * @package    ASO
 * @copyright  Copyright (c) Army of Bees (www.armyofbees.com)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 */
class ASO_Display
{
    /**
     * Display backend
     * @var ASO_Display
     */
    private static $dispBackend = null;

    /**
     * Creates the display adapter using the backend passed.
     *    
     * @param string $backend The name of the backend to use.
     * @param array $config The configuration to pass to the backend.
     * @return void
     */
    public static function factory( $backend, $config = array() )
    {
        // Verify that backend parameters are in an array.
        if( !is_array( $config ) )
            throw new ASO_Display_Exception( 'Backend parameters must be in an array' );

        // Verify that an backend name has been specified.
        if( !is_string( $backend ) || empty( $backend ) )
            throw new ASO_Display_Exception( 'Backend name must be specified in a string' );

        // Load the backend class.
        require_once 'ASO/Display/'.$backend.'.php';

        // Create an instance of the backend, passing the config to it.
        $backendName = 'ASO_Display_' . $backend;
        self::$dispBackend = new $backendName( $config );
    }

    /**
     * Tells the display backend to show its display
     *
     * @param array $view The view to display
     * @param array $data The data to be used in the view
     * @return void
     */
    public static function display( $view, $data = array() )
    {
        // Verify that the display backend has been constructed by the factory.
        if( null === self::$dispBackend )
            throw new ASO_Display_Exception( 'Backend not created by factory; please run factory first' );
            
        // Verify that backend parameters are in an array.
        if( !is_array( $data ) )
            throw new ASO_Display_Exception( 'Display data must be in an array' );

        // Verify that an backend name has been specified.
        if( !is_string( $view ) || empty( $view ) )
            throw new ASO_Display_Exception( 'View must be specified in a string' );

        self::$dispBackend->runDisplay( $view, $data );
    }
}

class ASO_Display_Exception extends ASO_Exception
{}
