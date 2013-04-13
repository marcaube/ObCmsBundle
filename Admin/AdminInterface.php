<?php

namespace Ob\CmsBundle\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;

use Ob\CmsBundle\Admin\ObjectManagerInterface;

interface AdminInterface
{
    function setObjectManager(ObjectManagerInterface $objectManager);

    function getObjectManager();

    function getListTemplate();

    function create($object);

    function update($object);

    function delete($object);

    function prePersist($object);

    function postPersist($object);

    function preUpdate($object);

    function postUpdate($object);

    function preRemove($object);

    function postRemove($object);
}