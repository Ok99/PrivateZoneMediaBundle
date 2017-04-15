<?php

namespace Ok99\PrivateZoneCore\MediaBundle\Controller;

use Sonata\MediaBundle\Controller\GalleryAdminController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class GalleryAdminController extends Controller
{
    /**
     * List action.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws AccessDeniedException If access is not granted
     */
    public function listAction(Request $request = null)
    {
        if (false === $this->admin->isGranted('LIST')) {
            throw new AccessDeniedException();
        }

        $preResponse = $this->preList($request);
        if ($preResponse !== null) {
            return $preResponse;
        }

        if ($listMode = $request->get('_list_mode')) {
            $this->admin->setListMode($listMode);
        }

        $sitesPool = $this->get('ok99.privatezone.site.pool');
        $sites = $sitesPool->getSites();
        $currentSite = $sitesPool->getCurrentSite($request, $sites);

        $datagrid = $this->admin->getDatagrid();
        $datagrid->setValue('site', null, $currentSite->getId());
        $formView = $datagrid->getForm()->createView();

        // set the theme for the current Admin Form
        $this->get('twig')->getExtension('form')->renderer->setTheme($formView, $this->admin->getFilterTheme());

        return $this->render($this->admin->getTemplate('list'), array(
            'action'      => 'list',
            'form'        => $formView,
            'datagrid'    => $datagrid,
            'sites'       => $sites,
            'currentSite' => $currentSite,
            'csrf_token'  => $this->getCsrfToken('sonata.batch'),
        ), null, $request);
    }
}