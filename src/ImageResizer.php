<?php

namespace Mhe\Imagetools;

use Mhe\Imagetools\Calculations\CssCalculator;
use Mhe\Imagetools\Data\RenderConfig;
use Mhe\Imagetools\Data\ImageData;

/**
 * Helper class to generate multiple resized images from one source according to given configurations
 *
 * The main work is done by calling {@see ImageResizer::getVariants()} with a source image and configuration
 */
class ImageResizer
{
    /**
     * @var CssCalculator instance for CSS calculations
     */
    protected $cssCalculator;

    /**
     * Get a new ImageResizer with options
     * @param $min_viewport_width int Minimum viewport width to consider for calculations
     * @param $max_viewport_width int Maximum viewport width to consider for calculations
     * @param $rem_size int Default font size to use for calculations of values given in rem units
     */
    public function __construct($min_viewport_width = 320, $max_viewport_width = 2400, $rem_size = 16)
    {
        $this->cssCalculator = new CssCalculator($min_viewport_width, $max_viewport_width, $rem_size);
    }

    /**
     * @return int|mixed
     */
    public function getMinViewportWidth()
    {
        return $this->cssCalculator->getMinViewportWidth();
    }

    /**
     * @param int|mixed $min_viewport_width
     */
    public function setMinViewportWidth($min_viewport_width): void
    {
        $this->cssCalculator->setMinViewportWidth($min_viewport_width);
    }

    /**
     * @return int|mixed
     */
    public function getMaxViewportWidth()
    {
        return $this->cssCalculator->getMaxViewportWidth();
    }

    /**
     * @param int|mixed $max_viewport_width
     */
    public function setMaxViewportWidth($max_viewport_width): void
    {
        $this->cssCalculator->setMaxViewportWidth($max_viewport_width);
    }

    /**
     * @return int|mixed
     */
    public function getRemSize()
    {
        return $this->cssCalculator->getRemSize();
    }

    /**
     * @param int|mixed $rem_size
     */
    public function setRemSize($rem_size): void
    {
        $this->cssCalculator->setRemSize($rem_size);
    }

    /**
     * Generates multiple resized image varirant for the given source and configuration
     * @param ImageData $srcimage
     * @param RenderConfig $config
     * @return ImageData[]
     */
    public function getVariants(ImageData $srcimage, RenderConfig $config): array
    {
        // ToDo: support configured image ratio ?
        $ratio = null;

        $sizevalues = $this->cssCalculator->calculateBreakpointValues($config->getSizes());

        $minwidth = min(array_values($sizevalues));
        $maxwidth = max(array_values($sizevalues));

        // prevent upscaling
        // ToDo: check logic, especially for highres – highres will still be upscaled this way!
        $maxwidth = min($maxwidth, $srcimage->getWidth());
        if ($maxwidth < $minwidth) {
            $minwidth = $maxwidth;
        }

        if ($maxwidth < 1) {
            return [$srcimage];
        }

        $variants = [];
        if (count($config->getRendersizes()) == 0) {
            for ($r = $config->getHighres(); $r > 1; $r--) {
                $variants = array_merge($variants, $this->processVariants($srcimage, $maxwidth * $r, $maxwidth * ($r - 1) * 1.2, $ratio, $config->getSizediff(), $config->getMaxsteps()));
            }
        }
        $variants = array_merge($variants, $this->processVariants($srcimage, $maxwidth, $minwidth, $ratio, $config->getSizediff(), $config->getMaxsteps(), $config->getRendersizes()));

        return $variants;
    }

    /**
     * internal generation of multiple resized image variants
     * @param ImageData $srcimage
     * @return ImageData[]
     */
    protected function processVariants(ImageData $srcimage, $maxwidth, $minwidth, $ratio, $sizediff, $maxsteps, $fixedsizes = [])
    {
        $images = [];
        // Todo: handle $ratio (crop source image) – Or keep in modules?
        // ToDo: implement $USERWIDTH param? – Or keep in modules generating config?

        $procWidths = [];

        // first get all desired size values
        if ($fixedsizes && count($fixedsizes) > 0) {
            $procWidths = $fixedsizes;
        } else {
            // processing maximum required width
            $images[] = $srcimage->resize($maxwidth);

            // rough estimate of file size difference per step
            $steps = floor($images[0]->getFilesize() / $sizediff);
            if ($maxsteps > 0 && $steps > ($maxsteps - 1)) {
                $steps = $maxsteps;
            }
            for ($step = $steps - 1; $step > 0; $step--) {
                // The filesize grows roughly with the square of width, but less due to compression
                // this formula works quite well to distribute result file sizes evenly
                $factor = 0.5 * sqrt($step / $steps) + 0.5 * ($step / $steps);
                $currwidth = round(($maxwidth) * $factor);
                if ($currwidth < $minwidth) {
                    break;
                }
                $procWidths[] = $currwidth;
            }
        }

        // calculate images
        foreach ($procWidths as $width) {
            $images[] = $srcimage->resize($width);
        }

        return $images;
    }
}
