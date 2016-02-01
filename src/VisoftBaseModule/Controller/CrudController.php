<?php 

namespace VisoftBaseModule\Controller;

use Zend\View\Model\ViewModel;

use Doctrine\ORM\EntityManager;

abstract class CrudController extends BaseController
{
    const EDIT_SUCCESS_MESSAGE = "Entity successfully updated";
	/**
     * @var Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    public function __construct(EntityManager $entityManager) 
    {
    	$this->entityManager = $entityManager;
    }

    public function createAction()
    {
        $paramsRoute = $this->params()->fromRoute();
        $form = $this->createForm;
        $form->setAttributes(['action' => $this->request->getRequestUri()]);
        $entityClass = static::ENTITY_CLASS;
        $entity = new $entityClass();
        $form->bind($entity);
        if($this->request->isPost()) {
            $post = array_merge_recursive(
                $this->request->getPost()->toArray(),           
                $this->request->getFiles()->toArray()
            );
            $files = $this->params()->fromFiles();
            if(is_null($this->createInputFilter))
                $this->setCreateInputFilter($form, $post);
            else
                $form->setInputFilter($this->createInputFilter);
            $form->setData($post);
            if($form->isValid()) {
                $data = $form->getData(); 
                $entity->setCreatedBy($this->identity());
                $this->entityManager->persist($entity);
                $this->entityManager->flush();
                $fileNames = null;
                if(!empty($files)) {
                    $targetDir = static::UPLOAD_PATH . '/'. $entity->getId() . '/';
                    $this->checkDir($targetDir);
                    $adapter = new \Zend\File\Transfer\Adapter\Http();
                    $adapter->setDestination($targetDir);
                    foreach ($files as $element => $file) {
                        $fileInfo = pathinfo($post[$element]['name']);
                        $adapter->setFilters([
                            new \Zend\Filter\File\Rename([
                                "target" => $targetDir . 'upload_' . '.' . $fileInfo['extension'],
                                "randomize" => true,
                            ])
                        ]);
                        if ($adapter->receive($element))
                            $fileNames[$element] = $adapter->getFileName($element);
                    }
                }
                $this->setExtraData($entity, $post, $paramsRoute, $fileNames);
                $this->entityManager->persist($entity);
                $this->entityManager->flush();
                if(static::CREATE_SUCCESS_MESSAGE !== null)
                    $this->flashMessenger()->addSuccessMessage(static::CREATE_SUCCESS_MESSAGE);
                $this->redirectAfterCreate($this->request, $entity);
            }
        }
        $this->setLayout($this->identity()->getRole());
    	$viewModel = new ViewModel();
    	$viewModel->setVariables([
    		'form' => $form,
    	]);
    	return $viewModel;
    }

    public function editAction()
    {
        $paramsRoute = $this->params()->fromRoute();
        $entityId = null;
        if(isset($paramsRoute['entityId']))
            $entityId = $paramsRoute['entityId'];
        $entity = $this->getEntity($entityId);
        if(is_null($entity))
            return $this->notFoundAction();
        $form = $this->setEditForm($this->identity()->getRole());
        $request = $this->getRequest();
        if($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );
            $files = $this->params()->fromFiles();
            if (isset($this->editInputFilter)) {
                if(is_null($this->editInputFilter))
                    $this->setEditInputFilter($form, $post);
                else
                    $form->setInputFilter($this->editInputFilter);
            }
            $form->bind($entity);
            $form->setData($post);
            if ($form->isValid()) {
                $data = $form->getData();
                if(!empty($files)) {
                    $targetDir = static::UPLOAD_PATH . '/'. $entity->getId() . '/';
                    $this->checkDir($targetDir);
                    $adapter = new \Zend\File\Transfer\Adapter\Http();
                    $adapter->setDestination($targetDir);
                    foreach ($files as $element => $file) {
                        $pictureNames[$element] = '';
                        if(empty($post[$element]['name']))
                            continue;
                        $fileInfo = pathinfo($post[$element]['name']);
                        $adapter->setFilters([
                            new \Zend\Filter\File\Rename([
                                "target" => $targetDir . 'img' . '.' . $fileInfo['extension'],
                                "randomize" => true,
                            ])
                        ]);
                        if ($adapter->receive($element))
                            $pictureNames[$element] = $adapter->getFileName($element);
                    }
                }
                if(!isset($pictureNames)) $pictureNames = null;
                $this->setExtraData($entity, $post, $paramsRoute, $pictureNames);
                $this->entityManager->persist($entity);
                $this->entityManager->flush();
                if(static::EDIT_SUCCESS_MESSAGE !== null)
                    $this->flashMessenger()->addSuccessMessage(static::EDIT_SUCCESS_MESSAGE);
                $this->redirectAfterEdit($request, $entity);
            }
        }

        $form->bind($entity);
        $this->bindExtraData($form, $entity);
        $viewModel = new ViewModel();
        $viewModel->setVariables([
            'form' => $form,
            'entity' => $entity,
        ]);
        $this->setLayout($this->identity()->getRole());
        // if(defined(static::LAYOUT))
        //     $this->layout(static::LAYOUT);
        // $this->layout('layout/admin');
        return $viewModel;
    }

    public function viewAction()
    {
        $paramsRoute = $this->params()->fromRoute();
        $id = $paramsRoute['entityId'];
        // if($id === null)
        //     return $this->redirect()->toRoute('administrator/default', array('controller' => $this->controllerName));
        $entity = $this->entityManager->getRepository(static::ENTITY_CLASS)->findOneBy(['id' => $id]);
        // if(is_null($entity))
        //     return $this->redirect()->toRoute('administrator/default', array('controller' => $this->controllerName));
        $viewModel = new ViewModel();
        $viewModel->setVariables([
            'entity' => $entity,
        ]);
        $this->layout('layout/admin');
        return $viewModel;
    }

    public function deleteAction()
    {
        $request = $this->getRequest();
        $paramsRoute = $this->params()->fromRoute();
        $entityId = $paramsRoute['entityId'];
        $entity = $this->entityManager->getRepository(static::ENTITY_CLASS)->findOneBy(['id' => $entityId]);
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
        $this->flashMessenger()->addSuccessMessage('Entity successfully deleted');
        $this->redirectAfterDelete();    
    }

    protected function getEntity($entityId)
    {
        return $this->entityManager->getRepository(static::ENTITY_CLASS)->findOneBy(['id' => $entityId]);
    }

    public function setEditForm($role)
    {
        $form = $this->editForm;
        $form->setAttributes(['action' => $this->getRequest()->getRequestUri()]);
        return $form;
    }

    public function setLayout($role)
    {
        if(defined('static::LAYOUT'))
            $this->layout(static::LAYOUT);
    }

    protected function redirectAfterCreate($request, $entity) 
    {
        $routeMatch = $this->getEvent()->getRouteMatch();
        $route = $routeMatch->getMatchedRouteName();
        return $this->redirect()->toRoute($route);
    }

    protected function redirectAfterEdit($request) 
    {
        $route = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        $parameters = $this->getEvent()->getRouteMatch()->getParams();
        // var_dump($route);
        // var_dump($parameters);
        // die('kk');
        return $this->redirect()->toRoute($route, $parameters);
    }

    protected function redirectAfterDelete($request) 
    {
        $refererUri = $request->getHeader('referer')->getUri();
        return $this->redirect()->toUrl($refererUri);
    }
    
    protected function setExtraData(&$entity, $post, $paramsRoute, $pictureName) {}
    protected function setCreateInputFilter(&$form, $post) {}
    protected function setEditInputFilter(&$form, $post) {}
    protected function bindExtraData(&$form, $entity) {}
}