<?php

namespace Mhe\Imagetools\Data;

/**
 * This interface serves as a connection for the ImageResizer to the actual image handling done by any calling application.
 *
 * The implementing class can be anything as needed by the application, either referencing an actual image file
 * or possibly even a virtual asset.
 * It just needs to provide information about the main image properties like dimensions and filesize, as well as a method
 * to resize the source to a new ImageData object.
 */
interface ImageData
{
    /**
     * get the image width in px
     * @return int
     */
    public function getWidth(): int;

    /**
     * get the file size in byte
     * @return int
     */
    public function getFilesize(): int;

    /**
     * get the public path to the image
     * (for default functionality this is optional, can also return empty values if not appropriate)
     */
    public function getPublicPath(): string;

    /**
     * Resize the Image
     * @param $width
     * @return ImageData object representing the resized result
     */
    public function resize($width): ImageData;
}
