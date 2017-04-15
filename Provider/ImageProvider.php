<?php

namespace Ok99\PrivateZoneCore\MediaBundle\Provider;

use Sonata\MediaBundle\Model\MediaInterface;
use Sonata\MediaBundle\Provider\ImageProvider as BaseImageProvider;

class ImageProvider extends BaseImageProvider
{
	/**
	 * Generate reference thumbnail beside common thumbnails
	 *
	 * {@inheritdoc}
	 */
	public function generateThumbnails(MediaInterface $media)
	{
		$this->generateReferenceThumbnail($media);
		$this->thumbnail->generate($this, $media);
	}

	/**
	 * Store reference thumbnail - filename e.g. thumb_007_reference.jpeg
	 *
	 * @param MediaInterface $media
	 */
	public function generateReferenceThumbnail(MediaInterface $media)
	{
		$in = $this->getReferenceFile($media);
		$out = $this->getFilesystem()->get($this->generatePrivateUrl($media, 'reference'), true);
		$oldUmask = umask(0002);
		$out->setContent($in->getContent());
		umask($oldUmask);
	}

	/**
	 * Return reference thumbnail path if exists, otherwise return common reference path
	 *
	 * {@inheritdoc}
	 */
	public function getReferenceImage(MediaInterface $media)
	{
		$thumbMediaPath = $this->thumbnail->generatePrivateUrl($this, $media, 'reference');
		if (file_exists($this->getFilesystem()->getAdapter()->getDirectory() . '/' . $thumbMediaPath)) {
			return $thumbMediaPath;
		} else {
			return sprintf('%s/%s',
				$this->generatePath($media),
				$media->getProviderReference()
			);
		}
	}

    public function getFormatName(MediaInterface $media, $format)
    {
        if ($format == 'admin') {
            return 'admin';
        }

        if ($format == 'reference') {
            return 'reference';
        }

        if ($format == 'cms') {
            return 'cms';
        }

        $baseName = $media->getContext().'_';
        if (substr($format, 0, strlen($baseName)) == $baseName) {
            return $format;
        }

        return $baseName.$format;
    }
}
