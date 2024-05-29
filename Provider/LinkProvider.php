<?php

namespace Ok99\PrivateZoneCore\MediaBundle\Provider;

use Ok99\PrivateZoneCore\MediaBundle\Entity\Media;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\CoreBundle\Model\Metadata;
use Sonata\CoreBundle\Validator\ErrorElement;
use Sonata\MediaBundle\Model\MediaInterface;
use Sonata\MediaBundle\Provider\BaseProvider;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Validator\Constraints\NotBlank;

class LinkProvider extends BaseProvider
{
    /**
     * {@inheritdoc}
     */
    public function getProviderMetadata()
    {
        return new Metadata($this->getName(), $this->getName().'.description', false, 'SonataMediaBundle', array('class' => 'fa fa-external-link'));
    }

    protected function doTransform(MediaInterface $media)
    {
        if (!$media->getProviderReference()) {
            $media->setProviderReference(sha1($media->getLink().uniqid().rand(11111, 99999)));
        }

        $media->setProviderStatus(MediaInterface::STATUS_OK);
    }

    public function getReferenceFile(MediaInterface $media)
    {
        return null;
    }

    /**
     * @param Media $media
     */
    public function getReferenceImage(MediaInterface $media)
    {
        return $media->getLink();
    }

    /**
     * @param Media $media
     */
    public function prePersist(MediaInterface $media)
    {
        parent::prePersist($media);

        $media->setProviderReference(sha1($media->getLink().uniqid().rand(11111, 99999)));
        $media->setProviderStatus(MediaInterface::STATUS_OK);
    }

    public function postPersist(MediaInterface $media)
    {
    }

    public function postUpdate(MediaInterface $media)
    {
    }

    public function buildEditForm(FormMapper $formMapper)
    {
        $formMapper->add('name', null, [
            'constraints' => array(
                new NotBlank(),
            ),
        ]);
        $formMapper->add('link', null, [
            'constraints' => [
                new NotBlank(),
            ],
            'required' => true,
        ]);
    }

    public function buildCreateForm(FormMapper $formMapper)
    {
        $formMapper->add('name', null, [
            'constraints' => array(
                new NotBlank(),
            ),
        ]);
        $formMapper->add('link', null, [
            'constraints' => [
                new NotBlank(),
            ],
            'required' => true,
        ]);
    }

    public function getHelperProperties(MediaInterface $media, $format, $options = array())
    {
        return array_merge([
            'title' => $media->getName(),
        ], $options);
    }

    /**
     * @param Media $media
     */
    public function generatePublicUrl(MediaInterface $media, $format)
    {
        return $media->getLink();
    }

    /**
     * @param Media $media
     */
    public function generatePrivateUrl(MediaInterface $media, $format)
    {
        return $media->getLink();
    }

    public function getDownloadResponse(MediaInterface $media, $format, $mode, array $headers = array())
    {
        throw new \RuntimeException('Invalid mode provided');
    }

    public function buildMediaType(FormBuilder $formBuilder)
    {
        $formMapper->add('link');
    }

    public function updateMetadata(MediaInterface $media, $force = true)
    {
    }
}