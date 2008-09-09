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
 * @see ASO_Display
 */
require_once 'ASO/Display.php';

/** Smarty */
require_once 'Smarty/Smarty.class.php';

/**
 * Display backend for the Smarty templating engine
 *
 * @category   ASOworx
 * @package    ASO
 * @copyright  Copyright (c) A Small Orange Software (http://www.asmallorange.com)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 */
class ASO_Display_Smarty
{
    /**
     * Smarty instance
     * @var Smarty
     */
    private $smarty = null;

    /** 
     * Constructor
     *
     * Instantiates Smarty for later awesomeness
     */
    public function __construct()
    {    
        $this->smarty = new Smarty();
        
        $this->smarty->template_dir = './app/views/';
        $this->smarty->compile_dir = './var/cache/';
        $this->smarty->config_dir = './var/cache/';
        $this->smarty->cache_dir = './var/cache/';
    }

    /**
     * Display the data through Smarty
     *
     * @param array $view The template to use
     * @param array $data The data to be used in the template
     * @return void
     */
    public function runDisplay( $view, $data )
    {
        $this->smarty->assign( $data );
        $this->smarty->assign( 'input', ASO_Input::filterInput() );
        $this->smarty->assign( 'sess', ASO_Registry('sess') );
        
        // GZip compression
        //ob_start( 'ob_gzhandler' );
        
        if( file_exists( 'app/views/global.tpl' ) && $view != 'error' )
        {        
            $this->smarty->assign( 'templatefile', $view . '.tpl' );
            $this->smarty->display( 'global.tpl' );
        }
        else
        {
            $this->smarty->display( $view . '.tpl' );
        }
    }

}

class ASO_Display_Smarty_Exception extends ASO_Display_Exception
{}