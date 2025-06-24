<?php

namespace Ngekoding\Vite;

use PHPUnit\Framework\TestCase;
use Ngekoding\Vite\Manifest;

class ManifestTest extends TestCase
{
    protected $manifestPath;

    protected function setUp()
    {
        $this->manifestPath = __DIR__ . '/fixtures/manifest.json';
    }

    public function testDevModeTags()
    {
        $vite = new Manifest(true, $this->manifestPath, '/dist/');
        $tags = $vite->createTags("main.js");

        $this->assertSame("", $tags->preload);
        $this->assertSame("", $tags->css);
        $this->assertSame(implode("\n", [
            '<script type="module" src="/dist/@vite/client"></script>',
            '<script type="module" src="/dist/main.js"></script>',
        ]), $tags->js);
    }

    public function testProdModeTags()
    {
        $vite = new Manifest(false, $this->manifestPath, '/dist/');
        $vite->preloadImages();
        $tags = $vite->createTags("main.js");

        $this->assertEquals([
            '<link rel="modulepreload" href="/dist/assets/main.4889e940.js" />',
            '<link rel="preload" as="image" type="image/png" href="/dist/assets/asset.0ab0f9cd.png" />',
            '<link rel="modulepreload" href="/dist/assets/shared.83069a53.js" />',
        ], explode("\n", $tags->preload));

        $this->assertEquals([
            '<link rel="stylesheet" href="/dist/assets/main.b82dbe22.css" />',
            '<link rel="stylesheet" href="/dist/assets/shared.a834bfc3.css" />',
        ], explode("\n", $tags->css));

        $this->assertEquals([
            '<script type="module" src="/dist/assets/main.4889e940.js"></script>',
        ], explode("\n", $tags->js));
    }

    public function testMultipleEntryPoints()
    {
        $vite = new Manifest(false, $this->manifestPath, '/dist/');
        $vite->preloadImages();
        $tags = $vite->createTags("main.js", "consent-banner.js");

        $this->assertContains('<link rel="modulepreload" href="/dist/assets/consent-banner.0e3b3b7b.js" />', $tags->preload);
        $this->assertContains('<link rel="stylesheet" href="/dist/assets/consent-banner.8ba40300.css" />', $tags->css);
        $this->assertContains('<script type="module" src="/dist/assets/consent-banner.0e3b3b7b.js"></script>', $tags->js);
    }

    public function testMissingEntryThrows()
    {
        $this->expectException('RuntimeException');

        $vite = new Manifest(false, $this->manifestPath, '/dist/');
        $vite->createTags("does-not-exist.js");
    }

    public function testNonEntryThrows()
    {
        $this->expectException('RuntimeException');

        $vite = new Manifest(false, $this->manifestPath, '/dist/');
        $vite->createTags("views/foo.js");
    }

    public function testGetUrl()
    {
        $vite = new Manifest(false, $this->manifestPath, '/dist/');
        $this->assertEquals('/dist/assets/foo.869aea0d.js', $vite->getURL("views/foo.js"));

        $vite = new Manifest(true, $this->manifestPath, '/dist/');
        $this->assertEquals('/dist/views/foo.js', $vite->getURL("views/foo.js"));
    }
}
