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

// Including the Propel libraries into the path
set_include_path( 'lib/Propel/runtime/lib' . PATH_SEPARATOR . get_include_path() );

/**
 * @see Bee_Db_Abstract
 */
require_once 'Bee/Db/Abstract.php';

/**
 * @see Propel
 */
require_once 'Propel/runtime/lib/Propel.php';

/**
 * Propel database adapter.
 *
 * @category   Colony
 * @package    Bee
 * @copyright  Copyright (c) Army of Bees (www.armyofbees.com)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 */
class Bee_Db_Propel extends Bee_Db_Abstract
{
	/**
	 * Constructor for the class. Just sets some values for other functions.
	 *
	 * @param array $config The configuration to use for connection
	 */
	public function __construct( $config = array() )
	{
        // Verify that configuration is in an array.
        if( !is_array( $config ) )
            throw new Bee_Db_Exception('Configuration must be in an array');
	
        // Configure Propel to talk to the database
		Propel::init( 'app/model/conf/colony-conf.php' );
		
		// Make sure we can see the models
		set_include_path( 'app/model' . PATH_SEPARATOR . get_include_path() );
	}

}