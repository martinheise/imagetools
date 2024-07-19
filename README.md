# Image tools

This module contains logic to calculate multiple scaled image sizes from one source image and configuration options. It is meant to create appropriate `srcset`and `sizes` attributes for responsive images that best fit the given image and information about the HTML/CSS context.

It only contains business logic to define the expected sizes. The actual handling of the image data, like resizing, caching of the result etc. is not handled by the module, but done by the calling code, the interface `ImageData` serves as main connection point. 

## Installation

## Usage overview

- Implement interface `Mhe\Imagetools\Data\ImageData` as a wrapper for your images and processing methods
- create a `Mhe\Imagetools\Data\RenderConfig` object holding options and context information like the specific image layout context
    ``` 
    $config = new RenderConfig("max-width: 1000px) 100vw, 1000px", 5, 20000, 2);
    ```
- create a new `Mhe\Imagetools\ImageResizer` and let it process the source image with given configuration:
    ```
    $resizer = new ImageResizer();
    $result = $resizer->getVariants($srcimg, $config);
    ```
- The result is an array of images you will usually use to output a `srcset` attribute

For a small demonstration of the very basic usage see `mhe/imagetools-cli`.

A more advanced usage is `mhe/silverstripe-responsiveimages`, a module for CMS Silverstripe.


