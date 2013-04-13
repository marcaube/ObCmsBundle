<?php

namespace Ob\CmsBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Admin Form object
 */
class AdminType extends AbstractType
{
    private $fields;

    public function __construct($fields)
    {
        $this->fields = $fields;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($this->fields as $field) {
            $builder->add($field);
        }
    }

    public function getName()
    {
        return 'ob_cms_admin_form';
    }
}