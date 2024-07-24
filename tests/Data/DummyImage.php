<?php

namespace Mhe\Imagetools\Tests\Data;

use Mhe\Imagetools\Data\ImageData;

class DummyImage implements ImageData
{
    protected int $width;
    protected int $filesize;

    /**
     * @param int $width
     * @param int $filesize
     */
    public function __construct(int $width, int $filesize)
    {
        $this->width = $width;
        $this->filesize = $filesize;
    }

    /**
     * @inheritDoc
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @inheritDoc
     */
    public function getFilesize(): int
    {
        return $this->filesize;
    }

    /**
     * @inheritDoc
     */
    public function getPublicPath(): string
    {
        return "dummy_" . $this->getWidth() . "_" . $this->getFilesize() . ".jpg";
    }

    /**
     * @inheritDoc
     */
    public function resize($width): ImageData
    {
        $filesize = round($this->getFilesize() / ($this->width / $width) ** 2);
        return new DummyImage($width, $filesize);
    }
}
