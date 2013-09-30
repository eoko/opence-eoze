<?php
/**
 * @author Éric Ortéga <eric@planysphere.fr>
 * @since 23/02/11 01:13
 */

namespace eoko\util\image;

/**
 * This class manage a collection of images and their corresponding cached
 * thumbnails of various sizes. This manager must be given two working directory.
 * In the image directory, all the original images are stored, while generated
 * caches are kept in the cache directory.
 * 
 * @internal Classes implementing this interface must allow that the cache can
 * be randomly cleared at any time, without that disrupting them from working 
 * as expected.
 */
interface ThumbnailledImagesManager {

	/**
	 * Creates a new ThumbnailledImagesManager.
	 * 
	 * @param string $imageRootPath absolute path of the directory 
	 * containing the images
	 * 
	 * @param string $cacheRootPath absolute path of the directory this
	 * ThumbnailledImagesManager must use as its cache. The ThumbnailledImagesManager expects to
	 * be the only one that write under this directory.
	 * 
	 * @throws \IllegalStateException if $imageRootPath or $cacheRootPath
	 * cannot be read
	 */
	function __construct($imageRootPath, $cacheRootPath);

	/**
	 * Adds the given image to the image path managed by the ThumbnailledImagesManager.
	 * If a $width and $height are specified, then the image is immediately
	 * resized before being copied to the image path. If only $width or $height
	 * is specified (and the other is NULL), then the image is resized, keeping
	 * the same aspect ratio as the original before being copied to the image
	 * path.
	 * 
	 * @param string $absolutePath
	 * @param string $relativeDestPath destination path, relative to the image
	 * directory root
	 * @param int $width
	 * @param int $height
	 * @throws ImageNotFoundException if no image can be found at 
	 * $absoluteSourcePath
	 * @throws InvalidImageException if a file exists at $absoluteSourcePath 
	 * but is not an image or cannot be read as such
	 * @throws \IllegalStateException if a writting error occurs
	 */
	function addImage($absoluteSourcePath, $relativeDestPath, $width = null, $height = null);

	/**
	 * Deletes the image identified by $path, and all the cached image it has
	 * generated.
	 * @param $path path to the image, relative to the images directory
	 * @throws \IllegalStateException if the image cannot be found, or if a
	 * delete error occurs
	 */
	function deleteImage($path);

	/**
	 * Get the absolute path for an image with the desired $width and $height.
	 * 
	 * If $width and $height are both NULL, then the original image (that is,
	 * the one in the images directory) is always returned.
	 * 
	 * If only one of $width or $height is specified (that is, the other is
	 * NULL), then a path to an image with the same aspect ratio as the original
	 * is returned.
	 * 
	 * @param string $path relative path of the image, in the image directory
	 * @param int $width desired width of the image
	 * @param int $height desired height of the image
	 * @param boolean $isInCache this variable is set to TRUE if the returned
	 * path points to an image in the cache directory, else it is set to FALSE
	 * @return string the absolute path to the requested image
	 * @throws ImageNotFoundException
	 * @throws InvalidImageException
	 */
	function getImagePath($path, $width = null, $height = null, &$isInCache = null);
}

class ImageException extends \SystemException {

}

class ImageNotFoundException extends ImageException {

}

class InvalidImageException extends ImageException {

}
