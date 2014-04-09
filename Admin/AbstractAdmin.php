<?php

namespace Ob\CmsBundle\Admin;

abstract class AbstractAdmin implements AdminInterface
{
    /**
     * The entity/document class managed by the admin
     *
     * @var string
     */
    protected $class;

    /**
     * Returns the Entity class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    public function listPageItems()
    {
        return 25;
    }

    public function listDisplay()
    {
        return array();
    }

    public function listLinks()
    {
        return array();
    }

    public function listSort()
    {
        return array();
    }

    public function listSearch()
    {
        return array();
    }

    public function listFilter()
    {
        return array();
    }

    public function listActions()
    {
        return array();
    }

    public function listOrderBy()
    {
        return array();
    }

    public function listExport()
    {
        return array();
    }

    public function formType()
    {
        return null;
    }

    public function formDisplay()
    {
        return array();
    }

    public function listTemplate()
    {
        return null;
    }

    public function newTemplate()
    {
        return null;
    }

    public function editTemplate()
    {
        return null;
    }

    public function inlineAdmin()
    {
        return null;
    }

    public function prePersist($entity)
    {
        return;
    }

    public function postPersist($entity)
    {
        return;
    }
}
