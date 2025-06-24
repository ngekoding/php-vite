<?php
/**
 * This file is part of a fork of the original project:
 * https://github.com/mindplay-dk/php-vite
 *
 * Original author: Rasmus Schultz
 * Original license: Mozilla Public License 2.0 (MPL-2.0)
 *
 * Modifications by Nur Muhammad
 * Date: 2025-06-24
 * Description:
 * - Adjusted syntax and features to support PHP 5.6 and above.
 * - Changed namespace to `ngekoding\*`.
 */

namespace Ngekoding\Vite;

/**
 * @see Manifest::createTags()
 */
class Tags
{
    /**
     * @readonly
     * @var string
     */
    public $preload = '';

    /**
     * @readonly
     * @var string
     */
    public $css = '';

    /**
     * @readonly
     * @var string
     */
    public $js = '';

    /**
     * @param string $preload
     * @param string $css
     * @param string $js
     */
    public function __construct($preload = '', $css = '', $js = '')
    {
        $this->preload = $preload;
        $this->css = $css;
        $this->js = $js;
    }
}
