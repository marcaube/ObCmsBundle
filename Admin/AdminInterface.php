<?php

namespace Ob\CmsBundle\Admin;

use Symfony\Component\HttpFoundation\Request;

interface AdminInterface
{
    function getClass();

    function getRepository();

    function getItemsPage();

    function getListDisplay();

    function getListLinks();

    function getListSort();

    function getListSearch();

    function getListActions();

    function getOrderBy();

    function getEditForm();

    function getFormDisplay();
}