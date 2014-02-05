<?php

namespace Ob\CmsBundle\Admin;

use Symfony\Component\HttpFoundation\Request;

use Ob\CmsBundle\Admin\AdminInterface;

abstract class AbstractAdmin implements AdminInterface
{
    /**
     * The entity/document class managed by the admin
     *
     * @var string
     */
    protected $class;

    /**
     * The repository used to query entities or documents
     *
     * @var string
     */
    protected $repository;

    /**
     * @var Request
     */
    protected $request;

    /**
     * The maximum number of objects shown on the list page
     *
     * @var int
     */
    protected $itemsPerPage;

    /**
     * The properties to display on the list page
     *
     * @var array
     */
    protected $listDisplay;

    /**
     * The properties on the listing page to display as a link
     *
     * @var array
     */
    protected $listLinks;

    /**
     * The properties with sorting enabled
     *
     * @var array
     */
    protected $listSort;

    /**
     * The properties on which we can do a text search
     *
     * @var array
     */
    protected $listTextSearch;

    /**
     * The properties available as extra-filters for advanced search
     *
     * @var array
     */
    protected $listFilters;

    /**
     * The actions that can be performed on the object from the list
     *
     * @var array
     */
    protected $listActions;

    /**
     * Returns the Entity class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Returns the Repository
     *
     * @return string
     */
    public function getRepository()
    {
        return $this->repository;
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

    public function listActions()
    {
        return array();
    }

    public function listOrderBy()
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
}
