<?php

namespace Ob\CmsBundle\Form;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class LabelPrefixExtension extends AbstractTypeExtension
{
    /**
     * Add the label_prefix option
     *
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(array('label_prefix'));
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (array_key_exists('label_prefix', $options)) {
            $parentData = $form->getParent()->getData();

            if (null !== $parentData) {
                $accessor = PropertyAccess::getPropertyAccessor();
                $labelPrefix = $accessor->getValue($parentData, $options['label_prefix']);
            } else {
                $labelPrefix = null;
            }
            $view->vars['label'] = $labelPrefix . $form->getAttribute('label');
        }
    }

    public function getExtendedType()
    {
        return 'field';
    }
}