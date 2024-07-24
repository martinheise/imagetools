<?php

namespace Mhe\Imagetools\Tests\Calculations;

use Mhe\Imagetools\Calculations\CssCalculator;
use PHPUnit\Framework\TestCase;

class CssCalculatorTest extends TestCase
{
    public function testCalculateCssExpression()
    {
        $calculator = new CssCalculator(120, 2400, 16);
        $this->assertEquals("50", $calculator->calculateCssExpression('50px', 1000));
        $this->assertEquals("48", $calculator->calculateCssExpression('calc(50px-2px)', 500));
        $this->assertEquals("(width > 80) 338", $calculator->calculateCssExpression('(width > 5rem) calc(80vw - 30px - 2rem)', 500));
    }

    public function testCalculateCssExpressionValue()
    {
        $calculator = new CssCalculator(120, 2400, 16);
        // valid number values:
        $this->assertEquals(50, $calculator->calculateCssExpressionValue('50px', 1000));
        $this->assertEquals(48, $calculator->calculateCssExpressionValue('calc(50px-2px)', 500));
        $this->assertEquals(338, $calculator->calculateCssExpressionValue('calc(80vw - 30px - 2rem)', 500));
        $this->assertEquals(266.6663, $calculator->calculateCssExpressionValue('calc(33.3333vw - 66.6667px)', 1000));
        $this->assertEquals(738, $calculator->calculateCssExpressionValue('calc(80vw - 30px - 2rem)', 1000));
        // invalid/unsupported values:
        $this->assertNull($calculator->calculateCssExpressionValue('50%'));
        $this->assertNull($calculator->calculateCssExpressionValue('(width > 5rem) calc(80vw - 30px)'));
    }

    public function testCalculateBreakpointValues()
    {
        $calculator = new CssCalculator(120, 2400);
        // ascending sizes
        $this->assertEquals(
            [
                120 => 96,
                800 => 640,
                801 => 320,
                1600 => 640,
                1601 => 800,
                2400 => 800
            ],
            $calculator->calculateBreakpointValues('(width <= 800px) 80vw, (width <= 1600px) 40vw, 800px')
        );
        // descending sizes
        $this->assertEquals(
            [
                2400 => 800,
                1601 => 800,
                1600 => 640,
                801 => 320,
                800 => 640,
                120 => 96,
            ],
            $calculator->calculateBreakpointValues('(width > 1600px) 800px, (width > 800px) 40vw, 80vw')
        );
        // descending sizes with calc
        $this->assertEquals(
            [
                2400 => 810,
                1617 => 810,
                1616 => 636,
                801 => 310,
                800 => 660,
                120 => 116,
            ],
            $calculator->calculateBreakpointValues('(width > calc(1600px + 1rem)) calc(50rem + 10px), (width > 800px) calc(40vw - 10px), calc(80vw + 20px)')
        );
        // descending sizes with periods
        $this->assertEquals(
            [
                2400 => 493,
                1680 => 493,
                1679 => 493,
                1020 => 273,
                1019 => 410,
                760 => 280,
                759 => 727,
                120 => 88
            ],
            $calculator->calculateBreakpointValues('(min-width: 1680px) 493px, (min-width: 1020px) calc(33.33vw - 66.67px), (min-width: 760px) calc(50vw - 100px), calc(100vw - 32px)')
        );
        // fallback to min/max viewport for invalid input
        $this->assertEquals(
            [
                2400 => 2400,
                120 => 120
            ],
            $calculator->calculateBreakpointValues('(min-width: 1680px) 493px 322px')
        );
    }

    public function testCalculateBreakpointRange()
    {
        $calculator = new CssCalculator(120, 2400);
        // width <= query, vw value
        $this->assertEquals(
            [
                120 => 96,
                800 => 640
            ],
            $calculator->calculateBreakpointRange('(width <= 800px) 80vw')
        );
        // width < query, vw value
        $this->assertEquals(
            [
                120 => 96,
                799 => 639
            ],
            $calculator->calculateBreakpointRange('(width < 800px) 80vw')
        );
        // min-width and max-width query, vw value
        $this->assertEquals(
            [
                400 => 360,
                500 => 450
            ],
            $calculator->calculateBreakpointRange('(min-width: 400px and max-width: 500px) 90vw')
        );
        // max-width query, vw value
        $this->assertEquals(
            [
                120 => 108,
                800 => 720
            ],
            $calculator->calculateBreakpointRange('(max-width: 800px) 90vw')
        );
        // max-width query, px value
        $this->assertEquals(
            [
                120 => 666,
                800 => 666
            ],
            $calculator->calculateBreakpointRange('(max-width: 800px) 666px')
        );
        // min-width query with calc value
        $this->assertEquals(
            [
                800 => 656,
                2400 => 1936
            ],
            $calculator->calculateBreakpointRange('(min-width: 800px) calc(80vw + 1rem)')
        );
        // one px value
        $this->assertEquals(
            [
                120 => 130,
                2400 => 130
            ],
            $calculator->calculateBreakpointRange('130px')
        );
        // one calc value
        $this->assertEquals(
            [
                120 => 112,
                2400 => 1936
            ],
            $calculator->calculateBreakpointRange('calc(80vw + 1rem)')
        );
        // unsupported query
        $this->assertEquals(
            [
                120 => 112,
                2400 => 1936
            ],
            $calculator->calculateBreakpointRange('(prefers-reduced-motion: reduce) calc(80vw + 1rem)')
        );
        // invalid value
        $this->assertEquals(
            [],
            $calculator->calculateBreakpointRange('(min-width: 800px) 80px + 20px')
        );
        // invalid string
        $this->assertEquals(
            [],
            $calculator->calculateBreakpointRange('what is this')
        );
    }
}
