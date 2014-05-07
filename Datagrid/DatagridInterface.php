<?php

namespace Ob\CmsBundle\Datagrid;

use Ob\CmsBundle\Admin\AdminInterface;

interface DatagridInterface
{
    public function getQuery(AdminInterface $admin);

    public function getEntities(AdminInterface $admin);

    public function getPaginatedEntities(AdminInterface $admin);
}
