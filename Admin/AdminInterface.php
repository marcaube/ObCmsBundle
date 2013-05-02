<?php

namespace Ob\CmsBundle\Admin;

use Symfony\Component\HttpFoundation\Request;

interface AdminInterface
{
    function getClass();

    function getRepository();

    function listPageItems();

    function listDisplay();

    function listLinks();

    function listSort();

    function listSearch();

    function listActions();

    function listOrderBy();

    function formType();

    function formDisplay();

    function listTemplate();

    function newTemplate();

    function editTemplate();
}