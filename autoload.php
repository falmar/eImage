<?php

/**
 * Project: eImage
 * Date: 12/21/15
 * Time: 10:43 PM
 *
 * @link      https://github.com/falmar/eImage
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

/**
 * Class eImageAutoload
 *
 * @author David Lavieri (falmar) <daviddlavier@gmail.com>
 */
class eImageAutoload
{
    private static $Prefix = 'eImage';

    static public function load($Class)
    {
        if (strpos($Class, self::$Prefix) === 0) {
            $DS   = DIRECTORY_SEPARATOR;
            $File = __DIR__ . $DS . str_replace('\\', $DS, $Class) . '.php';
            if (file_exists($File)) {
                require_once($File);
            }
        } else {
            return;
        }
    }
}

spl_autoload_register('eImageAutoload::load');