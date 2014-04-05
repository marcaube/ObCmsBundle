<?php

namespace Ob\CmsBundle\Datagrid;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\Paginator;
use Ob\CmsBundle\Admin\AdminInterface;

class Datagrid implements DatagridInterface
{
    private $request;
    private $objectManager;
    private $paginator;

    /**
     * @param Request       $request
     * @param ObjectManager $objectManager
     * @param Paginator     $paginator
     */
    public function __construct(Request $request, ObjectManager $objectManager, Paginator $paginator)
    {
        $this->request = $request;
        $this->objectManager = $objectManager;
        $this->paginator = $paginator;
    }

    /**
     * @param AdminInterface $admin
     *
     * @return Query
     */
    public function getQuery(AdminInterface $admin)
    {
        $repository = $this->objectManager->getRepository($admin->getRepository());

        $query = $repository->createQueryBuilder('o');

        $this->filter($admin, $this->request->query->get('filter') ? : null, $query);
        $this->buildSearch($admin->listSearch(), $this->request->query->get('search') ? : null, $query);
        $this->buildOrderBy($admin->listOrderBy(), $query);

        return $query->getQuery();
    }

    /**
     * @param AdminInterface $admin
     *
     * @return mixed
     */
    public function getEntities(AdminInterface $admin)
    {
        return $this->getQuery($admin)->execute();
    }

    /**
     * Get a list of filtered, sorted and paginated entities
     *
     * @param AdminInterface $admin
     *
     * @return PaginationInterface
     */
    public function getPaginatedEntities(AdminInterface $admin)
    {
        $query = $this->getQuery($admin);
        $page = $this->request->query->get('page', 1);
        $limit = $admin->listPageItems();

        return $this->paginator->paginate($query, $page, $limit);
    }

    /**
     * @param AdminInterface $admin
     *
     * TODO: order by value numerically|alphabetically
     *
     * @return array
     */
    public function getFilters(AdminInterface $admin)
    {
        $filters = array();

        foreach ($admin->listFilter() as $name => $class)
        {
            if (gettype($class) == 'array') {
                $filterValues = $class;
            } else {
                $repository = $this->objectManager->getRepository($class);
                $filterValues = $repository->findAll();
            }

            $filters[$name] = $filterValues;
        }

        return $filters;
    }

    /**
     * Build the text search clause
     *
     * @param array        $searchFields
     * @param string       $searchQuery
     * @param QueryBuilder $query
     */
    private function buildSearch($searchFields, $searchQuery, $query)
    {
        if (count($searchFields) == 0 || !$searchQuery) {
            return;
        }

        foreach ($searchFields as $k => $field) {
            if (strpos($field, '.') !== false) {
                list($entity, $column) = explode('.', $field);
                $query->join("o.$entity", $entity);
                $query->orWhere($query->expr()->like("$field", "?$k"));
            } else {
                $query->orWhere($query->expr()->like("o.$field", "?$k"));
            }

            $query->setParameter($k, '%' .$searchQuery . '%');
        }
    }

    /**
     * @param AdminInterface $admin
     * @param array          $filterQuery
     * @param QueryBuilder   $query
     */
    private function filter(AdminInterface $admin, $filterQuery, $query)
    {
        if (count($admin->listFilter()) == 0 || !$filterQuery) {
            return;
        }

        $filterFields = $admin->listFilter();

        foreach ($filterQuery as $field => $value) {
            // Try to infer if the $field is a collection (oneToMany, manyToMany)
            $isCollection = method_exists($admin->getClass(), 'add' . ucwords(rtrim($field, 's')));

            if ($value && array_key_exists($field, $filterFields)) {
                if ($isCollection) {
                    $query->join("o.$field", $field);
                    $query->andWhere("$field = $value");
                } else {
                    $query->andWhere("o.$field = $value");
                }
            }
        }
    }

    /**
     * Build the order by clause
     *
     * @param array        $orderByFields
     * @param QueryBuilder $query
     */
    private function buildOrderBy($orderByFields, $query)
    {
        if (count($orderByFields) == 0) {
            return;
        }

        foreach ($orderByFields as $k => $v) {
            $field = is_string($k) ? $k : $v;
            $direction = is_string($k) ? $v : 'DESC';
            $query->addOrderBy("o.$field", $direction);
        }
    }
}