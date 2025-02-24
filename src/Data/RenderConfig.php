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
    protected int $highres;
    protected array $rendersizes;

    /**
     * @param $sizes string image size definition, possibly containing media queries, as given in img sizes attribute, e.g. "(max-width: 1000px) 100vw, 1000px"
     * @param $maxsteps int maximum number of image variants to create
     * @param $sizediff int desired filesize difference (in bytes) between variants – will not be considered exactly but is a rough goal
     * @param $highres int levels for pixel density (1, 2 or 3)
     * @param $rendersizes int[] optionally set fixed sizes (in px) to generate – the other params for auto calculation will be ignored
     */
    public function __construct (string $sizes, int $maxsteps = 10, int $sizediff = 50000, int $highres = 1, array $rendersizes = [])
    {
        $this->sizes = $sizes;
        $this->maxsteps = $maxsteps;
        $this->sizediff = $sizediff;
        $this->highres = $highres;
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
        $this->highres = max(min($this->highres, 3), 1);
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

    public function getHighres(): int
    {
        return $this->highres;
    }

    /**
     * @param int $highres
     */
    public function setHighres(int $highres): void
    {
        $this->highres = $highres;
        $this->validateValues();
    }

    /**
     * @deprecated use getHighres()
     */
    public function getRetinalevel(): int
    {
        return $this->getHighres();
    }

    /**
     * @deprecated use setHighres()
     */
    public function setRetinalevel(int $retinalevel): void
    {
        $this->setHighres($retinalevel);
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
