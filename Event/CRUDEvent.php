<?php

namespace Ob\CmsBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class CRUDEvent extends Event
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var mixed
     */
    private $entity;

    /**
     * @param Request $request
     * @param mixed   $entity
     */
    public function __construct(Request $request, $entity)
    {
        $this->request = $request;
        $this->entity = $entity;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }
}