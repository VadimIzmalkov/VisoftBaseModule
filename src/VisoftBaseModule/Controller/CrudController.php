<?php 

namespace VisoftBaseModule\Controller;

use Zend\View\Model\ViewModel;

use Doctrine\ORM\EntityManager;

abstract class CrudController extends BaseController
{
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
        $request = $this->getRequest();
        $paramsRoute = $this->params()->fromRoute();
        $form = $this->createForm;
        $form->setAttributes(['action' => $request->getRequestUri()]);
        $entityClass = static::ENTITY_CLASS;
        $entity = new $entityClass();
        $form->bind($entity);
        if($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),           
                $request->getFiles()->toArray()
            );
            $files = $this->params()->fromFiles();
            $this->setDependencyFilter($form, $post);
            $form->setData($post);
            if($form->isValid()) {
                $data = $form->getData(); 
                $entity->setCreatedBy($this->identity());
                $this->entityManager->persist($entity);
                $this->entityManager->flush();
                if(!empty($files)) {
                    $targetDir = static::UPLOAD_PATH . '/'. $entity->getId() . '/';
                    $this->checkDir($targetDir);
                    $adapter = new \Zend\File\Transfer\Adapter\Http();
                    $adapter->setDestination($targetDir);
                    foreach ($files as $element => $file) {
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
                $this->redirectAfterCreate($request, $entity);  
                // $redirect = $this->composeCreateRedirect($entity, $paramsRoute);
                // return $this->redirect()->toRoute($redirect['route'], $redirect['params']);
            }
        }
    	$viewModel = new ViewModel();
    	$viewModel->setVariables([
    		'form' => $form,
    	]);
    	$this->layout('layout/admin');
    	return $viewModel;//->setTemplate(static::VIEW_CREATE);
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
        $form = $this->editForm;
        $form->setAttributes(['action' => $this->getRequest()->getRequestUri()]);
        $request = $this->getRequest();
        if($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );
            $files = $this->params()->fromFiles();
            $this->setDependencyFilter($form, $post);
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
                $redirect = $this->composeEditRedirect($entity, $paramsRoute);
                $this->flashMessenger()->addSuccessMessage('Changes successfully saved!');
                $this->redirectAfterEdit($request, $entity);  
                // return $this->redirect()->toRoute($redirect['route'], $redirect['params']);
            }
        }
        $form->bind($entity);
        $this->bindDependencyData($form, $entity);
        $viewModel = new ViewModel();
        $viewModel->setVariables([
            'form' => $form,
            'entity' => $entity,
        ]);
        $this->layout('layout/admin');
        return $viewModel;
    }

    public function viewAction()
    {
        $paramsRoute = $this->params()->fromRoute();
        $id = $paramsRoute['entityId'];
        // if($id === null)
        //     return $this->redirect()->toRoute('administrator/default', array('controller' => $this->controllerName));
        // var_dump($id);
        $entity = $this->entityManager->getRepository(static::ENTITY_CLASS)->findOneBy(['id' => $id]);
        // var_dump($entity);
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

    protected function redirectAfterCreate($request, $entity) 
    {
        $routeMatch = $this->getEvent()->getRouteMatch();
        $route = $routeMatch->getMatchedRouteName();
        $params = $routeMatch->getParams();
        return $this->redirect()->toRoute($route);
    }

    protected function redirectAfterEdit($request) 
    {
        $route = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        $parameters = $this->getEvent()->getRouteMatch()->getParams();
        return $this->redirect()->toRoute($route, $parameters);
    }

    protected function redirectAfterDelete($request) 
    {
        $refererUri = $request->getHeader('referer')->getUri();
        return $this->redirect()->toUrl($refererUri);
    }
    
    protected function composeEditRedirect($entity, $paramsRoute) {}
    protected function setExtraData(&$entity, $post, $paramsRoute, $pictureName = null) {}
    protected function setDependencyFilter(&$form, $post) {}
    protected function bindDependencyData(&$form, $entity) {}
}