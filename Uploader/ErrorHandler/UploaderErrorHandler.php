<?php
namespace Ok99\PrivateZoneCore\MediaBundle\Uploader\ErrorHandler;

use Exception;
use Oneup\UploaderBundle\Uploader\ErrorHandler\ErrorHandlerInterface;
use Oneup\UploaderBundle\Uploader\Response\AbstractResponse;
use Symfony\Component\Translation\TranslatorInterface;


class UploaderErrorHandler implements ErrorHandlerInterface
{
    private $translator;

    public function __construct(TranslatorInterface $translator) {
        $this->translator = $translator;
    }

    public function addException(AbstractResponse $response, Exception $exception)
    {
        $message = $this->translator->trans($exception->getMessage(), array(), 'Ok99PrivateZoneMediaBundle');
        $response['error'] = $message;
    }
}