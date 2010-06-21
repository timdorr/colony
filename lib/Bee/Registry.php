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
 * Registry class for accessing variables globally.
 *
 * @category   Colony
 * @package    ASO
 * @copyright  Copyright (c) Army of Bees (www.armyofbees.com)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 */
class Bee_Registry
{
    /**
     * Singleton instance of registry
     * @var Bee_Registry
     */
    private static $instance;

    /**
     * Stored variables in registry
     * @var array
     */
    private $vars = array();
    
    /**
     * Stores a variable in the registry
     * 
     * @param string $key Key of the variable
     * @param mixed $var Reference to the variable
     * @return void
     */
    public function setVar( $key, $var )
    {
        $this->vars[$key] = null;
        $this->vars[$key] &= $var;
    }
    
    /**
     * Gets a variable from the registry
     * 
     * @param string $key Key of the variable
     * @return mixed
     */
    public function &getVar( $key )
    {
        return $this->vars[$key];
    }
    
    /**
     * Gets the singleton instance of the registry
     * 
     * @return Bee_Registry
     */   
    public static function &getInstance() 
    {
        if( !isset( self::$instance ) )
            self::$instance = new Bee_Registry;

        return self::$instance;
    }
}
 
/**
 * Public function for convienience. Wraps around Bee_Registry::getVar()
 * 
 * @param string $key Key of the variable
 * @return mixed
 */
function &Bee_Registry( $key )
{
    $reg =& Bee_Registry::getInstance();

    return $reg->getVar( $key );
}
