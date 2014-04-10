<?php

namespace Ob\CmsBundle\Admin;

use Doctrine\ORM\QueryBuilder;

interface AdminInterface
{
    public function getClass();

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

    public function inlineAdmin();

    public function prePersist($entity);

    public function postPersist($entity);

    public function query(QueryBuilder $qb);
}
