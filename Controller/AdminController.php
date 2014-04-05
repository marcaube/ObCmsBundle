<?php

namespace Ob\CmsBundle\Controller;

use Doctrine\ORM\QueryBuilder;
use Ob\CmsBundle\Export\ExporterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\EngineInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\FormFactoryInterface;
use Knp\Component\Pager\Paginator;
use Ob\CmsBundle\Admin\AdminContainer;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Ob\CmsBundle\Form\AdminType;
use Ob\CmsBundle\Admin\AdminInterface;

class AdminController
{
    private $templating;
    private $entityManager;
    private $formFactory;
    private $router;
    private $session;
    private $paginator;
    private $container;
    private $templates;
    private $exporter;
    
    public function __construct(
        EngineInterface $templating,
        ObjectManager $entityManager,
        FormFactoryInterface $formFactory,
        RouterInterface $router,
        $session,
        Paginator $paginator,
        AdminContainer $container,
        $templates,
        ExporterInterface $exporter
    )
    {
        $this->templating = $templating;
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
        $this->router = $router;
        $this->session = $session;
        $this->paginator = $paginator;
        $this->container = $container;
        $this->templates = $templates;
        $this->exporter = $exporter;
    }

    /**
     * Render the menu
     *
     * @param $request
     *
     * @return Response
     */
    public function menuAction(Request $request)
    {
        $menu = $this->container->getClasses();

        // Get the current module from the URI
        $current = explode('?', $request->server->get('REQUEST_URI'));
        $current = $current[0];

        return $this->templating->renderResponse($this->templates['menu'], array(
            'items'   => $menu,
            'flat'    => true,
            'current' => $current,
        ));
    }

    /**
     * Display the homepage/dashboard
     *
     * @return Response
     */
    public function dashboardAction()
    {
        return $this->templating->renderResponse($this->templates['dashboard']);
    }

    /**
     * Display the listing page.
     * Handles searches, sorting, actions and pagination on the list of entities.
     *
     * @param Request $request
     * @param string  $name
     *
     * @return Response
     */
    public function listAction(Request $request, $name)
    {
        $this->executeAction($request, $name);

        $adminClass = $this->container->getClass($name);
        $entities = $this->getPagination($adminClass, $request);

        $template = $adminClass->listTemplate() ? : $this->templates['list'];

        return $this->templating->renderResponse($template, array(
            'module'     => $name,
            'adminClass' => $adminClass,
            'entities'    => $entities,
            'search'      => $request->query->get('search') ? : null,
        ));
    }

    /**
     * Export the listing
     *
     * @param Request $request
     * @param string  $name
     * @param string  $format
     *
     * @return Response
     */
    public function exportAction(Request $request, $name, $format)
    {
        $adminClass = $this->container->getClass($name);
        $query = $this->getQuery($adminClass, $request);
        $entities = $query->execute();

        $now = new \DateTime();
        $filename = $now->format('Y-m-d-') . $name . '.' . $format;

        return $this->exporter->export($filename, $format, $entities, $adminClass->listExport());
    }

    /**
     * Display the form to create a new entity
     *
     * @param Request $request
     * @param string  $name
     *
     * @return Response|RedirectResponse
     */
    public function newAction(Request $request, $name)
    {
        $adminClass = $this->container->getClass($name);
        $entity = $adminClass->getClass();
        $entity = new $entity;

        $formType = $adminClass->formType();
        $formType = $formType ? new $formType() : new AdminType($adminClass->formDisplay());
        $form = $this->formFactory->create($formType, $entity);

        if ($request->isMethod('POST')) {
            if ($form->submit($request)->isValid()) {
                $this->entityManager->persist($entity);
                $this->entityManager->flush();
                $this->session->getFlashBag()->add('success', $name . '.create.success');

                return new RedirectResponse($this->router->generate('ObCmsBundle_module_edit', array(
                    'name' => $name,
                    'id' => $entity->getId()
                )));
            }
        }

        $template = $adminClass->newTemplate() ? : $this->templates['new'];

        return $this->templating->renderResponse($template, array(
            'module' => $name,
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

    /**
     * Display the form to edit an entity
     *
     * @param Request $request
     * @param string  $name
     * @param int     $id
     *
     * @return Response
     *
     * @throws NotFoundHttpException
     */
    public function editAction(Request $request, $name, $id)
    {
        $adminClass = $this->container->getClass($name);
        $entity = $this->entityManager->getRepository($adminClass->getRepository())->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ' . $name . ' entity.');
        }

        $formType = $adminClass->formType();
        $formType = $formType ? new $formType() : new AdminType($adminClass->formDisplay());
        $editForm = $this->formFactory->create($formType, $entity);

        if ($request->isMethod('POST')) {
            if ($editForm->submit($request)->isValid()) {
                $this->entityManager->persist($entity);
                $this->entityManager->flush();
                $this->session->getFlashBag()->add('success', $name . '.edit.success');
            }
        }

        $template = $adminClass->editTemplate() ? : $this->templates['edit'];

        return $this->templating->renderResponse($template, array(
            'module' => $name,
            'entity' => $entity,
            'form' => $editForm->createView()
        ));
    }

    /**
     * Executes an action on selected table rows
     *
     * TODO: move in an ObjectManager class
     *
     * @param Request $request
     * @param string  $name
     */
    private function executeAction(Request $request, $name)
    {
        if ($request->getMethod() == 'POST') {
            $action = $request->get('action');
            $ids = $request->get('action-checkbox')?:array();
            $ids = array_keys($ids);

            if (!empty($ids) and $action != '') {
                $adminClass = $this->container->getClass($name);
                $entities = $this->entityManager->getRepository($adminClass->getRepository())->findById($ids);

                foreach ($entities as $entity) {
                    // TODO: check if function exists or raise Exception
                    if ($action == 'delete-action') {
                        $this->entityManager->remove($entity);
                    } else {
                        $entity->{$action}();
                        $this->entityManager->persist($entity);
                    }
                }
                $this->entityManager->flush();
            }
        }
    }

    /**
     * @param AdminInterface $adminClass
     * @param Request        $request
     *
     * @return \Doctrine\ORM\Query
     */
    private function getQuery(AdminInterface $adminClass, Request $request)
    {
        $repository = $this->entityManager->getRepository($adminClass->getRepository());
        /** @var QueryBuilder $query */
        $query = $repository->createQueryBuilder('o');

        // Search
        $this->buildSearch($adminClass->listSearch(), $request->query->get('search') ? : null, $query);

        // Order by
        $this->buildOrderBy($adminClass->listOrderBy(), $query);

        return $query->getQuery();
    }

    /**
     * Get the list of filtered, sorted and paginated entities
     *
     * TODO: move in an ObjectManager class
     *
     * @param AdminInterface $adminClass
     * @param Request        $request
     *
     * @return mixed
     */
    private function getPagination(AdminInterface $adminClass, Request $request)
    {
        $query = $this->getQuery($adminClass, $request);

        return $this->paginator->paginate(
            $query,
            $request->query->get('page', 1),
            $adminClass->listPageItems()
        );
    }

    /**
     * Build the order by clause
     *
     * TODO: move in an ObjectManager class
     *
     * @param $orderByFields
     * @param $query
     */
    private function buildOrderBy($orderByFields, $query)
    {
        if (count($orderByFields) > 0) {
            foreach ($orderByFields as $k => $field) {
                if ($k == 0) {
                    $query->orderBy("o.$field", 'DESC');
                } else {
                    $query->addOrderBy("o.$field", 'DESC');
                }
            }
        }
    }

    /**
     * Build the text search clause
     *
     * TODO: move in an ObjectManager class
     *
     * @param $searchFields
     * @param $searchQuery
     * @param $query
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
}
