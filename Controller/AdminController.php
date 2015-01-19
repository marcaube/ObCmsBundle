<?php

namespace Ob\CmsBundle\Controller;

use Doctrine\Common\Persistence\ObjectManager;
use Ob\CmsBundle\Admin\AdminContainer;
use Ob\CmsBundle\Admin\AdminInterface;
use Ob\CmsBundle\Datagrid\DatagridInterface;
use Ob\CmsBundle\Event\CRUDEvent;
use Ob\CmsBundle\Event\FormEvent;
use Ob\CmsBundle\Export\ExporterInterface;
use Ob\CmsBundle\Form\AdminType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\EngineInterface;

class AdminController
{
    /**
     * @var EngineInterface
     */
    private $templating;

    /**
     * @var ObjectManager
     */
    private $entityManager;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var AdminContainer
     */
    private $container;

    /**
     * @var DatagridInterface
     */
    private $datagrid;

    /**
     * @var array
     */
    private $templates;

    /**
     * @var ExporterInterface
     */
    private $exporter;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @param EngineInterface          $templating
     * @param ObjectManager            $entityManager
     * @param FormFactoryInterface     $formFactory
     * @param RouterInterface          $router
     * @param SessionInterface         $session
     * @param AdminContainer           $container
     * @param DatagridInterface        $datagrid
     * @param array                    $templates
     * @param ExporterInterface        $exporter
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        EngineInterface $templating,
        ObjectManager $entityManager,
        FormFactoryInterface $formFactory,
        RouterInterface $router,
        SessionInterface $session,
        AdminContainer $container,
        DatagridInterface $datagrid,
        $templates,
        ExporterInterface $exporter,
        EventDispatcherInterface $dispatcher
    ) {
        $this->templating    = $templating;
        $this->entityManager = $entityManager;
        $this->formFactory   = $formFactory;
        $this->router        = $router;
        $this->session       = $session;
        $this->container     = $container;
        $this->datagrid      = $datagrid;
        $this->templates     = $templates;
        $this->exporter      = $exporter;
        $this->dispatcher    = $dispatcher;
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
        $entities   = $this->datagrid->getPaginatedEntities($adminClass);
        $template   = $adminClass->listTemplate() ?: $this->templates['list'];
        $filters    = $this->datagrid->getFilters($adminClass);

        return $this->templating->renderResponse($template, array(
            'module'          => $name,
            'adminClass'      => $adminClass,
            'entities'        => $entities,
            'search'          => $request->query->get('search') ?: null,
            'filters'         => $filters,
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
        $entities   = $this->datagrid->getEntities($adminClass);

        $filename = $this->getExportFilename(
            $name,
            $this->datagrid->getFilters($adminClass),
            $request->query->get('filter', array()),
            $format
        );

        return $this->exporter->export($filename, $format, $entities, $adminClass->listExport());
    }

    /**
     * Builds a file name for the export from the admin class name and the datagrid filters
     *
     * @param string $className   The name of the admin class
     * @param array  $filterNames The list of all datagrid filters available for this admin class
     * @param array  $queryFilter The filters used
     * @param string $format      The file format to export (e.g. xls)
     *
     * @return string
     */
    private function getExportFilename($className, $filterNames, $queryFilter, $format)
    {
        $now      = new \DateTime();
        $filename = $now->format('Y-m-d-') . $className;

        foreach ($queryFilter as $filter => $value) {
            if ($value || $value == "0") {
                // The array key and the entity id are not the same,
                // so we need to loop through the array to find the
                // entity we are looking for.
                if (gettype($filterNames[$filter][$value]) == 'object') {
                    foreach ($filterNames[$filter] as $object) {
                        if ($object->getId() == $value) {
                            $filename .= '-' . strtolower($object->__toString());
                        }
                    }
                } else {
                    $filename .= '-' . strtolower($filterNames[$filter][$value]);
                }
            }
        }

        // Remove characters that are not "filename-safe"
        $filename = preg_replace('/(\s)/', '-', $filename);
        $filename = preg_replace('/([^\w\d\-_~\[\]\(\)])/u', '', $filename);
        $filename .= '.' . $format;

        return $filename;
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
        $entity     = $adminClass->getClass();
        $entity     = new $entity();

        $this->dispatcher->dispatch('ob_cms.new.init', new CRUDEvent($request, $entity));
        $form = $this->getFormForAdmin($request, $adminClass, $entity);
        $this->dispatcher->dispatch('ob_cms.form.init', new FormEvent($form, $entity));

        if ($request->isMethod('POST')) {
            if ($form->submit($request)->isValid()) {
                $this->processForm($form, $adminClass, $entity);
                $this->session->getFlashBag()->add('success', $name . '.create.success');

                return new RedirectResponse($this->router->generate('ObCmsBundle_module_edit', array(
                    'name'    => $name,
                    'id'      => $entity->getId(),
                    'referer' => $this->getReferer($request, $form)
                )));
            }
        }

        $template = $adminClass->newTemplate() ?: $this->templates['new'];

        return $this->templating->renderResponse($template, array(
            'module'     => $name,
            'adminClass' => $adminClass,
            'entity'     => $entity,
            'form'       => $form->createView(),
            'referer'    => $this->getReferer($request, $form)
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
        $entity     = $this->entityManager->getRepository($adminClass->getClass())->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ' . $name . ' entity.');
        }

        $editForm = $this->getFormForAdmin($request, $adminClass, $entity);
        $this->dispatcher->dispatch('ob_cms.form.init', new FormEvent($editForm, $entity));

        if ($request->isMethod('POST')) {
            if ($editForm->submit($request)->isValid()) {
                $this->processForm($editForm, $adminClass, $entity);
                $this->session->getFlashBag()->add('success', $name . '.edit.success');
            }
        }

        $template = $adminClass->editTemplate() ?: $this->templates['edit'];

        return $this->templating->renderResponse($template, array(
            'module'     => $name,
            'adminClass' => $adminClass,
            'entity'     => $entity,
            'form'       => $editForm->createView(),
            'referer'    => $this->getReferer($request, $editForm)
        ));
    }

    /**
     * @param Request        $request
     * @param AdminInterface $adminClass
     * @param object         $entity
     *
     * @return FormInterface
     */
    private function getFormForAdmin(Request $request, AdminInterface $adminClass, $entity = null)
    {
        $formType = $adminClass->formType();
        $formType = $formType ? new $formType() : new AdminType($adminClass->formDisplay());
        $form     = $this->formFactory->create($formType, $entity);
        $form     = $this->addRefererField($request, $form);

        return $form;
    }

    private function processForm($form, AdminInterface $adminClass, $entity)
    {
        $adminClass->prePersist($entity, $form);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        $adminClass->postPersist($entity, $form);
    }

    /**
     * @param Request       $request
     * @param FormInterface $form
     *
     * @return FormInterface
     */
    private function addRefererField(Request $request, FormInterface $form)
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
    private function getReferer(Request $request, FormInterface $form)
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
     *
     * @return bool
     */
    private function executeAction(Request $request, $name)
    {
        if ($request->getMethod() == 'POST') {
            $action = $request->get('action');
            $ids    = array_keys($request->get('action-checkbox', array()));

            if (!empty($ids) and $action != '') {
                $adminClass = $this->container->getClass($name);
                $entities   = $this->entityManager->getRepository($adminClass->getClass())->findById($ids);

                foreach ($entities as $entity) {
                    if ($action == 'delete-action') {
                        $this->entityManager->remove($entity);
                    } else {
                        $entity = $this->executeActionOnAdminOrEntity($adminClass, $entity, $action);
                        $this->entityManager->persist($entity);
                    }
                }
                $this->entityManager->flush();
            }

            return true;
        }

        return false;
    }

    /**
     * @param AdminInterface $adminClass
     * @param object         $entity
     * @param string         $action
     *
     * @return mixed
     */
    private function executeActionOnAdminOrEntity(AdminInterface $adminClass, $entity, $action)
    {
        if (method_exists($adminClass, $action)) {
            $entity = $adminClass->{$action}($entity);
        } else {
            if (!method_exists($entity, $action)) {
                throw new \InvalidArgumentException(sprintf('The method %s does not exist on entity', $action));
            }

            $entity->{$action}();
        }

        return $entity;
    }
}
