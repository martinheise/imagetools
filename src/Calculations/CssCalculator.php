<?php

namespace Mhe\Imagetools\Calculations;

/**
 * Helper class to parse specific CSS information and perform calculations based on it
 */
class CssCalculator
{
    protected $min_viewport_width;
    protected $max_viewport_width;
    protected $rem_size;

    /**
     * @var MathParser
     */
    protected $mathparser;

    /**
     * Create a new CssCalculator with given base properties
     * @param $min_viewport_width int Minimum viewport width to consider for calculations
     * @param $max_viewport_width int Maximum viewport width to consider for calculations
     * @param $rem_size int Default font size to use for calculations of values given in rem units
     */
    public function __construct($min_viewport_width = 320, $max_viewport_width = 2400, $rem_size = 16)
    {
        $this->min_viewport_width = $min_viewport_width;
        $this->max_viewport_width = $max_viewport_width;
        $this->rem_size = $rem_size;
        $this->mathparser = new MathParser();
    }

    /**
     * @return int
     */
    public function getMinViewportWidth()
    {
        return $this->min_viewport_width;
    }

    /**
     * @param int $min_viewport_width
     */
    public function setMinViewportWidth($min_viewport_width): void
    {
        $this->min_viewport_width = $min_viewport_width;
    }

    /**
     * @return int
     */
    public function getMaxViewportWidth()
    {
        return $this->max_viewport_width;
    }

    /**
     * @param int $max_viewport_width
     */
    public function setMaxViewportWidth($max_viewport_width): void
    {
        $this->max_viewport_width = $max_viewport_width;
    }

    /**
     * @return int
     */
    public function getRemSize()
    {
        return $this->rem_size;
    }

    /**
     * @param int $rem_size
     */
    public function setRemSize($rem_size): void
    {
        $this->rem_size = $rem_size;
    }

    /**
     * calculates given image sizes for different width breakpoints, according media-query
     * returns entries for each breakpoint (below and above point) and the minimum and maximum viewport widths
     * @internal may change in the future, public only for testing
     *
     * @param $string string, e.g. "(width > 1600px) 800px, (width > 800px) 40vw, 80vw"
     * @return array associative array in the form [ viewportwidth => imagewidth ... ]
     */
    public function calculateBreakpointValues($string)
    {
        $sizetokens = preg_split('/,\s*/', $string);

        $minbp = $this->min_viewport_width;
        $maxbp = $this->max_viewport_width;

        $ranges = [];

        foreach ($sizetokens as $token) {
            $range = $this->calculateBreakpointRange($token, $minbp, $maxbp);
            if (is_array($range) and count($range) > 1) {
                $bps = array_keys($range);
                // limit breakpoints for next step
                if ($bps[0] > $minbp) {
                    $maxbp = $bps[0] - 1;
                } elseif ($bps[1] < $maxbp) {
                    $minbp = $bps[1] + 1;
                }
                if (!isset($ranges[$bps[0]])) {
                    $ranges[$bps[0]] = $range[$bps[0]];
                }
                if (!isset($ranges[$bps[1]])) {
                    $ranges[$bps[1]] = $range[$bps[1]];
                }
            }
        }
        if (count($ranges) == 0) {
            $ranges[$minbp] = $minbp;
            $ranges[$maxbp] = $maxbp;
        }
        return $ranges;
    }

    /**
     * calculates given image size for min and max values of one breakpoint range
     * @internal may change in the future, public only for testing
     *
     * @param string $string e.g. "(width > 1600px) 800px" or "100vw"
     * @param int $minbp lower limit for calculation range
     * @param null $minbp upper limit for calculation range
     * @return array
     */
    public function calculateBreakpointRange($string, $minbp = null, $maxbp = null)
    {
        $min = $minbp ?: $this->min_viewport_width;
        $max = $maxbp ?: $this->max_viewport_width;

        $range = [];

        if (preg_match('/^\s*\((.*)\)\s+(.*)/', $string, $matches)) {
            // with query
            $query = $this->calculateCssExpression($matches[1]);

            if (preg_match('/(max-width:|width\s*<=)\s*(\d+)/', $query, $cond)) {
                $max = min($max, (int)$cond[2]);
            }
            if (preg_match('/(width\s*<?)\s*(\d+)/', $query, $cond)) {
                $max = min($max, (int)$cond[2] - 1);
            }
            if (preg_match('/(min-width:|width\s*>=)\s*(\d+)/', $query, $cond)) {
                $min = max($min, (int)$cond[2]);
            }
            if (preg_match('/(width\s*>?)\s*(\d+)/', $query, $cond)) {
                $min = max($min, (int)$cond[2] + 1);
            }

            if (!empty($val = $this->calculateCssExpressionValue($matches[2], $min))) {
                $range[$min] = round($val);
            }
            if (!empty($val = $this->calculateCssExpressionValue($matches[2], $max))) {
                $range[$max] = round($val);
            }
        } else {
            // without query, using full given range
            if (!empty($val = $this->calculateCssExpressionValue($string, $min))) {
                $range[$min] = round($val);
            }
            if (!empty($val = $this->calculateCssExpressionValue($string, $max))) {
                $range[$max] = round($val);
            }
        }
        return $range;
    }

    /**
     * Perform calculations on a CSS expression
     * convert unit values to px-based numbers and evaluate calc() expressions
     *
     * @param string $expression CSS expression
     * @param int $basevw base viewport width
     * @param int $basevh base viewport height
     * @return string result with replacement of valid values/calculations
     */
    public function calculateCssExpression($expression, $basevw = null, $basevh = null)
    {
        $basevw = $basevw ?: $this->max_viewport_width;
        $basevh = $basevh ?: round($this->max_viewport_width * 9 / 16);

        // convert unit expression to px numbers
        // not supported yet: percentage (would need ancestor context) – and other units
        $step1 = preg_replace_callback('/([\d.]+)(rem|em|px|vh|vw)/', function ($matches) use ($basevw, $basevh) {
            return $this->replaceUnitValue($matches, $basevw, $basevh);
        }, $expression);

        $step2 = preg_replace_callback('/calc\((.*?)\)/', function ($matches) {
            return $this->replaceCalc($matches);
        }, $step1);
        return $step2;
    }

    /**
     * calculate CSS expression to numeric (px-based) value
     *
     * @param string $expression CSS expression
     * @param int $basevw base viewport width
     * @param int $basevh base viewport height
     * @return float|null value in px, or null if the expression could not be reduced to a simple numerical value
     */
    public function calculateCssExpressionValue($expression, $basevw = null, $basevh = null)
    {
        $value = $this->calculateCssExpression($expression, $basevw, $basevh);
        return is_numeric($value) ? floatval($value) : null;
    }

    /**
     * Replace values including units with number value based on px, for use in `preg_replace_callback`
     *
     * @param string $matches submatches [expression, value, unit]
     * @param int $basevw base viewport width
     * @param int $basevh base viewport height
     * @return string
     */
    protected function replaceUnitValue($matches, $basevw, $basevh)
    {
        if ($matches[2] == 'px') {
            return $matches[1];
        }
        $num = (double) $matches[1];
        $converted = $num;
        switch ($matches[2]) {
            case "rem":
            case "em":
                $converted = $num * $this->rem_size;
                break;
            case "vw":
                $converted = $num * $basevw / 100;
                break;
            case "vh":
                $converted = $num * $basevh / 100;
                break;
            default:
                return $matches[0];
        }
        return strval($converted);
    }

    /**
     * replace CSS calc() expression with calculated numerical value, for use in `preg_replace_callback`
     * single values need to be converted to numbers already
     *
     * @param string $matches submatches [expression, calc-content]
     * @return float|int|mixed
     */
    protected function replaceCalc($matches)
    {
        $value = $this->mathparser->calculate($matches[1]);
        return $value ?: $matches[1];
    }
}
