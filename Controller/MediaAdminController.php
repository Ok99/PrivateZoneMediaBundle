<?php

namespace Ok99\PrivateZoneCore\MediaBundle\Controller;

use Sonata\MediaBundle\Controller\MediaAdminController as Controller;
use Ok99\PrivateZoneCore\MediaBundle\Admin\MediaAdmin;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class MediaAdminController extends Controller
{
    /**
     * Returns the response object associated with the browser action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws AccessDeniedException
     */
    public function browserAction(Request $request)
    {
        if (false === $this->admin->isGranted('LIST')) {
            throw new AccessDeniedException();
        }

        $linkTo = $request->query->get('linkTo', 'page');

        // params for both templates
        $tplName = null;
        $tplParams = array(
            'action' => 'browser',
            'base_template' => 'Ok99PrivateZoneMediaBundle::layout.html.twig',
            'linkTo' => $linkTo,
        );

        // page link
        if ($linkTo == 'page') {
            $pool = $this->get('ok99.privatezone.site.pool');
            $currentSite = $pool->getCurrentSite($request);
            $pageList =  $this->loadPageList($currentSite, $request->getLocale());

            // set template values
            $tplName = 'Ok99PrivateZoneMediaBundle:MediaAdmin:pages.html.twig';
            $tplParams['pages'] = $pageList;
            $tplParams['currentSite'] = $currentSite;
            $tplParams['sites'] = $pool->getSites();
        }

        // media file link
        else {
            $categoryManager = $this->container->get('sonata.classification.manager.category');

            $currentContext = $this->admin->getPersistentParameter('context');
            $currentCategory = $this->admin->getPersistentParameter('category');
            $rootCategory = $categoryManager->getRootCategory($currentContext);

            $contextInCategory = $categoryManager->findBy(array(
                'id'      => (int) $request->get('category'),
                'context' => $currentContext
            ));

            // set filters for datagrid
            $datagrid = $this->admin->getDatagrid();
            $datagrid->setValue('context', null, $currentContext);
            $datagrid->setValue('providerName', null, $this->admin->getPersistentParameter('provider'));

            // make sure, that category is selected and that it belongs to current context
            if (!$currentCategory || empty($contextInCategory)) {
                // if not, mark as selected category context root category
                $currentCategory = $rootCategory;
            }

            // set selected category
            $datagrid->setValue('category', null, $currentCategory);

            // transform context list to associative array
            $contextList = array();
            foreach ($this->admin->getContextList() as $context) {
                $contextList[$context->getId()] = $context->getName();
            }

            // Store formats
            $formats = array();
            foreach ($datagrid->getResults() as $media) {
                $formats[$media->getId()] = $this->get('sonata.media.pool')->getFormatNamesByContext($media->getContext());
            }

            $formView = $datagrid->getForm()->createView();

            // set template values
            $tplName = 'Ok99PrivateZoneMediaBundle:MediaAdmin:browser.html.twig';
            $tplParams = array_merge($tplParams, array(
                'form' => $formView,
                'datagrid' => $datagrid,
                'formats' => $formats,
                'contextList' => $contextList,
                'rootCategory' => $rootCategory,
                'currentCategory' => $categoryManager->find($currentCategory),
            ));
        }

        // render template
        return $this->render($tplName, $tplParams);
    }


    /**
     * {@inheritdoc}
     */
    public function listAction(Request $request = null)
    {
        if (false === $this->admin->isGranted('LIST')) {
            throw new AccessDeniedException();
        }

        if ($listMode = $request->get('_list_mode', 'mosaic')) {
            $this->admin->setListMode($listMode);
        }

        $sitesPool = $this->get('ok99.privatezone.site.pool');
        $sites = $sitesPool->getSites();
        $currentSite = $sitesPool->getCurrentSite($request, $sites);

        $datagrid = $this->admin->getDatagrid();

        $filters = $request->get('filter');

        // set the default context
        if (!$filters || !array_key_exists('context', $filters)) {
            $context = $this->admin->getPersistentParameter('context');
        } else {
            $context = $filters['context']['value'];
        }

        $datagrid->setValue('context', null, $context);

        // retrieve the main category for the tree view
        $category = $this->container->get('sonata.classification.manager.category')->getRootCategory($context);

        if (!$filters) {
            $datagrid->setValue('category', null, $category->getId());
        }

        if ($request->get('category')) {
            $contextInCategory = $this->container->get('sonata.classification.manager.category')->findBy(array(
                'id'      => (int) $request->get('category'),
                'context' => $context
            ));

            if (!empty($contextInCategory)) {
                $datagrid->setValue('category', null, $request->get('category'));
            } else {
                $datagrid->setValue('category', null, $category->getId());
            }
        }

        // filters by provider name, e.g. sonata.media.provider.file
        if ($request->get('provider')) {
            $datagrid->setValue('providerName', null, $request->get('provider'));
        }

        $formView = $datagrid->getForm()->createView();

        // set the theme for the current Admin Form
        $this->get('twig')->getExtension('form')->renderer->setTheme($formView, $this->admin->getFilterTheme());

        return $this->render($this->admin->getTemplate('list'), array(
            'action'        => 'list',
            'form'          => $formView,
            'datagrid'      => $datagrid,
            'root_category' => $category,
            'sites'         => $sites,
            'currentSite'   => $currentSite,
            'csrf_token'    => $this->getCsrfToken('sonata.batch'),
        ));
    }


    public function uploadAction(Request $request)
    {
        if (false === $this->admin->isGranted('CREATE')) {
            throw new AccessDeniedException();
        }
        $mediaManager = $this->get('sonata.media.manager.media');
        $provider = $request->get('provider');
        $file = $request->files->get('upload');
        if (!$request->isMethod('POST') || !$provider || null === $file) {
            throw $this->createNotFoundException();
        }
        $context = $request->get('context', $this->get('sonata.media.pool')->getDefaultContext());
        $media = $mediaManager->create();
        $media->setBinaryContent($file);
        $mediaManager->save($media, $context, $provider);
        $this->admin->createObjectSecurity($media);
        return $this->render('Ok99PrivateZoneMediaBundle:MediaAdmin:upload.html.twig', array(
            'action' => 'list',
            'object' => $media
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function createAction(Request $request = null)
    {
        /*
         * Remove provider from querystring when upload is submitted.
         * Provider existence causes list media from  provider only.
         */
        if ($request->getMethod() == Request::METHOD_POST && $request->query->get('provider')) {
            $request->query->remove('provider');
        }
        return parent::createAction($request);
    }

    /**
     * Loads lists of pages available that user can links to
     * @return array
     */
    protected function loadPageList($site, $locale) {
        $list = $this->get('doctrine')->getManager()->createQuery("
			SELECT
				p
			FROM
				Ok99PrivateZonePageBundle:Page p
				INNER JOIN p.translations t
			WHERE
				    t.enabled = :enabled
			    AND p.site = :site
				AND p.parent IS NULL
				AND t.locale = :locale
				AND p.routeName NOT LIKE '_page_internal_%'
				AND t.url NOT LIKE '%{%'
		  	ORDER BY
		  		p.position
		")
            ->setParameter('site', $site)
            ->setParameter('locale', $locale)
            ->setParameter('enabled', true)
            ->getResult()
        ;

        $pages = array();
        foreach ($list as $page) {
            $this->childWalker($page, $pages);
        }
        return $pages;
    }


    /**
     * Builds list page from tree
     * @param $page
     * @param $choices
     */
    private function childWalker($page, &$choices)
    {
        if (
            !$page->isInternal()
            && strpos($page->getUrl(), '{') === FALSE
        ) {
            $parent = $page->getParent();
            if ($parent && $parent->getParent()) {
                $page->setName($parent->getName() . '/' . $page->getName());
            }

            $choices[$page->getId()] = $page;

            foreach ($page->getChildren() as $child) {
                $this->childWalker($child, $choices);
            }
        }
    }
}
