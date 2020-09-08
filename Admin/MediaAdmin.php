<?php

namespace Ok99\PrivateZoneCore\MediaBundle\Admin;

use Sonata\AdminBundle\Admin\AdminInterface;
use Knp\Menu\ItemInterface as MenuItemInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\ClassificationBundle\Model\CategoryManagerInterface;
use Sonata\ClassificationBundle\Model\ContextManagerInterface;
use Sonata\MediaBundle\Form\DataTransformer\ProviderDataTransformer;
use Sonata\MediaBundle\Provider\Pool;
use Ok99\PrivateZoneCore\PageBundle\Entity\SitePool;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Symfony\Component\HttpFoundation\Request;

class MediaAdmin extends \Sonata\MediaBundle\Admin\ORM\MediaAdmin
{
    protected $datagridValues = array(
        '_page' => 1,
        '_sort_by' => 'name',
        '_sort_order' => 'asc'
    );

    protected $listModes = array(
//        'list' => array(
//            'class' => 'fa fa-list fa-fw',
//        ),
        'mosaic' => array(
            'class' => 'fa fa-th-large fa-fw',
        ),
//        'tree' => array(
//            'class' => 'fa fa-sitemap fa-fw',
//        ),
    );

    /**
     * @var ContextManagerInterface
     */
    protected $contextManager;

    /**
     * @var SitePool
     */
    protected $sitePool;

    private $currentContext = null;

    /**
     * {@inheritdoc}
     */
    public function __construct($code, $class, $baseControllerName, Pool $pool, CategoryManagerInterface $categoryManager, ContextManagerInterface $contextManager, SitePool $sitePool)
    {
        parent::__construct($code, $class, $baseControllerName, $pool, $categoryManager);

        $this->contextManager = $contextManager;
        $this->sitePool = $sitePool;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(\Sonata\AdminBundle\Route\RouteCollection $collection)
    {
        $collection->add('browser', 'browser');
        $collection->add('upload', 'upload');
        $collection->remove('history');
        $collection->remove('export');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureSideMenu(MenuItemInterface $menu, $action, AdminInterface $childAdmin = null)
    {
        if (!$childAdmin && !in_array($action, array('list'))) {
            return;
        }

        $current_context = $this->getPersistentParameter('context');

        $contexts = $this->getContextList();

        if (count($contexts) > 1) {
            foreach ($this->getContextList() as $context) {
                $this->currentContext = $context->getId();

                $child = $menu->addChild(
                    $this->trans($context->getName()),
                    array('uri' => $this->generateUrl('list', array('context' => $context->getId(), 'category' => null, 'hide_context' => null)))
                );

                if ($current_context === $context->getId()) {
                    $child->setCurrent(true);
                }
            }
        }
    }

    /**
     * Returns list of available contexts
     *
     * @return array
     */
    public function getContextList()
    {
        $criteria = array(
            'site' => $this->sitePool->getCurrentSite($this->getRequest())
        );

        return $this->contextManager->findBy($criteria, array('name' => 'asc'));
    }

    /**
     * {@inheritdoc}
     */
    public function getPersistentParameters()
    {
        $context = $this->currentContext;
        $this->currentContext = null;

        $parameters = parent::getPersistentParameters();
        if (!is_null($context)) {
            $parameters['context'] = $context;
        }

        if (!$this->hasRequest()) {
            return $parameters;
        }

        if (is_null($context)) {
            if ($filter = $this->getRequest()->get('filter') && isset($filter['context'])) {
                $context = $filter['context']['value'];
            } else {
                $context = $this->getRequest()->get('context', false);
                $available_contexts = array_map(function ($c) { return $c->getId(); }, $this->getContextList());
                if (!$context || !in_array($context, $available_contexts)) {
                    $context = $available_contexts[0];
                }
            }
        }

        $providers = $this->pool->getProvidersByContext($context);

        if ($this->getRequest()->getMethod() != Request::METHOD_GET) {
            $provider = $this->getRequest()->get('provider');
        }

        // if the context has only one provider, set it into the request
        // so the intermediate provider selection is skipped
        if (count($providers) == 1 && (!isset($provider) || !$provider)) {
            $provider = array_shift($providers)->getName();
            $this->getRequest()->query->set('provider', $provider);
        }

        $categoryId = $this->getRequest()->get('category');

        if (!$categoryId) {
            $categoryId = $this->categoryManager->getRootCategory($context)->getId();
        }

        return array_merge($parameters, array(
            'provider' => isset($provider) && $provider ? $provider : null,
            'context' => $context,
            'category' => $categoryId,
            'hide_context' => (bool)$this->getRequest()->get('hide_context')
        ));
    }

    /**
     * Set datagrid values used before datagrid build
     *
     * @param $values array
     * @return AdminInterface
     */
    public function setDatagridValues(array $values)
    {
        $this->datagridValues = array_merge($this->datagridValues, $values);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $media = $this->getSubject();

        if (!$media) {
            $media = $this->getNewInstance();
        }

        if (!$media || !$media->getProviderName()) {
            return;
        }

        $formMapper->add('providerName', 'hidden');
        $formMapper->add('enabled');

        $formMapper->getFormBuilder()->addModelTransformer(new ProviderDataTransformer($this->pool, $this->getClass()), true);

        $provider = $this->pool->getProvider($media->getProviderName());

        if ($media->getId()) {
            $provider->buildEditForm($formMapper);
        } else {
            $provider->buildCreateForm($formMapper);
        }

        $formMapper->add('category', 'sonata_type_model_list', array(), array(
            'link_parameters'  => array(
                'context'      => $media->getContext(),
                'hide_context' => true,
                'mode'         => 'tree',
            ),
            'admin_code' => 'sonata.classification.admin.category'
        ));

        if ($formMapper->has('name')) {
            $formMapper->get('name')->setRequired(true);
        }

        $formMapper->remove('description');
        $formMapper->remove('copyright');
        $formMapper->remove('cdnIsFlushable');
        $formMapper->remove('authorName');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name')
            ->add('description')
            ->add('enabled')
            ->add('size')
            ->add('createdAt')
        ;
    }

    /**
     * @param \Sonata\AdminBundle\Datagrid\DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $options = array(
            'choices' => array(),
        );

        foreach ($this->pool->getContexts() as $name => $context) {
            $options['choices'][$name] = $name;
        }

        $datagridMapper
            ->add('name', null, array('label' => 'list.label_name'))
            ->add('enabled', null, array('label' => 'list.label_enabled'))
            ->add('category', null, array(
                'show_filter' => false,
                'admin_code' => 'sonata.classification.admin.category'
            ))
            ->add('context', null, array(
                'show_filter' => $this->getPersistentParameter('hide_context') !== true,
            ), 'choice', $options)
        ;
    }

    public function showInAddBlock()
    {
        return true;
    }

    public function isAdmin($object = null)
    {
        return ($object ? $this->isGranted('ADMIN', $object) : $this->isGranted('ADMIN'))
            || $this->isGranted('ROLE_OK99_PRIVATEZONE_MEDIA_ADMIN_MEDIA_ADMIN')
            || $this->isGranted('ROLE_SUPER_ADMIN')
        ;
    }
}
