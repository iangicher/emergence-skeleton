<?php

/**
 * Adds spaces (or the given character(s)) between every character of a string
 * <pre>
 *  * value : the string to process
 *  * space_char : the character(s) to insert between each character
 * </pre>
 * This software is provided 'as-is', without any express or implied warranty.
 * In no event will the authors be held liable for any damages arising from the use of this software.
 *
 * @author     Jordi Boggiano <j.boggiano@seld.be>
 * @copyright  Copyright (c) 2008, Jordi Boggiano
 * @license    http://dwoo.org/LICENSE   Modified BSD License
 * @link       http://dwoo.org/
 * @version    1.0.0
 * @date       2008-10-23
 * @package    Dwoo
 */
function Dwoo_Plugin_spacify_compile(Dwoo_Compiler $compiler, $value, $space_char=' ')
{
    return 'implode('.$space_char.', str_split('.$value.', 1))';
}
