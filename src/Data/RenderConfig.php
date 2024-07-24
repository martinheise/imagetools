<?php

namespace Mhe\Imagetools\Data;

/**
 * Configuration parameters for one {@see ImageResizer::getVariants()} processing action
 */
class RenderConfig
{
    protected string $sizes;
    protected int $maxsteps;
    protected int $sizediff;
    protected int $retinalevel;
    protected array $rendersizes;

    /**
     * @param $sizes string image size definition, possibly containing media queries, as given in img sizes attribute, e.g. "(max-width: 1000px) 100vw, 1000px"
     * @param $maxsteps int maximum number of image variants to create
     * @param $sizediff int desired filesize difference (in bytes) between variants – will not be considered exactly but is a rough goal
     * @param $retinalevel int number of levels for retina resolutions (1, 2, 3)
     * @param $rendersizes int[] optionally set fixed sizes (in px) to generate – the other params for auto calculation will be ignored
     */
    public function __construct(string $sizes, int $maxsteps = 10, int $sizediff = 50000, int $retinalevel = 1, array $rendersizes = [])
    {
        $this->sizes = $sizes;
        $this->maxsteps = $maxsteps;
        $this->sizediff = $sizediff;
        $this->retinalevel = $retinalevel;
        $this->rendersizes = $rendersizes;
        $this->validateValues();
    }

    /**
     * limit parameters to reasonable values
     * @return void
     */
    private function validateValues(): void
    {
        $this->maxsteps = max(min($this->maxsteps, 10), 0);
        if ($this->maxsteps > 0) {
            $this->sizediff = max($this->sizediff, 5000);
        } else {
            $this->sizediff = max($this->sizediff, 20000);
        }
        $this->retinalevel = max(min($this->retinalevel, 3), 1);
        foreach ($this->rendersizes as $size) {
            if (!is_numeric($size)) {
                $this->rendersizes = [];
                break;
            }
        }
        rsort($this->rendersizes);
    }

    /**
     * @return string
     */
    public function getSizes(): string
    {
        return $this->sizes;
    }

    /**
     * @param string $sizes
     */
    public function setSizes(string $sizes): void
    {
        $this->sizes = $sizes;
        $this->validateValues();
    }

    /**
     * @return string
     * @deprecated Use getSizes() instead
     */
    public function getSizesstring(): string
    {
        return $this->sizes;
    }

    /**
     * @param string $sizesstring
     * @deprecated Use getSizes() instead
     */
    public function setSizesstring(string $sizesstring): void
    {
        $this->sizes = $sizesstring;
        $this->validateValues();
    }

    /**
     * @return int
     */
    public function getMaxsteps(): int
    {
        return $this->maxsteps;
    }

    /**
     * @param int $maxsteps
     */
    public function setMaxsteps(int $maxsteps): void
    {
        $this->maxsteps = $maxsteps;
        $this->validateValues();
    }

    /**
     * @return int
     */
    public function getSizediff(): int
    {
        return $this->sizediff;
    }

    /**
     * @param int $sizediff
     */
    public function setSizediff(int $sizediff): void
    {
        $this->sizediff = $sizediff;
        $this->validateValues();
    }

    /**
     * @return int
     */
    public function getRetinalevel(): int
    {
        return $this->retinalevel;
    }

    /**
     * @param int $retinalevel
     */
    public function setRetinalevel(int $retinalevel): void
    {
        $this->retinalevel = $retinalevel;
        $this->validateValues();
    }

    /**
     * @return array
     */
    public function getRendersizes(): array
    {
        return $this->rendersizes;
    }

    /**
     * @param array $rendersizes
     */
    public function setRendersizes(array $rendersizes): void
    {
        $this->rendersizes = $rendersizes;
        $this->validateValues();
    }
}
