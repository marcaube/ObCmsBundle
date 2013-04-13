<?php

namespace Ob\CmsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Ob\CmsBundle\Form\AdminType;
use Tco\Cms\UserBundle\Entity\User;

class AdminController extends Controller
{
    /**
     * Render the menu
     *
     * @param $request
     *
     * @return Response
     */
    public function menuAction($request)
    {
        // Get the menu from the config or defaults to the list of modules
//        $menu = $this->container->getParameter('menu');
//        if (!$menu) {
            $menu = $this->container->getParameter('bundles');
            $flat = true;
//        }

        // Get the current module from the URI
        $current = explode('?', $this->container->get('request')->server->get('REQUEST_URI'));
        $current = $current[0];

        return $this->render('ObCmsBundle:Menu:menu.html.twig', array(
            'items'   => $menu,
            'flat'    => isset($flat),
            'current' => $current,
            'request' => $request  // For the locale switcher in the menu
        ));
    }


    /**
     * Render the locale switcher if there is more than one locale available
     *
     * @param $request
     *
     * @return Response
     */
    public function localesAction($request)
    {
        $locales = $this->container->getParameter('locales');
        $locale = $request->query->get('_locale')?:null;

        if(in_array($locale, $locales)) {
            $this->get('session')->setLocale($locale);
        }

        return $this->render('ObCmsBundle:Admin:locales.html.twig', array(
            'locale'  =>  $request->getLocale(),
            'locales' =>  $locales,
        ));
    }


    /**
     * Display the homepage/dashboard
     *
     * @return Response
     */
    public function dashboardAction()
    {
        $bundles = $this->container->getParameter('bundles');

        $template = 'ObCmsBundle:Admin:' . (empty($bundles) ? 'welcome.html.twig' : 'dashboard.html.twig');

        return $this->render($template);
    }


    /**
     * Display the listing page.
     * Handles searches, sorting, actions and pagination on the list of entities.
     *
     * @param string $name
     *
     * @return Response
     */
    public function listAction($name)
    {
        // Forward action if Controller is Overriden
        $this->executeForward($name, 'listController');

        // Execute actions on selected rows
        $this->executeAction($name);

        // Get the sorted and paginated entities
        $entities = $this->getEntities($name);

        // Retreive the list of params
        $listDisplay = $this->getParamOrNull($name, 'listDisplay');
        $listLinks   = $this->getParamOrNull($name, 'listLinks');
        $listSort    = $this->getParamOrNull($name, 'listSort');
        $listActions = $this->getParamOrNull($name, 'listActions');
        $listSearch  = $this->getParamOrNull($name, 'listSearch');
        $template    = $this->getParamOrNull($name, 'listTemplate');

        $template = $template ? : 'ObCmsBundle:List:list.html.twig';

        return $this->render($template, array(
            'module'      => $name,
            'entities'    => $entities,
            'listDisplay' => $listDisplay,
            'listLinks'   => $listLinks,
            'listSort'    => $listSort,
            'listActions' => $listActions,
            'listSearch'  => count($listSearch) > 0,
            'search'      => $this->get('request')->query->get('search') ? : null,
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
        // Forward action if Controller is Overriden
        $this->executeForward($name, 'newController');

        // Create a new entity
        $entity = $this->getParamOrNull($name, 'entity');
        $entity = new $entity;

        // Create the form
        $formDisplay = $this->getParamOrNull($name, 'formDisplay');
        $form = $this->createForm(new AdminType($formDisplay), $entity);

        $template = $this->getParamOrNull($name, 'newTemplate');
        $template = $template ? : 'ObCmsBundle:New:new.html.twig';

        return $this->render($template, array(
            'module' => $name,
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }


    /**
     * Handle the creation of a new entity
     *
     * @param string $name
     *
     * @return RedirectResponse|Response
     */
    public function createAction($name)
    {
        // Forward action if Controller is Overriden
        $this->executeForward($name, 'createController');

        // Create a new entity
        $entity = $this->getParamOrNull($name, 'entity');
        $entity = new $entity;

        // Create the form
        $formDisplay = $this->getParamOrNull($name, 'formDisplay');
        $form = $this->createForm(new AdminType($formDisplay), $entity);

        // Bind the form with the request
        $request = $this->getRequest();
        $form->bindRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->get('session')->setFlash('success', $name . '.create.success');

            return $this->redirect($this->generateUrl('ObCmsBundle_module_edit', array(
                'name' => $name,
                'id' => $entity->getId()
            )));
        }

        $template = $this->getParamOrNull($name, 'newTemplate');
        $template = $template ? : 'ObCmsBundle:New:new.html.twig';

        return $this->render($template, array(
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
        // Forward action if Controller is Overriden
        $this->executeForward($name, 'editController');

        // Retreive the entity
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->getParamOrNull($name, 'repository'))->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ' . $name . ' entity.');
        }

        // Create the form
        $form = $this->getParamOrNull($name, 'editForm') ? : 'Ob\CmsBundle\Form\AdminType';
        $editForm = $this->createForm(new $form($this->getParamOrNull($name, 'formDisplay')), $entity);

        $template = $this->getParamOrNull($name, 'editTemplate');
        $template = $template ? : 'ObCmsBundle:Edit:edit.html.twig';

        return $this->render($template, array(
            'module' => $name,
            'entity' => $entity,
            'edit_form'   => $editForm->createView(),
        ));
    }


    /**
     * Update an entity
     *
     * @param string $name
     * @param int    $id
     *
     * @return RedirectResponse|Response
     *
     * @throws NotFoundHttpException
     */
    public function updateAction($name, $id)
    {
        // Forward action if Controller is Overriden
        $this->executeForward($name, 'updateController');

        // Retreive the entity
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->getParamOrNull($name, 'repository'))->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ' . $name . ' entity.');
        }

        // Create the form
        $editForm = $this->createForm(new AdminType($this->getParamOrNull($name, 'formDisplay')), $entity);

        // Bind the form with the request
        $request = $this->getRequest();
        $editForm->bindRequest($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this->get('session')->setFlash('success', $name . '.edit.success');

            return $this->redirect($this->generateUrl('ObCmsBundle_module_edit', array('name' => $name, 'id' => $id)));
        }

        $template = $this->getParamOrNull($name, 'editTemplate');
        $template = $template ? : 'ObCmsBundle:Edit:edit.html.twig';

        return $this->render($template, array(
            'module'    => $name,
            'entity'    => $entity,
            'edit_form' => $editForm->createView(),
        ));
    }


    /**
     * Get the value of a param for a certain module or return null
     *
     * @param string $name
     * @param string $param
     *
     * @return null
     */
    private function getParamOrNull($name, $param)
    {
        $params = $this->container->getParameter('bundles');

        return isset($params[$name][$param]) ? $params[$name][$param] : null;
    }


    /**
     * Get the value of a param for a certain module or return an empty array
     *
     * @param string $name
     * @param string $param
     *
     * @return array
     */
    private function getParamOrArray($name, $param)
    {
        return $this->getParamOrNull($name, $param) ? : array();
    }


    /**
     * Forwards the action to another controller if the param is set
     *
     * @param string $name
     * @param string $controller
     *
     * @return Response
     */
    private function executeForward($name, $controller)
    {
        $forward = $this->getParamOrNull($name, $controller);

        if (isset($forward)) {
            return $this->forward($forward);
        }
    }


    /**
     * Executes an action on selected table rows
     *
     * @param string $name
     */
    private function executeAction($name)
    {
        $request = $this->get('request');

        if ($request->getMethod() == 'POST') {
            $action = $request->request->get('action');
            $ids = $request->request->get('action-checkbox')?:array();
            $ids = array_keys($ids);

            if(!empty($ids) and $action != '') {
                $em = $this->getDoctrine()->getManager();
                $entities = $em->getRepository($this->getParamOrNull($name, 'repository'))->findById($ids);

                foreach($entities as $entity) {
                    // TODO: check if function exists or raise Exception
                    if($action == 'delete-action') {
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
     * @param string $name
     */
    private function getEntities($name) {
        // Start to build the query
        $repository = $this->getDoctrine()->getRepository($this->getParamOrNull($name, 'repository'));
        $query = $repository->createQueryBuilder('o');

        // Search query and list of search fields
        $search = $this->get('request')->query->get('search') ? : null;
        $listSearch = $this->getParamOrArray($name, 'listSearch');

        // If there is a search query, build the where clause for every searchFields
        if(count($listSearch) > 0 && $search) {
            foreach($listSearch as $k => $field) {
                if($k == 0) {
                    $query->where($query->expr()->like("o.$field", "?$k"));
                } else {
                    $query->orWhere($query->expr()->like("o.$field", "?$k"));
                }
                $listSearch[$k] = '%' .$search . '%';
            }
            $query->setParameters($listSearch);
        }

        // Paginate the result
        $paginator = $this->get('knp_paginator');
        $entities = $paginator->paginate(
            $query,
            $this->get('request')->query->get('p', 1),
            $this->getParamOrNull($name, 'itemsPage') ? : $this->container->getParameter('itemsPage')
        );

        return $entities;
    }

}