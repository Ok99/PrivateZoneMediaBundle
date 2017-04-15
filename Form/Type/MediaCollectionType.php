<?php

namespace Ok99\PrivateZoneCore\MediaBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Sonata\CoreBundle\Form\EventListener\ResizeFormListener;

class MediaCollectionType extends AbstractType
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $listener = new ResizeFormListener(
            $options['type'],
            $options['type_options'],
            $options['modifiable'],
            $options['pre_bind_data_callback']
        );

        $builder->addEventSubscriber($listener);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['btn_list'] = $options['btn_list'];
        $view->vars['btn_upload_new'] = $options['btn_upload_new'];
        $view->vars['btn_upload_new_file'] = $options['btn_upload_new_file'];
        $view->vars['btn_catalogue'] = $options['btn_catalogue'];
        $view->vars['media_field'] = $options['media_field'];
        $view->vars['media_admin'] = $options['media_admin'];
        $view->vars['category'] = $options['category'];
        $view->vars['context'] = $options['context'];
        $view->vars['media_type'] = $options['media_type'];
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $media_admin = $this->container->get('ok99.privatezone.media.admin.media');
        $resolver->setDefaults(array(
            'modifiable'             => false,
            'type'                   => 'text',
            'type_options'           => array(),
            'pre_bind_data_callback' => null,
            'btn_list'               => 'link_list',
            'btn_upload_new'         => 'link_upload_new',
            'btn_upload_new_file'    => 'link_upload_new_file',
            'btn_catalogue'          => 'Ok99PrivateZoneMediaBundle',
            'media_field'            => 'media',
            'media_admin'            => $media_admin,
            'category'               => null,
            'context'                => 'default',
            'media_type'             => 'image',
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'ok99_privatezone_type_media_collection';
    }
}
