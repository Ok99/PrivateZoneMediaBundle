<?php

namespace Ok99\PrivateZoneCore\MediaBundle\Admin;

use Sonata\MediaBundle\Provider\Pool;
use Ok99\PrivateZoneCore\AdminBundle\Admin\Admin as BaseAdmin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Admin\AdminInterface;

class GalleryHasMediaAdmin extends BaseAdmin
{
    protected $pool;
    protected $translationDomain = 'Ok99PrivateZoneMediaBundle';

    public function __construct($code, $class, $baseControllerName, Pool $pool)
    {
        parent::__construct($code, $class, $baseControllerName);

        $this->pool = $pool;
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $context = $this->getRequest()->get('context');

        if (!$context) {
            $context = $this->pool->getDefaultContext();
        }

        $formats = array();
        foreach ((array) $this->pool->getFormatNamesByContext($context) as $name => $options) {
            $formats[$name] = $name;
        }

        $contexts = array();
        foreach ((array) $this->pool->getContexts() as $contextItem => $format) {
            $contexts[$contextItem] = $contextItem;
        }

        $formMapper
            ->with($this->trans('form_post_has_media.group_main_label'))
            ->add('media', 'ok99_privatezone_type_image', array(), array(
                'placeholder' => 'No image selected',
                'link_parameters' => array('context' => $context),
            ))
            ->add('position', 'hidden')
            ->end();
        ;
    }
}
