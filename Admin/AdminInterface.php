<?php

namespace Ob\CmsBundle\Admin;

interface AdminInterface
{
    public function getClass();

    public function getRepository();

    public function listPageItems();

    public function listDisplay();

    public function listLinks();

    public function listSort();

    public function listSearch();

    public function listFilter();

    public function listActions();

    public function listOrderBy();

    public function listExport();

    public function formType();

    public function formDisplay();

    public function listTemplate();

    public function newTemplate();

    public function editTemplate();
}
