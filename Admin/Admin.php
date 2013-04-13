<?php

namespace Ob\CmsBundle\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;

use Ob\CmsBundle\Admin\AdminInterface;
use Ob\CmsBundle\Admin\ObjectManagerInterface;

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
     * The object manager used to persist objects to a database or a filesystem.
     *
     * @var ObjectManagerInterface
     */
    protected $objectManager;

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

    public function __construct($managerType, $class, $repository)
    {
        $this->objectManager = ucfirst($managerType) . 'ObjectManager';
        $this->class = $class;
        $this->repository = $repository;

    }

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function setObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @return ObjectManagerInterface
     */
    public function getObjectManager()
    {
        return $this->objectManager;
    }

    public function getListTemplate()
    {
        // TODO: param
        return 'ObCmsBundle:CRUD:list.html.twig';
    }

    public function getList()
    {
        $repository = $this->get('doctrine')->getRepository($this->repository);
        $objects = $repository->findAll();

        return $objects;
    }

    /**
     * @param mixed $object
     */
    public function create($object)
    {
        $this->prePersist($object);
        $this->getObjectManager()->create($object);
        $this->postPersist($object);
    }

    /**
     * @param mixed $object
     */
    public function update($object)
    {
        $this->preUpdate($object);
        $this->getObjectManager()->create($object);
        $this->postUpdate($object);
    }

    /**
     * @param mixed $object
     */
    public function delete($object)
    {
        $this->preRemove($object);
        $this->getObjectManager()->create($object);
        $this->postRemove($object);
    }


    public function prePersist($object) {

    }

    public function postPersist($object) {

    }

    public function preUpdate($object) {

    }

    public function postUpdate($object) {

    }

    public function preRemove($object) {

    }

    public function postRemove($object) {

    }

}