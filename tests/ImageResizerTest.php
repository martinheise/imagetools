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
        // test small image
        $this->images['200'] = new DummyImage(200, 2000);
        // test big image: 2x max viewport for easier calculation of desired values
        $this->images['4800'] = new DummyImage(4800, 1024000);
    }

    /*
     * helper method to assert result widths of generated image variants
     */
    protected function assertResultWidths($expect, $variants)
    {
        $widths = array_map(fn($img): int => $img->getWidth(), $variants);
        $this->assertEquals($expect, $widths);
    }

    public function testSimple()
    {
        $config = new RenderConfig("100vw", 5, 1000000, 1, []);
        $variants = $this->resizer->getVariants($this->images['200'], $config);
        $this->assertResultWidths([200], $variants); // kept original size
        $variants = $this->resizer->getVariants($this->images['4800'], $config);
        $this->assertResultWidths([2400], $variants); // max viewport
    }

    public function testSimpleHighres()
    {
        $config = new RenderConfig("100vw", 5, 1000000, 2, []);
        $variants = $this->resizer->getVariants($this->images['200'], $config);
        $this->assertResultWidths([200, 400], $variants); // kept original size
        $variants = $this->resizer->getVariants($this->images['4800'], $config);
        $this->assertResultWidths([2400, 4800], $variants); // max viewport
    }

    public function testRemValue()
    {
        $config = new RenderConfig("20rem", 1);

        $this->resizer->setRemSize(20);
        $variants = $this->resizer->getVariants($this->images['4800'], $config);
        $this->assertResultWidths([400], $variants);

        $this->resizer->setRemSize(10);
        $variants = $this->resizer->getVariants($this->images['4800'], $config);
        $this->assertResultWidths([200], $variants);
    }

    public function testViewportValue()
    {
        $config = new RenderConfig("80vw", 1);

        $this->resizer->setMaxViewportWidth(1600);
        $variants = $this->resizer->getVariants($this->images['4800'], $config);
        $this->assertResultWidths([1280], $variants); // maxvw * 0,8

        $this->resizer->setMaxViewportWidth(3000);
        $variants = $this->resizer->getVariants($this->images['4800'], $config);
        $this->assertResultWidths([2400], $variants); // maxvw * 0,8
    }

    public function testSizeDiff()
    {
        $config = new RenderConfig("100vw", 10, 64000, 1, []);
        $variants = $this->resizer->getVariants($this->images['200'], $config);
        $this->assertResultWidths([200], $variants); // kept original size
        // first step should be: 2400px -> filesize: 256000 => 4 steps
        $variants = $this->resizer->getVariants($this->images['4800'], $config);
        $this->assertEquals(4, count($variants));
        $this->assertResultWidths([2400, 1939, 1449, 900], $variants); // max viewport
    }

    public function testSizeDiffHighres()
    {
        $config = new RenderConfig("100vw", 10, 64000, 2, []);
        $variants = $this->resizer->getVariants($this->images['200'], $config);
        $this->assertResultWidths([200, 400], $variants); // kept original size
        $variants = $this->resizer->getVariants($this->images['4800'], $config);
        $this->assertEquals(10, count($variants));
        // first step should be: 2400px -> filesize: 256000 => 4 steps
        // first step for 2x should be: 4800px -> filesize: 1024000 => 10 (max)steps, limited by 1x level
        $this->assertResultWidths([2400, 1939, 1449, 900, 4800, 4437, 4067, 3688, 3299, 2897], $variants); // max viewport
    }

    public function testMaxSteps()
    {
        $config = new RenderConfig("100vw", 4, 500, 1, []);
        $variants = $this->resizer->getVariants($this->images['200'], $config);
        $this->assertResultWidths([200], $variants); // kept original size
        $variants = $this->resizer->getVariants($this->images['4800'], $config);
        $this->assertEquals(4, count($variants));
        $this->assertResultWidths([2400, 1939, 1449, 900], $variants); // max viewport
    }

    public function testMaxStepsHighres()
    {
        $config = new RenderConfig("100vw", 4, 1000, 2, []);
        $variants = $this->resizer->getVariants($this->images['200'], $config);
        $this->assertResultWidths([200, 400], $variants); // kept original size
        $variants = $this->resizer->getVariants($this->images['4800'], $config);
        $this->assertEquals(7, count($variants));
        // highres resolutions: 2x the regular ones, but stopping before reaching 1x width, so fewer values
        $this->assertResultWidths([2400, 1939, 1449, 900, 4800, 3878, 2897], $variants); // max viewport
    }

    public function testBreakpoints()
    {
        // calculated max width: 600px
        $config = new RenderConfig("(width <= 600px) 100vw, 520px", 4, 64000, 1, []);
        $variants = $this->resizer->getVariants($this->images['200'], $config);
        $this->assertResultWidths([200], $variants); // no upsacling
        $variants = $this->resizer->getVariants($this->images['4800'], $config);
        $this->assertResultWidths([600], $variants);
        // calculated max width: 720px
        $config = new RenderConfig("(max-width: 600px) 100vw, 720px", 4, 64000, 1, []);
        $variants = $this->resizer->getVariants($this->images['200'], $config);
        $this->assertResultWidths([200], $variants); // no upsacling
        $variants = $this->resizer->getVariants($this->images['4800'], $config);
        $this->assertResultWidths([720], $variants);
        // calculated max width: 2000px, and min width: 1200px
        $config = new RenderConfig("(max-width: 800px) 1200px, 2000px", 4, 500, 1, []);
        $variants = $this->resizer->getVariants($this->images['4800'], $config);
        $this->assertResultWidths([2000, 1616, 1207], $variants); // max viewport
    }

    public function testInvalidConfig()
    {
        // calculate based on max/min viewport for invalid input
        $config = new RenderConfig("(width <= 600px) 100vw 520px", 4, 64000, 1, []);
        $variants = $this->resizer->getVariants($this->images['4800'], $config);
        $this->assertResultWidths([2400, 1939, 1449, 900], $variants);
    }
}
