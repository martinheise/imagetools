# Image tools

This module contains logic to calculate multiple scaled image sizes from one source image and configuration options. It is meant to create appropriate `srcset`and `sizes` attributes for responsive images that best fit the given image and information about the HTML/CSS context.

It only contains business logic to define the expected sizes. The actual handling of the image data, like resizing, caching of the result etc. is not handled by the module, but done by the calling code, the interface `ImageData` serves as main connection point.

## Background

“Humans shouldn’t be doing this” – some inspiring thoughts on deciding which image resolutions to use in responsive output can be found in this article by Jason Grigsby: [Image Breakpoints](https://cloudfour.com/thinks/responsive-images-101-part-9-image-breakpoints/), especially [Setting image breakpoints based on a performance budget](https://cloudfour.com/thinks/responsive-images-101-part-9-image-breakpoints/#setting-image-breakpoints-based-on-a-performance-budget)

## Installation

Install with composer:

    composer require mhe/imagetools ^1.0

## Usage overview

- Implement interface `Mhe\Imagetools\Data\ImageData` as a wrapper for your images and processing methods
- create a `Mhe\Imagetools\Data\RenderConfig` object holding options and context information about the specific image layout context:
    ```
    $config = new RenderConfig("(max-width: 1000px) 100vw, 1000px");
    ```
- create a new `Mhe\Imagetools\ImageResizer` and let it process the source image with given configuration:
    ```
    $resizer = new ImageResizer();
    $result = $resizer->getVariants($srcimg, $config);
    ```
- The result is an array of images you can use to output a `srcset` attribute

For a small demonstration of the very basic usage see [mhe/imagetools-cli](https://github.com/martinheise/imagetools-cli).

A more advanced usage is [mhe/silverstripe-responsiveimages](https://github.com/martinheise/silverstripe-responsiveimages), a module for CMS Silverstripe.

## Configuration and options

### ImageResizer options

When creating a new `ImageResizer` you can provide these parameters to tweak the general behaviour:

- `min_viewport_width`: minimum viewport width to consider  (default: 320)
- `max_viewport_width`: maximum viewport width to consider, e.g. for fullwidth images (default: 2400)
- `rem_size`: used to translate values in rem unit to px (default: 16)

### RenderConfig options

A `RenderConfig` contains several options used for the calculations for a specific image – usually they are the same for multiple images used in the same layout context and/or CSS class, so you would have one configuration for Hero images, one for slider images, one for teaser images etc.

#### sizes

The main parameter of `RenderConfig`. It matches the `sizes` attribute of a desired `ìmg` element, telling the ImageResizer in which actual layout sizes (widths) the image will output on different screensizes.

This information can be deducted from the page layout usually defined in CSS, depending on the complexity of your layout it can get a bit more complicated to count in all conditions, often you just have to add up a couple of container margins etc. In doubt: if the information don’t exactly match the image sizes it probably doesn’t effect the result very much – a rough approximation is better than no information.

*Some examples:*

Full width image with margins of 16px on each side:

```calc(100vw - 32px)```

Image has full width with margins in mobile view, displayed in a 2-column grid on desktop:

```(max-width: 720px) calc(100vw - 32px), calc(50vw - 40px)```

Image has full width with margins in mobile view, displayed in a 2-column grid on desktop, and is limited to a fixed width on large screens:

```(max-width: 720px) calc(100vw - 32px), (max-width: 1680px) calc(50vw - 40px), 800px```

#### sizediff

This is the desired filesize difference in bytes between two different renditions. It will not be reached exactly, but rather a rough target.

Lower values mean more generated files, better matching the particular user conditions, but of course more load on generating images.

#### maxsteps

Set a limit on the number of renditions generated, has precedence of `sizediff`. With low `sizediff` values and large images this will assure that you don’t end up with vast amount of images generated.

#### retinalevel

Set to either `2` or `3`adds up extra levels for high resolution screens. If e.g. the maximum calculated image width is 1200px, also renditions for 2400px are created.

#### rendersizes

Request specific image widths to generate for specific purposes. If given the other parameters are ignored.

Can be helpful in specific cases – if you _only_ use this kind of configuration you probably don’t need this module at all ...

