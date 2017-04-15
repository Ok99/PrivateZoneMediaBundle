<?php

namespace Ok99\PrivateZoneCore\MediaBundle\Twig\Extension;

use Sonata\FormatterBundle\Extension\BaseProxyExtension;
use Ok99\PrivateZoneCore\MediaBundle\Twig\TokenParser\PathTokenParser;

class FormatterMediaExtension extends BaseProxyExtension
{
    protected $twigExtension;

    /**
     * @param \Twig_Extension $twigExtension
     */
    public function __construct(\Twig_Extension $twigExtension)
    {
        $this->twigExtension = $twigExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedTags()
    {
        return array(
            'og_path',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods()
    {
        return array(
            'Sonata\MediaBundle\Model\MediaInterface' => array(
                'getproviderreference'
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenParsers()
    {
        return array(
            new PathTokenParser($this->getName()),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTwigExtension()
    {
        return $this->twigExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ok99_privatezone_formatter_media';
    }

    /**
     * @param integer $media
     * @param string  $format
     *
     * @return string
     */
    public function path($media = null, $format)
    {
        return $this->getTwigExtension()->path($media, $format);
    }
}
