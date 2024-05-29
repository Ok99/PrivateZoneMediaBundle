<?php

namespace Ok99\PrivateZoneCore\MediaBundle\Provider;

use Cocur\Slugify\Slugify;
use Gaufrette\Filesystem;
use Doctrine\ORM\EntityManager;
use Sonata\CoreBundle\Validator\ErrorElement;
use Sonata\MediaBundle\CDN\CDNInterface;
use Sonata\MediaBundle\Generator\GeneratorInterface;
use Sonata\MediaBundle\Thumbnail\ThumbnailInterface;
use Sonata\MediaBundle\Metadata\MetadataBuilderInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\MediaBundle\Provider\FileProvider as BaseFileProvider;
use Sonata\MediaBundle\Model\MediaInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class FileProvider extends BaseFileProvider
{
    protected $allowedExtensions;

    protected $allowedMimeTypes;

    protected $entityManager;

    public function __construct($name, Filesystem $filesystem, CDNInterface $cdn, GeneratorInterface $pathGenerator, ThumbnailInterface $thumbnail, array $allowedExtensions = array(), array $allowedMimeTypes = array(), MetadataBuilderInterface $metadata = null, EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct($name, $filesystem, $cdn, $pathGenerator, $thumbnail, $allowedExtensions, $allowedMimeTypes, $metadata);
    }

	/**
	 * {@inheritdoc}
	 */
	public function generatePublicUrl(MediaInterface $media, $format)
	{
		if ($format == 'reference') {
			$path = $this->getReferenceImage($media);
			return $this->getCdn()->getPath($path, $media->getCdnIsFlushable());
		} else {
			return sprintf('/bundles/sonatamedia/files/%s/file.png', $format != 'admin' ? $format : '256');
		}
	}

    /**
     * {@inheritdoc}
     */
    protected function generateReferenceName(MediaInterface $media)
    {
        $filename = uniqid().'_'.$this->generateReferenceSlug($media).'.';

        if ($media->getBinaryContent()) {
            $file = $media->getBinaryContent();

            if (is_a($file, UploadedFile::class)) {
                $extension = $file->getClientOriginalExtension();
            } elseif (is_a($file, File::class)) {
                $extension = $file->guessExtension();
            }

            if (!$extension) {
                $extension = $media->getBinaryContent()->guessExtension();
            }
        } else {
            $extension = pathinfo($media->getMetadataValue('filename'), PATHINFO_EXTENSION);
        }

        return $filename . $extension;
    }

    protected function generateReferenceSlug(MediaInterface $media)
    {
        $filename = $media->getMetadataValue('filename');
        $extension = substr($filename,strrpos($filename,'.')+1);
        return (new Slugify())->slugify(substr($filename,0,strlen($filename)-strlen($extension)));
    }

    /**
     * {@inheritdoc}
     */
    public function buildEditForm(FormMapper $formMapper)
    {
        $formMapper->add('name', null, [
            'constraints' => array(
                new NotBlank(),
            ),
        ]);
        $formMapper->add('binaryContent', 'file', array('required' => false));
    }

    /**
     * {@inheritdoc}
     */
    public function buildCreateForm(FormMapper $formMapper)
    {
        $formMapper->add('name', null, [
            'constraints' => array(
                new NotBlank(),
            ),
        ]);
        $formMapper->add('binaryContent', 'file', array(
            'constraints' => array(
                new NotBlank(),
            )
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function validate(ErrorElement $errorElement, MediaInterface $media)
    {
        if (!$media->getBinaryContent() instanceof \SplFileInfo) {
            return;
        }

        if ($media->getBinaryContent() instanceof UploadedFile) {
            $fileName = $media->getBinaryContent()->getClientOriginalName();
        } elseif ($media->getBinaryContent() instanceof File) {
            $fileName = $media->getBinaryContent()->getFilename();
        } else {
            throw new \RuntimeException(sprintf('Invalid binary content type: %s', get_class($media->getBinaryContent())));
        }

        if (
            !in_array(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)), $this->allowedExtensions) ||
            !in_array($media->getBinaryContent()->getMimeType(), $this->allowedMimeTypes)
        ) {
            $errorElement
                ->with('binaryContent')
                ->addViolation('Dokument s příponou .%extension% není možné nahrát.', ['%extension%' => $media->getBinaryContent()->getClientOriginalExtension()])
                ->end();
        }
    }
}
