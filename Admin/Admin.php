<?php

namespace Ob\CmsBundle\Admin;

use Symfony\Component\HttpFoundation\Request;

use Ob\CmsBundle\Admin\AdminInterface;

/**
 * Class Admin
 *
 * @package Ob\CmsBundle\Admin
 */
abstract class Admin implements AdminInterface
{
    /**
     * The entity/document class managed by the admin
     *
     * @var string
     */
    protected $class;

    /**
     * The repository used to query entites or documents
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

    public function getItemsPage()
    {
        return 25;
    }

    public function getListDisplay()
    {
        return array();
    }

    public function getListLinks()
    {
        return array();
    }

    public function getListSort()
    {
        return array();
    }

    public function getListSearch()
    {
        return array();
    }

    public function getListActions()
    {
        return array();
    }

    public function getOrderBy()
    {
        return array();
    }

    public function getEditForm()
    {
        return null;
    }

    public function getFormDisplay()
    {
        return array();
    }
}