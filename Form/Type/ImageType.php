<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Ok99\PrivateZoneCore\MediaBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Sonata\AdminBundle\Form\DataTransformer\ModelToIdTransformer;

class ImageType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->resetViewTransformers()
            ->addViewTransformer(new ModelToIdTransformer($options['model_manager'], $options['class']));
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (isset($view->vars['sonata_admin'])) {
            // set the correct edit mode
            $view->vars['sonata_admin']['edit'] = 'list';
            $view->vars['context'] = $options['context'];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'model_manager'     => null,
            'class'             => null,
            'context'           => 'default'
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'hidden';
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'ok99_privatezone_type_image';
    }
}
