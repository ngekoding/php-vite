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

use RuntimeException;

/**
 * This class represents Vite's manifest of published files.
 */
class Manifest
{
    /**
     * @var array<string,Chunk> map where chunk names => `Chunk` instances.
     */
    private $chunks;

    /**
     * @var array<string,array{type:string,as:string}> map where file extensions => preloaded MIME and content types.
     */
    private $preloadTypes = [];

    /**
     * Indicates whether the application is running in development mode.
     *
     * In production mode, the `manifest.json` file will be read to generate
     * preload links for all dependencies, and CSS and JS tags for all entries.
     *
     * In development mode, Vite will dynamically inject CSS and JS tags.
     *
     * @var bool
     */
    private $dev;

    /**
     * Absolute path to the `manifest.json` file.
     *
     * This is only used and required in production mode.
     *
     * @var string
     */
    private $manifestPath;

    /**
     * Public base path from which Vite's published assets are served.
     *
     * For example `/dist/` if your assets are served from `http://example.com/dist/`.
     *
     * Should match the `base` option in your Vite configuration, but could also point
     * to a CDN or other asset server, if you are serving assets from a different domain.
     *
     * @var string
     */
    private $basePath;

    /**
     * @param bool $dev
     * @param string $manifestPath
     * @param string $basePath
     */
    public function __construct($dev, $manifestPath, $basePath)
    {
        $this->dev = $dev;
        $this->manifestPath = $manifestPath;
        $this->basePath = $basePath;

        if ($this->dev) {
            // In development mode, we don't need the `manifest.json` file:

            $this->chunks = [];
        } else {
            // In production, read Vite's `manifest.json` file:

            if (!is_readable($this->manifestPath)) {
                throw new RuntimeException(
                    file_exists($this->manifestPath)
                    ? "Manifest file is not readable: {$this->manifestPath}"
                    : "Manifest file not found: {$this->manifestPath}"
                );
            }

            $this->chunks = array_map(
                function (array $chunk) {
                    return Chunk::create($chunk);
                },
                json_decode(file_get_contents($this->manifestPath), true)
            );
        }
    }

    /**
     * Register a MIME type for preloading assets with a specific file extension.
     *
     * @param string $ext the file extension (without the leading dot)
     * @param string $mimeType the MIME type to preload
     * @param string $preloadAs the `as` attribute value (content type) for the preload tag
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/rel/preload#what_types_of_content_can_be_preloaded
     *
     * @return void
     */
    public function preload($ext, $mimeType, $preloadAs)
    {
        $this->preloadTypes[$ext] = ['type' => $mimeType, 'as' => $preloadAs];
    }

    /**
     * Register MIME types for preloading all common web image formats.
     *
     * @return void
     */
    public function preloadImages()
    {
        $this->preloadTypes = array_merge(
            $this->preloadTypes,
            [
                'apng' => ['type' => 'image/apng', 'as' => 'image'],
                'avif' => ['type' => 'image/avif', 'as' => 'image'],
                'bmp' => ['type' => 'image/bmp', 'as' => 'image'],
                'cur' => ['type' => 'image/x-icon', 'as' => 'image'],
                'gif' => ['type' => 'image/gif', 'as' => 'image'],
                'ico' => ['type' => 'image/x-icon', 'as' => 'image'],
                'jpeg' => ['type' => 'image/jpeg', 'as' => 'image'],
                'jpg' => ['type' => 'image/jpeg', 'as' => 'image'],
                'png' => ['type' => 'image/png', 'as' => 'image'],
                'svg' => ['type' => 'image/svg+xml', 'as' => 'image'],
                'tif' => ['type' => 'image/tiff', 'as' => 'image'],
                'tiff' => ['type' => 'image/tiff', 'as' => 'image'],
                'webp' => ['type' => 'image/webp', 'as' => 'image']
            ]
        );
    }

    /**
     * Register MIME types for preloading common web font formats.
     *
     * @return void
     */
    public function preloadFonts()
    {
        $this->preloadTypes = array_merge(
            $this->preloadTypes,
            [
                'ttf' => ['type' => 'font/ttf', 'as' => 'font'],
                'otf' => ['type' => 'font/otf', 'as' => 'font'],
                'woff' => ['type' => 'font/woff', 'as' => 'font'],
                'woff2' => ['type' => 'font/woff2', 'as' => 'font']
            ]
        );
    }

    /**
     * Create preload, CSS and JS tags for the specified entry point script(s).
     *
     * Entry points are defined in Vite's `build.rollupOptions` using RollUp's `input` setting.
     *
     * The expected typical usage in an HTML template is as follows:
     *
     * ```html
     * <!DOCTYPE html>
     * <html>
     *   <head>
     *     <title>My App</title>
     *     <?= $tags->preload ?>
     *     <?= $tags->css ?>
     *   </head>
     *   <body>
     *     <h1>My App</h1>
     *     <?= $tags->js ?>
     *   </body>
     * </html>
     * ```
     *
     * @link https://vitejs.dev/config/build-options#build-rollupoptions
     * @link https://rollupjs.org/configuration-options/#input
     *
     * @param string ...$entries
     * @return Tags
     */
    public function createTags(...$entries)
    {
        if ($this->dev) {
            // In development mode, Vite will dynamically inject CSS and JS tags:

            $js = ["<script type=\"module\" src=\"{$this->basePath}@vite/client\"></script>"];

            foreach ($entries as $entry) {
                $js[] = "<script type=\"module\" src=\"{$this->basePath}{$entry}\"></script>";
            }

            return new Tags('', '', implode("\n", $js));
        } else {
            // In production mode, we generate CSS/JS and preload tags for all entries and their dependencies:

            $chunks = $this->findImportedChunks($entries);

            return new Tags(
                $this->createPreloadTags($chunks),
                $this->createStyleTags($chunks),
                $this->createScriptTags($chunks)
            );
        }
    }

    /**
     * Get the URL for an asset published by Vite.
     *
     * You can use this method to get the URL for an asset, for example, if you need
     * to create custom preload tags with media queries, or if you need to load an
     * asset dynamically, based on user interaction, and so on.
     *
     * @param string $entry
     * @return string
     */
    public function getURL($entry)
    {
        if ($this->dev) {
            return $this->basePath . $entry;
        }

        $chunk = isset($this->chunks[$entry]) ? $this->chunks[$entry] : null;

        if ($chunk === null) {
            throw new RuntimeException("Entry not found in manifest: {$entry}");
        }

        return $this->basePath . $chunk->file;
    }

    /**
     * @param Chunk[] $chunks
     * @return string
     */
    private function createPreloadTags(array $chunks)
    {
        $tags = [];

        foreach ($chunks as $chunk) {
            // Preload module:

            if (substr_compare($chunk->file, '.js', -strlen('.js')) === 0) {
                $tags[] = "<link rel=\"modulepreload\" href=\"{$this->basePath}{$chunk->file}\" />";
            }

            // Preload assets:

            foreach ($chunk->assets as $asset) {
                $type = substr($asset, strrpos($asset, '.') + 1);

                if (isset($this->preloadTypes[$type])) {
                    $preload = $this->preloadTypes[$type];
                    $type = $preload['type'];
                    $as = $preload['as'];

                    $tags[] = "<link rel=\"preload\" as=\"{$as}\" type=\"{$type}\" href=\"{$this->basePath}{$asset}\" />";
                }
            }
        }

        return implode("\n", $tags);
    }

    /**
     * @param Chunk[] $chunks
     * @return string
     */
    private function createStyleTags(array $chunks)
    {
        $tags = [];

        foreach ($chunks as $chunk) {
            foreach ($chunk->css as $css) {
                $tags[] = "<link rel=\"stylesheet\" href=\"{$this->basePath}{$css}\" />";
            }
        }

        return implode("\n", $tags);
    }

    /**
     * @param Chunk[] $chunks
     * @return string
     */
    private function createScriptTags(array $chunks)
    {
        $tags = [];

        foreach ($chunks as $chunk) {
            if ($chunk->isEntry) {
                $tags[] = "<script type=\"module\" src=\"{$this->basePath}{$chunk->file}\"></script>";
            }
        }

        return implode("\n", $tags);
    }

    /**
     * @return array
     */
    private function findImportedChunks(array $entries)
    {
        $chunks = [];

        foreach ($entries as $entry) {
            $chunk = isset($this->chunks[$entry]) ? $this->chunks[$entry] : null;

            if ($chunk === null) {
                throw new RuntimeException("Entry not found in manifest: {$entry}");
            }

            if (!$chunk->isEntry) {
                throw new RuntimeException("Chunk is not an entry point: {$entry}");
            }

            $chunks[$entry] = $chunk;

            // Recursively find all statically imported chunks:

            $imports = $chunk->imports;

            while ($imports) {
                $import = array_shift($imports);

                if (!isset($chunks[$import])) {
                    $chunks[$import] = $this->chunks[$import];

                    $imports = array_merge($imports, $chunks[$import]->imports);
                }
            }
        }

        return $chunks;
    }
}
