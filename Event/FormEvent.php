<?php

namespace Ob\CmsBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormInterface;

class FormEvent extends Event
{
    /**
     * @var FormInterface
     */
    private $form;

    /**
     * @var mixed
     */
    private $entity;

    /**
     * @param FormInterface $form
     * @param mixed         $entity
     */
    public function __construct(FormInterface $form, $entity)
    {
        $this->form = $form;
        $this->entity = $entity;
    }

    /**
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }
}