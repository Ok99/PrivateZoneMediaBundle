<?php

namespace Ok99\PrivateZoneCore\MediaBundle\Controller;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sonata\MediaBundle\Controller\MediaController as Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class MediaController extends Controller
{
	/**
	 * @Route("/cms/media/show/{id}/{format}", name="ok99_privatezone_media_show")
	 *
	 * @throws NotFoundHttpException
	 *
	 * @param string $id
	 * @param string $format
	 *
	 * @return Response
	 */
	public function showAction($id, $format = 'reference')
	{
		$media = $this->getMedia($id);

		if (!$media) {
			throw new NotFoundHttpException(sprintf('unable to find the media with the id : %s', $id));
		}

		$provider = $this->get($media->getProviderName());

		if ($format == 'reference') {
			$file = $provider->getReferenceFile($media);
		} else {
			$file = $provider->getFilesystem()->get($provider->generatePrivateUrl($media, $format));
		}

		$filePath = sprintf('%s/%s',
			$provider->getFilesystem()->getAdapter()->getDirectory(),
			$file->getKey()
		);

		if (!$file || !file_exists($filePath)) {
			throw new NotFoundHttpException(sprintf('file not exists : %s', $file->getKey()));
		}

		$response = new StreamedResponse(function() use ($file) {
			echo $file->getContent();
		}, Response::HTTP_OK, array(
			'Content-Type'          => $media->getContentType(),
			'Content-Disposition'   => sprintf('inline; filename="%s"', $media->getMetadataValue('filename')),

		));

		$response->setPrivate();
		$response->headers->addCacheControlDirective('max-age', 0);
		$response->headers->addCacheControlDirective('no-cache', true);
		$response->headers->addCacheControlDirective('no-store', true);
		$response->headers->addCacheControlDirective('must-revalidate', true);

		return $response;
	}
}