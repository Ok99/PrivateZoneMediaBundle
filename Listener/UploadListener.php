<?php

namespace Ok99\PrivateZoneCore\MediaBundle\Listener;

use Behat\Behat\Util\Transliterator;
use Ok99\PrivateZoneBundle\Entity\Attachment;
use Ok99\PrivateZoneBundle\Entity\EmailAttachment;
use Oneup\UploaderBundle\Event\PostPersistEvent;
use Sonata\MediaBundle\Model\MediaManagerInterface;
use Sonata\ClassificationBundle\Model\CategoryManagerInterface;
use Sonata\MediaBundle\Provider\Pool;
use Ok99\PrivateZoneCore\MediaBundle\Entity\Media;
use Ok99\PrivateZoneCore\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;

class UploadListener
{
    /** @var MediaManagerInterface */
    private $manager;

    /** @var CategoryManagerInterface */
    private $categoryManager;

    /** @var Pool */
    private $pool;

    /** @var ContainerInterface */
    private $container;

    /**
     * UploadListener constructor.
     * @param MediaManagerInterface $manager
     * @param Pool $pool
     * @param CategoryManagerInterface $categoryManager
     * @param ContainerInterface $container
     */
    public function __construct(MediaManagerInterface $manager, Pool $pool, CategoryManagerInterface $categoryManager, ContainerInterface $container)
    {
        $this->manager = $manager;
        $this->pool = $pool;
        $this->categoryManager = $categoryManager;
        $this->container = $container;
    }

    /**
     * @param PostPersistEvent $event
     */
    public function onUpload(PostPersistEvent $event)
    {
        switch($event->getType()) {
            case 'media_user_image':
                $this->userProfileUpload($event);
                break;
            case 'media_attachment':
                $this->attachmentUpload($event);
                break;
            case 'media_email_attachment':
                $this->emailAttachmentUpload($event);
                break;
            default:
                $this->standardUpload($event);
        }
    }

    /**
     * @param PostPersistEvent $event
     */
    protected function standardUpload(PostPersistEvent $event)
    {
        $request = $event->getRequest();

        $context = $request->get('context');
        $mediaType = $request->get('mediaType');
        $providerName = $mediaType == 'image' ? 'sonata.media.provider.image' : 'sonata.media.provider.file';
        $categoryId = $request->get('category');

        if ($categoryId) {
            $category = $this->categoryManager->find($categoryId);
        } else {
            $category = $this->getRootCategory($context);
        }

        $file = $event->getFile();

        $media = new Media();
        $media->setProviderName($providerName);
        $media->setContext($context);
        $media->setCategory($category);
        $media->setBinaryContent($file);

        //TODO - improve the retrieval of the file name
        if(is_array(current($request->files)) && is_array(current(current($request->files)))) {
            $media->setName(current(current($request->files))[0]->getClientOriginalName());
        }

        $provider = $this->pool->getProvider($providerName);
        $provider->transform($media);

        $this->manager->save($media);

        $mediaAdmin = $this->container->get('ok99.privatezone.media.admin.media');
        $response = $event->getResponse();
        $response['name'] = $media->getName();
        $response['size'] = $media->getSize();
        $response['url'] = $mediaType == 'image' ? $provider->generatePublicUrl($media, $provider->getFormatName($media, 'cms')) : $mediaAdmin->generateObjectUrl('edit', $media);
        $response['id'] = $media->getId();
        $response['mediaType'] = $mediaType;
        $response['contentType'] = $media->getContentType();

        @unlink($file->getPathname());
    }

    protected function userProfileUpload(PostPersistEvent $event)
    {
        $request = $event->getRequest();

        $documentRoot = $this->container->get('kernel')->getRootDir() . '/../web';

        /** @var File $file */
        $file = $event->getFile();

        /** @var User $user */
        $userId = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get(User::ID_HANDLER);
        $user = $this->container->get('doctrine.orm.entity_manager')->getRepository('Ok99PrivateZoneUserBundle:User')->find($userId);

        $context = $request->get('context');

        switch($context) {
            case 'avatar':
                $filenamePrefix = sprintf('tmp_%s', $user->getRegnum());
                break;
            default:
                $filenamePrefix = $user->getRegnum();
                break;
        }

        $filename = sprintf('%s_%s.%s', $filenamePrefix, date('U'), strtolower($file->getExtension()));
        $path = sprintf('%s/%s', $file->getPath(), $context);
        $pathname = sprintf('%s/%s', $path, $filename);
        $relativePathname = sprintf('/uploads/%s/%s/%s', $event->getType(), $context, $filename);

        $file->move($path, $filename);

        if (file_exists($pathname)) {
            // remove all old images
            foreach (glob($path . sprintf('/%s_*', $filenamePrefix)) as $file) {
                if (is_file($file) && strtolower(basename($file)) != $filename) {
                    @unlink($file);
                }
            }

            switch($context) {
                case 'photo':
                    // remove all avatar thumbnails
                    foreach(['t','tr'] as $baseDir) {
                        $baseDirPath = sprintf('%s/%s', $documentRoot, $baseDir);
                        $iterator = new \DirectoryIterator($baseDirPath);
                        foreach ($iterator as $node) {
                            if ($node->isDir() && !$node->isDot()) {
                                $path = sprintf('%s%s/%s_*', $node->getPathname(), dirname($relativePathname), $user->getRegnum());
                                foreach (glob($path) as $file) {
                                    if (is_file($file) && strtolower(basename($file)) != strtolower($filename)) {
                                        @unlink($file);
                                    }
                                }
                            }
                        }
                    }

                    $user->{'set'.ucfirst($context)}($relativePathname);
                    $this->container->get('doctrine.orm.default_entity_manager')->flush($user);
                    break;
            }

            $response = $event->getResponse();
            $response['pathname'] = $relativePathname;
        }
    }

    /**
     * @param PostPersistEvent $event
     * @param string|null $className
     */
    protected function attachmentUpload(PostPersistEvent $event, $className = null)
    {
        $request = $event->getRequest();
        $entityManager = $this->container->get('doctrine.orm.default_entity_manager');

        /** @var File $file */
        $file = $event->getFile();

        $path = $file->getPath();
        $filename = $file->getFilename();
        $extension = $file->getExtension();

        if (is_array(current($request->files)) && is_array(current(current($request->files)))) {
            $filename = current(current($request->files))[0]->getClientOriginalName();

            $pathinfo = pathinfo($filename);
            $baseFilename = Transliterator::urlize($pathinfo['filename']);

            $filename = sprintf(
                '%s.%s',
                $baseFilename,
                $pathinfo['extension']
            );

            $filenameIndex = 0;
            while (file_exists(sprintf('%s/%s', $path, $filename))) {
                $filename = sprintf(
                    '%s_%s.%s',
                    $baseFilename,
                    ++$filenameIndex,
                    $pathinfo['extension']
                );
            }
        }

        $file->move($path, $filename);
        $pathname = sprintf('%s/%s', $path, $filename);

        if (file_exists($pathname)) {
            /** @var User $user */
            $user = $this->container->get('security.token_storage')->getToken()->getUser();

            $relativePathname = sprintf('/uploads/%s/%s', $event->getType(), $filename);

            if (function_exists('finfo_file')) {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($pathname);
            } else {
                $mimeType = mime_content_type($pathname);
            }

            if ($className) {
                $attachment = new $className();
            } else {
                $attachment = new Attachment();
            }

            $attachment->setCode(uniqid());
            $attachment->setPath($relativePathname);
            $attachment->setSize(filesize($pathname));
            $attachment->setMimetype($mimeType);

            switch(strtolower($extension)) {
                case 'jpg':
                case 'jpeg':
                case 'png':
                case 'gif':
                case 'bmp':
                    $attachment->setIcon('image');
                    list($imageWidth, $imageHeight) = getimagesize($pathname);
                    if ($imageWidth && $imageHeight) {
                        $attachment->setWidth($imageWidth);
                        $attachment->setHeight($imageHeight);
                    }
                    break;
                case 'doc':
                case 'docx':
                    $attachment->setIcon('word');
                    break;
                case 'xls':
                case 'xlsx':
                    $attachment->setIcon('excel');
                    break;
                case 'pwt':
                case 'pwtx':
                    $attachment->setIcon('powerpoint');
                    break;
                case 'pdf':
                    $attachment->setIcon('pdf');
                    break;
                case 'txt':
                case 'rtf':
                    $attachment->setIcon('text');
                    break;
                case 'mpg':
                case 'mpeg':
                case 'avi':
                case 'mov':
                    $attachment->setIcon('video');
                    break;
                case 'zip':
                case 'rar':
                    $attachment->setIcon('archive');
                    break;
                default:
                    $attachment->setIcon('file');
            }

            $attachment->setCreatedBy($user);
            $attachment->setUpdatedBy($user);

            $entityManager->persist($attachment);
            $entityManager->flush($attachment);

            $response = $event->getResponse();
            $response['id'] = $attachment->getId();
            $response['code'] = $attachment->getCode();
            $response['icon'] = $attachment->getIcon();
            $response['size'] = $attachment->getSizeDecorated();
            $response['pathname'] = $relativePathname;
            $response['filename'] = $filename;
        }
    }

    /**
     * @param PostPersistEvent $event
     */
    protected function emailAttachmentUpload(PostPersistEvent $event)
    {
        $this->attachmentUpload($event, EmailAttachment::class);
    }

    /**
     * @param string $context
     *
     * @return mixed
     */
    protected function getRootCategory($context)
    {
        $rootCategories = $this->categoryManager->getRootCategories(false);

        if (!array_key_exists($context, $rootCategories)) {
            throw new \RuntimeException(sprintf('There is no main category related to context: %s', $context));
        }

        return $rootCategories[$context];
    }

}
