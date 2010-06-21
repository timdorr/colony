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
 * @see Bee_Exception
 */
require_once 'Bee/Exception.php';

/**
 * Input filtering. Removes bad characters and reduces potential injection vectors
 *
 * @category   Colony
 * @package    Bee
 * @copyright  Copyright (c) Army of Bees (www.armyofbees.com)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 */
class Bee_Input 
{
    /**
     * Cached input data
     * @var array
     */
    private static $input = null;

	/**
	 * Cleans input. Caches locally.
	 *
	 * Takes all available $_GET, $_POST, and $_COOKIE input and reduces the
	 * chances of attacks and problems.
	 *
	 * Also cleans the source IP address.
	 *
	 * @return array The cleaned input
	 */
	public static function &filterInput()
	{
		global $HTTP_CLIENT_IP, $REQUEST_METHOD, $REMOTE_ADDR, $HTTP_PROXY_USER, $HTTP_X_FORWARDED_FOR;

		if( self::$input == null )
		{
            $super = array( &$_GET, &$_POST, &$_COOKIE );

            $return = array();
            foreach( $super as $duper )
            {
                if( is_array( $duper ) )
                {
                    foreach( $duper as $k => $v )
                    {
                        if( is_array( $duper[$k] ) )
                        {
                            foreach( $duper[$k] as $k2 => $v2 )
                            {	
                                if( is_array( $duper[$k][$k2] ) )
                                {
                                    foreach( $duper[$k][$k2] as $k3 => $v3 )
                                        $return[ self::cleanKey($k) ][ self::cleanKey($k2) ][ self::cleanKey( $k3 ) ] = self::cleanValue( $v3 );
                                }
                                else
                                    $return[ self::cleanKey($k) ][ self::cleanKey( $k2 ) ] = self::cleanValue( $v2 );
                            }
                        }
                        else
                            $return[ self::cleanKey($k) ] = self::cleanValue( $v );
                    }
                }
            }

            // Sort out the accessing IP
            $return['IP_ADDRESS'] = $_SERVER['REMOTE_ADDR'];
            $return['IP_ADDRESS'] = preg_replace( "/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})/", 
                                                  "\\1.\\2.\\3.\\4", 
                                                  $return['IP_ADDRESS'] );

            $return['REQUEST_METHOD'] = strtolower( $REQUEST_METHOD );

            self::$input = $return;
        }

		return self::$input;
	}

	/**
	 * Handles some potential exploits with key names
	 * 
	 * @param string $key The key to clean
	 * @return string The cleaned key
	 */
    public static function cleanKey( $key ) 
    {
    	if ( $key == "" )
    	{
    		return "";
    	}
    	
    	$key = str_replace(  ".."               , ""  , $key );
    	$key = preg_replace( "/\_\_(.+?)\_\_/"  , ""  , $key );
    	$key = preg_replace( "/^([\w\.\-\_]+)$/", "$1", $key );
    	
    	return $key;
    }

	/**
	 * Cleans a value
	 *
	 * Handles most problems with SQL queries. Not neccessarily safe for 
	 * input displayed back to other users.
	 *
	 * @param mixed $val The value to clean
	 * @return string The cleaned value
	 */
    public static function cleanValue( $val ) 
    {
    	if ( $val == "" )
    	{
    		return "";
    	}
 
		if ( get_magic_quotes_gpc() )
    	{
    		$val = stripslashes($val);
    	}
    	
    	$val = htmlentities( $val, ENT_QUOTES );

    	return $val;
    }
    
	/**
	 * Cleans a value.
	 *
	 * Handles more potential problems with input, such as XSS attacks.
	 *
	 * @param mixed $val The value to clean
	 * @return string The cleaned value
	 */
    public static function cleanerValue( $val ) 
    {
    	if ( $val == "" )
    	{
    		return "";
    	}
    	
    	$replace = array(  "&#032;" => " ",
    					   chr(0xCA) => "",
    					   "&" => "&amp;",
    					   "<\!--" => "&#60;&#33;--",
    					   "-->" => "--&#62;",
    					   "!" => "&#33;",
    					   "'" => "&#39;",
    					   ">" => "&gt;",
    					   "<" => "&lt;",
    					   "\"" => "&quot;" );
    					   
    	$val = str_replace( array_keys( $replace ), $replace,  $val );
    	
    	$replace = array( "/<script/i" => "&#60;script",
    					  "/\|/" => "&#124;",
    					  "/\n/" => "<br>", 
    					  "/\\\$/" => "&#036;",
    					  "/\r/" => "", 
    					  "/\\\/" => "&#092;" );
    					  
    	$val = preg_replace( array_keys( $replace ), $replace,  $val );

		if ( get_magic_quotes_gpc() )
    	{
    		$val = stripslashes( $val );
    	}

    	return $val;
    }
}

class Bee_Input_Exception extends Bee_Exception
{}
