<?php

namespace Ok99\PrivateZoneCore\MediaBundle\Provider;

class DownloadProvider extends \Sonata\MediaBundle\Provider\FileProvider
{

	/**
     * {@inheritdoc}
     */
    public function buildEditForm(\Sonata\AdminBundle\Form\FormMapper $formMapper)
    {
        $formMapper->add('name');
        $formMapper->add('enabled', null, array('required' => false));
        $formMapper->add('authorName');
        $formMapper->add('cdnIsFlushable');
        $formMapper->add('description');
        $formMapper->add('lang');
        $formMapper->add('copyright');
        $formMapper->add('binaryContent', 'file', array('required' => false));
    }
}