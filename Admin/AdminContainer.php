<?php

namespace Ob\CmsBundle\Admin;

use Ob\CmsBundle\Admin\AdminInterface;

class AdminContainer
{
    /**
     * @var array
     */
    private $classes;

    public function __construct()
    {
        $this->classes = array();
    }

    /**
     * @param AdminInterface $admin
     * @param string         $alias
     */
    public function addClass(AdminInterface $admin, $alias)
    {
        $this->classes[$alias] = $admin;
    }

    /**
     * @param string $alias
     *
     * @return AdminInterface|null
     */
    public function getClass($alias)
    {
        if (array_key_exists($alias, $this->classes)) {
            return $this->classes[$alias];
        }
        else {
            return null;
        }
    }

    public function getClasses()
    {
        return $this->classes;
    }
}