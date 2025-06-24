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
 * This class represents a chunk of Vite's `manifest.json` file, which contains
 * records of all published files, their dependencies, and other metadata.
 *
 * @see https://github.com/vitejs/vite/blob/e7adcf0878bd7f3c0b7bb5c9a1d7e6f0d55d9650/packages/vite/src/node/plugins/manifest.ts#L18-L28
 */
class Chunk
{
    /**
     * Path to the source file, relative to Vite's `root`.
     *
     * @readonly
     * @var string|null
     */
    public $src;

    /**
     * Logical chunk name, as defined by Rollup.
     *
     * Only defined for chunks that are entry points.
     *
     * Vite's `build.rollupOptions.input` setting affects this value - you
     * can define a custom chunk name for each entry point by using an
     * object instead of an array.
     *
     * @link https://rollupjs.org/configuration-options/#input
     *
     * @readonly
     * @var string|null
     */
    public $name;

    /**
     * Indicates whether this chunk is an entry point.
     *
     * @readonly
     * @var bool
     */
    public $isEntry;

    /**
     * Indicates whether this chunk is a dynamic entry point.
     *
     * @readonly
     * @var bool
     */
    public $isDynamicEntry;

    /**
     * Path to the published file, relative to Vite's `build.outDir`.
     *
     * @readonly
     * @var string
     */
    public $file;

    /**
     * Paths to published CSS files imported by this chunk,
     * relative to Vite's `build.outDir`.
     *
     * @readonly
     * @var string[]
     */
    public $css;

    /**
     * Paths to published assets imported by this chunk,
     * relative to Vite's `build.outDir`.
     *
     * @readonly
     * @var string[]
     */
    public $assets;

    /**
     * List of chunk names of other chunks (statically) imported by this chunk.
     *
     * @readonly
     * @var string[]
     */
    public $imports;

    /**
     * Links of chunk names of other chunks (dynamically) imported by this chunk.
     *
     * @readonly
     * @var string[]
     */
    public $dynamicImports;

    /**
     * @param string|null $src
     * @param string|null $name
     * @param bool $isEntry
     * @param bool $isDynamicEntry
     * @param string $file
     * @param string[] $css
     * @param string[] $assets
     * @param string[] $imports
     * @param string[] $dynamicImports
     */
    public function __construct($src, $name, $isEntry, $isDynamicEntry, $file, array $css, array $assets, array $imports, array $dynamicImports)
    {
        $this->src = $src;
        $this->name = $name;
        $this->isEntry = $isEntry;
        $this->isDynamicEntry = $isDynamicEntry;
        $this->file = $file;
        $this->css = $css;
        $this->assets = $assets;
        $this->imports = $imports;
        $this->dynamicImports = $dynamicImports;
    }

    /**
     * @param array $chunk
     * @return self
     */
    public static function create($chunk)
    {
        return new self(
            isset($chunk['src']) ? $chunk['src'] : null,
            isset($chunk['name']) ? $chunk['name'] : null,
            isset($chunk['isEntry']) ? $chunk['isEntry'] : false,
            isset($chunk['isDynamicEntry']) ? $chunk['isDynamicEntry'] : false,
            $chunk['file'],
            isset($chunk['css']) ? $chunk['css'] : [],
            isset($chunk['assets']) ? $chunk['assets'] : [],
            isset($chunk['imports']) ? $chunk['imports'] : [],
            isset($chunk['dynamicImports']) ? $chunk['dynamicImports'] : []
        );
    }
}
