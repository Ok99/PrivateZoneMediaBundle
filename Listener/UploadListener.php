<?php

namespace Ok99\PrivateZoneCore\MediaBundle\Listener;

use Oneup\UploaderBundle\Event\PostPersistEvent;
use Sonata\MediaBundle\Model\MediaManagerInterface;
use Sonata\ClassificationBundle\Model\CategoryManagerInterface;
use Sonata\MediaBundle\Provider\Pool;
use Ok99\PrivateZoneCore\MediaBundle\Entity\Media;
use Ok99\PrivateZoneCore\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;

class UploadListener
{
    private $manager;

    private $categoryManager;

    private $pool;

    private $container;

    public function __construct(MediaManagerInterface $manager, Pool $pool, CategoryManagerInterface $categoryManager, ContainerInterface $container)
    {
        $this->manager = $manager;
        $this->pool = $pool;
        $this->categoryManager = $categoryManager;
        $this->container = $container;
    }

    public function onUpload(PostPersistEvent $event)
    {
        switch($event->getType()) {
            case 'media_user_image':
                $this->userProfileUpload($event);
                break;
            default:
                $this->standardUpload($event);
        }
    }

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
        $user = $this->container->get('security.token_storage')->getToken()->getUser();

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
