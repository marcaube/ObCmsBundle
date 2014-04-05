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
     * Build the text search clause
     *
     * @param array        $searchFields
     * @param string       $searchQuery
     * @param QueryBuilder $query
     */
    private function buildSearch($searchFields, $searchQuery, $query)
    {
        if (count($searchFields) > 0 && $searchQuery) {
            $params = array();

            foreach ($searchFields as $k => $field) {
                if ($k == 0) {
                    $query->where($query->expr()->like("o.$field", "?$k"));
                } else {
                    $query->orWhere($query->expr()->like("o.$field", "?$k"));
                }
                $params[$k] = '%' .$searchQuery . '%';
            }

            $query->setParameters($params);
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
        if (count($orderByFields) > 0) {
            $ctr = 0;

            foreach ($orderByFields as $k => $v) {
                $field = is_string($k) ? $k : $v;
                $direction = is_string($k) ? $v : 'DESC';

                if ($ctr++ == 0) {
                    $query->orderBy("o.$field", $direction);
                } else {
                    $query->addOrderBy("o.$field", $direction);
                }
            }
        }
    }
}