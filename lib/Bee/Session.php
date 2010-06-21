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
 * @see Bee_Exception
 */
require_once 'Bee/Exception.php';

/**
 * Session handling and data storage
 *
 * @category   Colony
 * @package    ASO
 * @copyright  Copyright (c) Army of Bees (www.armyofbees.com)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 */
class Bee_Session
{
    /**
     * Creates the session handler using the adapter passed.
     *    
     * @param string $adapter The name of the backend to use.
     * @param array $config The configuration to pass to the backend adapter.
     * @return void
     */
    public static function factory( $adapter, $config = array() )
    {
        // Verify that adapter parameters are in an array.
        if( !is_array( $config ) )
            throw new Bee_Session_Exception( 'Adapter parameters must be in an array' );

        // Verify that an adapter name has been specified.
        if( !is_string( $adapter ) || empty( $adapter ) )
            throw new Bee_Session_Exception( 'Adapter name must be specified in a string' );
        
        // Load the adapter class.
        require_once 'Bee/Session/'.$adapter.'.php';

        // Create an instance of the adapter, passing the config to it.
        $adapterName = 'Bee_Session_' . $adapter;
        $sessAdapter = new $adapterName( $config );
        
        return $sessAdapter;
    }
}

class Bee_Session_Exception extends Bee_Exception
{}
