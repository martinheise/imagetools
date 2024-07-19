<?php

namespace Mhe\Imagetools\Tests\Calculations;

use Mhe\Imagetools\Data\RenderConfig;
use Mhe\Imagetools\ImageResizer;
use Mhe\Imagetools\Tests\Data\DummyImage;
use PHPUnit\Framework\TestCase;

class ImageResizerTest extends TestCase
{
    protected $resizer;

    protected $images = [];


    protected function setUp(): void
    {
        parent::setUp();
        $this->resizer = new ImageResizer(320, 2400, 16);
        $this->images['200'] = new DummyImage(200, 5000);
        $this->images['4000'] = new DummyImage(4000, 320000);
    }

    protected function assertResultWidths($expect, $variants)
    {
        $widths = array_map(fn($img): int => $img->getWidth(), $variants);
        $this->assertEquals($expect, $widths);
    }

    public function testSimple()
    {
        $config = new RenderConfig("100vw", 5, 500000, 1, []);
        $variants = $this->resizer->getVariants($this->images['200'], $config);
        $this->assertResultWidths([200], $variants); // kept original size
        $variants = $this->resizer->getVariants($this->images['4000'], $config);
        $this->assertResultWidths([2400], $variants); // max viewport
    }

    public function testSimpleRetina()
    {
        $config = new RenderConfig("100vw", 5, 500000, 2, []);
        $variants = $this->resizer->getVariants($this->images['200'], $config);
        $this->assertResultWidths([400, 200], $variants); // kept original size
        $variants = $this->resizer->getVariants($this->images['4000'], $config);
        $this->assertResultWidths([4800, 2400], $variants); // max viewport
    }

    public function testRemParam()
    {
        $config = new RenderConfig("20rem", 1);

        $this->resizer->setRemSize(20);
        $variants = $this->resizer->getVariants($this->images['4000'], $config);
        $this->assertResultWidths([400], $variants);

        $this->resizer->setRemSize(10);
        $variants = $this->resizer->getVariants($this->images['4000'], $config);
        $this->assertResultWidths([200], $variants);
    }

    public function testViewportParam()
    {
        $config = new RenderConfig("80vw", 1);

        $this->resizer->setMaxViewportWidth(1600);
        $variants = $this->resizer->getVariants($this->images['4000'], $config);
        $this->assertResultWidths([1280], $variants); // maxvw * 0,8

        $this->resizer->setMaxViewportWidth(3000);
        $variants = $this->resizer->getVariants($this->images['4000'], $config);
        $this->assertResultWidths([2400], $variants); // maxvw * 0,8
    }
}
