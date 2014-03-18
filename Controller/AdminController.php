<?php

namespace Ob\CmsBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
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
    private $request;
    private $templating;
    private $entityManager;
    protected $formFactory;
    private $paginator;
    private $container;
    private $templates;
    
    public function __construct(
        Request $request,
        EngineInterface $templating,
        ObjectManager $entityManager,
        FormFactoryInterface $formFactory,
        Paginator $paginator,
        AdminContainer $container,
        $templates
    )
    {
        $this->request = $request;
        $this->templating = $templating;
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
        $this->paginator = $paginator;
        $this->container = $container;
        $this->templates = $templates;
    }

    /**
     * Render the menu
     *
     * @param $request
     *
     * @return Response
     */
    public function menuAction($request)
    {
        $menu = $this->container->getClasses();

        // Get the current module from the URI
        $current = explode('?', $this->request->server->get('REQUEST_URI'));
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
        $this->executeAction($name);

        $adminClass = $this->container->getClass($name);
        $entities = $this->getEntities($adminClass, $request);

        $template = $adminClass->listTemplate() ? : $this->templates['list'];

        return $this->templating->renderResponse($template, array(
            'module'     => $name,
            'adminClass' => $adminClass,
            'entities'    => $entities,
            'search'      => $request->query->get('search') ? : null,
        ));
    }

    /**
     * Display the form to create a new entity
     *
     * @param string $name
     *
     * @return Response
     */
    public function newAction($name)
    {
        $adminClass = $this->container->getClass($name);
        $entity = $adminClass->getClass();
        $entity = new $entity;

        $formType = $adminClass->formType() ? : new AdminType($adminClass->formDisplay());
        $form = $this->formFactory->create($formType, $entity);

        $template = $adminClass->newTemplate() ? : $this->templates['new'];

        return $this->templating->renderResponse($template, array(
            'module' => $name,
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

    /**
     * Handle the creation of a new entity
     *
     * @param Request $request
     * @param string  $name
     *
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request, $name)
    {
        $adminClass = $this->container->getClass($name);
        $entity = $adminClass->getClass();
        $entity = new $entity;

        $formType = $adminClass->formType() ? : new AdminType($adminClass->formDisplay());
        $form = $this->createForm($formType, $entity);

        if ($form->bind($request)->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                $name . '.create.success'
            );

            return $this->redirect($this->generateUrl('ObCmsBundle_module_edit', array(
                'name' => $name,
                'id' => $entity->getId()
            )));
        }

        $template = $adminClass->newTemplate() ? : $this->templates['new'];

        return $this->templating->renderResponse($template, array(
            'module'      => $name,
            'entity'      => $entity,
            'form'        => $form->createView(),
        ));
    }

    /**
     * Display the form to edit an entity
     *
     * @param string $name
     * @param int    $id
     *
     * @return Response
     *
     * @throws NotFoundHttpException
     */
    public function editAction($name, $id)
    {
        $adminClass = $this->container->getClass($name);
        $entity = $this->entityManager->getRepository($adminClass->getRepository())->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ' . $name . ' entity.');
        }

        $formType = $adminClass->formType() ? : new AdminType($adminClass->formDisplay());
        $editForm = $this->formFactory->create($formType, $entity);

        $template = $adminClass->editTemplate() ? : $this->templates['edit'];

        return $this->templating->renderResponse($template, array(
            'module' => $name,
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'previous'  =>  $this->request->server->get('HTTP_REFERER')? : null
        ));
    }

    /**
     * Update an entity
     *
     * @param Request $request
     * @param string  $name
     * @param int     $id
     *
     * @return RedirectResponse|Response
     *
     * @throws NotFoundHttpException
     */
    public function updateAction(Request $request, $name, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $adminClass = $this->container->getClass($name);
        $entity = $em->getRepository($adminClass->getRepository())->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ' . $name . ' entity.');
        }

        $editForm = $this->createForm(new AdminType($adminClass->formDisplay()), $entity);

        if ($editForm->bind($request)->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                $name . '.edit.success'
            );

            return $this->redirect($this->generateUrl('ObCmsBundle_module_edit', array('name' => $name, 'id' => $id)));
        }

        return $this->templating->renderResponse('ObCmsBundle:Edit:edit.html.twig', array(
            'module'    => $name,
            'entity'    => $entity,
            'edit_form' => $editForm->createView()
        ));
    }

    /**
     * Executes an action on selected table rows
     *
     * @param string $name
     */
    private function executeAction($name)
    {
        if ($this->request->getMethod() == 'POST') {
            $action = $this->request->request->get('action');
            $ids = $this->request->request->get('action-checkbox')?:array();
            $ids = array_keys($ids);

            if (!empty($ids) and $action != '') {
                $adminClass = $this->container->getClass($name);
                $em = $this->getDoctrine()->getManager();
                $entities = $em->getRepository($adminClass->getRepository())->findById($ids);

                foreach ($entities as $entity) {
                    // TODO: check if function exists or raise Exception
                    if ($action == 'delete-action') {
                        $em->remove($entity);
                    } else {
                        $entity->{$action}();
                        $em->persist($entity);
                    }
                }
                $em->flush();
            }
        }
    }

    /**
     * Get the list of filtered, sorted and paginated entities
     *
     * @param AdminInterface $adminClass
     * @param Request        $request
     *
     * @return mixed
     */
    private function getEntities(AdminInterface $adminClass, Request $request)
    {
        $repository = $this->entityManager->getRepository($adminClass->getRepository());
        $query = $repository->createQueryBuilder('o');

        // Search
        $this->buildSearch($adminClass->listSearch(), $request->query->get('search') ? : null, $query);

        // Order by
        $this->buildOrderBy($adminClass->listOrderBy(), $query);

        return $this->paginator->paginate(
            $query,
            $request->query->get('page', 1),
            $adminClass->listPageItems()
        );
    }

    /**
     * Build the order by clause
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
