<?php

namespace Ob\CmsBundle\Controller;

use Ob\CmsBundle\Event\FormEvent;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Ob\CmsBundle\Admin\AdminContainer;
use Ob\CmsBundle\Datagrid\DatagridInterface;
use Ob\CmsBundle\Event\CRUDEvent;
use Ob\CmsBundle\Export\ExporterInterface;
use Ob\CmsBundle\Form\AdminType;

class AdminController
{
    private $templating;
    private $entityManager;
    private $formFactory;
    private $router;
    private $session;
    private $container;
    private $datagrid;
    private $templates;
    private $exporter;
    private $dispatcher;
    
    public function __construct(
        EngineInterface $templating,
        ObjectManager $entityManager,
        FormFactoryInterface $formFactory,
        RouterInterface $router,
        $session,
        AdminContainer $container,
        DatagridInterface $datagrid,
        $templates,
        ExporterInterface $exporter,
        EventDispatcherInterface $dispatcher
    )
    {
        $this->templating = $templating;
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
        $this->router = $router;
        $this->session = $session;
        $this->container = $container;
        $this->datagrid = $datagrid;
        $this->templates = $templates;
        $this->exporter = $exporter;
        $this->dispatcher = $dispatcher;
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
        if ($this->executeAction($request, $name)) {
            return new RedirectResponse($this->router->generate('ObCmsBundle_module_list', array('name' => $name)));
        }

        $adminClass = $this->container->getClass($name);
        $entities = $this->datagrid->getPaginatedEntities($adminClass);
        $template = $adminClass->listTemplate() ? : $this->templates['list'];
        $filters = $this->datagrid->getFilters($adminClass);

        return $this->templating->renderResponse($template, array(
            'module'     => $name,
            'adminClass' => $adminClass,
            'entities'   => $entities,
            'search'     => $request->query->get('search') ? : null,
            'filters'    => $filters,
            'selectedFilters' => $request->query->get('filter')
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
        $entities = $this->datagrid->getEntities($adminClass);

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

        $event = new CRUDEvent($request, $entity);
        $this->dispatcher->dispatch('ob_cms.new.init', $event);

        $formType = $adminClass->formType();
        $formType = $formType ? new $formType() : new AdminType($adminClass->formDisplay());
        $form = $this->formFactory->create($formType, $entity);
        $form = $this->addRefererField($request, $form);

        $event = new FormEvent($form, $entity);
        $this->dispatcher->dispatch('ob_cms.form.init', $event);

        if ($request->isMethod('POST')) {
            if ($form->submit($request)->isValid()) {
                $adminClass->prePersist($entity, $form);
                $this->entityManager->persist($entity);
                $this->entityManager->flush();
                $adminClass->postPersist($entity, $form);
                $this->session->getFlashBag()->add('success', $name . '.create.success');

                return new RedirectResponse($this->router->generate('ObCmsBundle_module_edit', array(
                    'name' => $name,
                    'id' => $entity->getId(),
                    'referer' => $this->getReferer($request, $form)
                )));
            }
        }

        $template = $adminClass->newTemplate() ? : $this->templates['new'];

        return $this->templating->renderResponse($template, array(
            'module' => $name,
            'adminClass' => $adminClass,
            'entity' => $entity,
            'form'   => $form->createView(),
            'referer' => $this->getReferer($request, $form)
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
        $entity = $this->entityManager->getRepository($adminClass->getClass())->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ' . $name . ' entity.');
        }

        $formType = $adminClass->formType();
        $formType = $formType ? new $formType() : new AdminType($adminClass->formDisplay());
        $editForm = $this->formFactory->create($formType, $entity);
        $editForm = $this->addRefererField($request, $editForm);

        $event = new FormEvent($editForm, $entity);
        $this->dispatcher->dispatch('ob_cms.form.init', $event);

        if ($request->isMethod('POST')) {
            if ($editForm->submit($request)->isValid()) {
                $adminClass->prePersist($entity, $editForm);
                $this->entityManager->persist($entity);
                $this->entityManager->flush();
                $adminClass->postPersist($entity, $editForm);
                $this->session->getFlashBag()->add('success', $name . '.edit.success');
            }
        }

        $template = $adminClass->editTemplate() ? : $this->templates['edit'];

        return $this->templating->renderResponse($template, array(
            'module' => $name,
            'adminClass' => $adminClass,
            'entity' => $entity,
            'form' => $editForm->createView(),
            'referer' => $this->getReferer($request, $editForm)
        ));
    }

    /**
     * @param Request       $request
     * @param FormInterface $form
     *
     * @return FormInterface
     */
    private function addRefererField($request, $form)
    {
        $referer = $this->getReferer($request, $form);

        $form->add('referer', 'hidden', array('mapped' => false));
        $form->get('referer')->setData($referer);

        return $form;
    }

    /**
     * @param Request       $request
     * @param FormInterface $form
     *
     * @return string
     */
    private function getReferer($request, $form)
    {
        if ($form->has('referer')) {
            $referer = $form->get('referer')->getData();
        } elseif ($request->query->has('referer')) {
            $referer = $request->query->get('referer');
        } else {
            $referer = $request->headers->get("referer");
        }

        return $referer;
    }

    /**
     * Executes an action on selected table rows
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
                $entities = $this->entityManager->getRepository($adminClass->getClass())->findById($ids);

                foreach ($entities as $entity) {
                    // TODO: check if function exists or raise Exception
                    if ($action == 'delete-action') {
                        $this->entityManager->remove($entity);
                    } else {
                        if (method_exists($adminClass, $action)) {
                            $entity = $adminClass->{$action}($entity);
                        } else {
                            $entity->{$action}();
                        }
                        $this->entityManager->persist($entity);
                    }
                }
                $this->entityManager->flush();
            }

            return true;
        }

        return false;
    }
}
