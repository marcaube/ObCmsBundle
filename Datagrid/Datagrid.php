<?php

namespace Ob\CmsBundle\Datagrid;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Ob\CmsBundle\Admin\AdminInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class Datagrid implements DatagridInterface
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var PaginatorInterface
     */
    private $paginator;

    /**
     * @param RequestStack           $requestStack
     * @param EntityManagerInterface $entityManager
     * @param PaginatorInterface     $paginator
     */
    public function __construct(RequestStack $requestStack, EntityManagerInterface $entityManager, PaginatorInterface $paginator)
    {
        $this->request       = $requestStack->getCurrentRequest();
        $this->entityManager = $entityManager;
        $this->paginator     = $paginator;
    }

    /**
     * @param AdminInterface $admin
     *
     * @return Query
     */
    public function getQuery(AdminInterface $admin)
    {
        $repository = $this->entityManager->getRepository($admin->getCLass());

        $query = $repository->createQueryBuilder('o');
        $query = $admin->query($query);
        $this->filter($admin, $this->request->query->get('filter') ?: null, $query);
        $this->buildSearch($admin->listSearch(), $this->request->query->get('search') ?: null, $query);
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
        $page  = $this->request->query->get('page', 1);
        $limit = $admin->listPageItems();

        return $this->paginator->paginate($query, $page, $limit);
    }

    /**
     * @param AdminInterface $admin
     *
     * @return array
     */
    public function getFilters(AdminInterface $admin)
    {
        $filters = array();

        foreach ($admin->listFilter() as $name => $class) {
            if (gettype($class) == 'array') {
                $filterValues = $class;
            } else {
                $repository   = $this->entityManager->getRepository($class);
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

        $expr  = $query->expr()->orX();
        $joins = array();

        foreach ($searchFields as $k => $field) {
            if (strpos($field, '.') !== false) {
                list($entity,) = explode('.', $field);

                if (!in_array($entity, $joins)) {
                    $query->join("o.$entity", $entity);
                    $joins[] = $entity;
                }

                $expr->add($query->expr()->like("$field", "?$k"));
            } else {
                $expr->add($query->expr()->like("o.$field", "?$k"));
            }

            $query->setParameter($k, '%' . $searchQuery . '%');
        }

        $query->andWhere($expr);
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

        $filterFields    = $admin->listFilter();
        $joinedRelations = array();

        foreach ($filterQuery as $field => $value) {
            // Try to infer if the $field is a collection (oneToMany, manyToMany)
            $isCollection = method_exists($admin->getClass(), 'add' . ucwords(rtrim($field, 's')));
            $isRelation   = null;

            // Check if the $field is a relation (onToOne, manyToOne)
            if (strpos($field, '.') !== false) {
                list($entity, $column) = explode('.', $field);
                $isRelation            = method_exists($admin->getClass(), 'set' . ucwords($entity));
            } else {
                $column = $field;
            }

            if ($value !== null && $value !== '' && array_key_exists($field, $filterFields)) {
                if ($isCollection) {
                    $query->join("o.$field", $field);
                    $query->andWhere("$field = $value");
                } elseif ($isRelation) {
                    if (isset($entity) && !in_array($entity, $joinedRelations)) {
                        $query->join("o.$entity", $entity);
                        $joinedRelations[] = $entity;
                    }

                    // Check if the $field is a relation's relation (onToOne, manyToOne)
                    if (method_exists($filterFields[$field], 'set' . ucwords($column))) {
                        $query->andWhere("$field = $value");
                    } else {
                        // If not, we go full retard and assume it's a collection ... bring the facepalms!
                        $query->join("$field", $column);
                        $query->andWhere("$column = $value");
                    }
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
            $field     = is_string($k) ? $k : $v;
            $direction = is_string($k) ? $v : 'DESC';
            $query->addOrderBy("o.$field", $direction);
        }
    }
}
