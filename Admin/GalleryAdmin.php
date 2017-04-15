<?php

namespace Ok99\PrivateZoneCore\MediaBundle\Admin;

use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\MediaBundle\Provider\Pool;
use Ok99\PrivateZoneCore\AdminBundle\Admin\Admin as BaseAdmin;
use Ok99\PrivateZoneCore\PageBundle\Entity\SitePool;

class GalleryAdmin extends BaseAdmin
{
    protected $pool;
    protected $sitePool;
    protected $translationDomain = 'Ok99PrivateZoneMediaBundle';

    /**
     * @param string                            $code
     * @param string                            $class
     * @param string                            $baseControllerName
     * @param \Sonata\MediaBundle\Provider\Pool $pool
     */
    public function __construct($code, $class, $baseControllerName, Pool $pool, SitePool $sitePool)
    {
        parent::__construct($code, $class, $baseControllerName);

        $this->pool = $pool;

        $this->sitePool = $sitePool;
    }

    protected function configureFormFields(FormMapper $formMapper)
    {

        $formMapper
            ->with('General')
            ->add('enabled', null, array('required' => false))
            ->add('translations', 'ok99_privatezone_translations', array(
                'translation_domain' => $this->translationDomain,
                'label' => false,
                'fields' => array(
                    'name' => array(),
                    'description' => array(
                        'field_type' => 'ckeditor',
                        'config_name' => 'news'
                    ),
                ),
                'exclude_fields' => array('slug')
            ))
            ->end()
            ->with('Gallery')
            ->add('galleryHasMedias', 'ok99_privatezone_type_media_collection', array(
                'label' => 'Images',
                'required' => false,
                'context' => 'gallery',
                'media_type' => 'image',
            ), array(
                'sortable' => 'position'
            ))
            ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name')
            ->add('enabled', 'boolean', array('editable' => true))
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('site', null, array(
                'show_filter' => false,
            ))
            ->add('name')
            ->add('enabled')
        ;
    }

    public function prePersist($gallery)
    {
        foreach ($gallery->getGalleryHasMedias() as $media) {
            $media->setGallery($gallery);
        }

        $gallery->setSite($this->sitePool->getCurrentSite($this->getRequest()));
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate($gallery)
    {
        $this->prePersist($gallery);
    }
}