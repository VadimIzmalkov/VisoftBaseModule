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

    abstract function getEntity($entityId);
    abstract function composeCreateRedirect($entity, $paramsRoute);
    abstract function composeDeleteRedirect($entity, $paramsRoute);
    abstract function composeEditRedirect($entity, $paramsRoute);
    abstract function setDependencyData(&$entity, $post, $paramsRoute, $pictureName = null);
    abstract function setDependencyFilter(&$form, $post);
    abstract function bindDependencyData(&$form, $entity);

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
                if(!isset($pictureNames)) $pictureNames = null;
                $this->setDependencyData($entity, $post, $paramsRoute, $pictureNames);
                $this->entityManager->persist($entity);
                $this->entityManager->flush();
                $redirect = $this->composeCreateRedirect($entity, $paramsRoute);
                return $this->redirect()->toRoute($redirect['route'], $redirect['params']);
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
                $this->setDependencyData($entity, $post, $paramsRoute, $pictureNames);
                $this->entityManager->persist($entity);
                $this->entityManager->flush();
                $redirect = $this->composeEditRedirect($entity, $paramsRoute);
                $this->flashMessenger()->addSuccessMessage('Changes successfully saved!');
                return $this->redirect()->toRoute($redirect['route'], $redirect['params']);
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
        $paramsRoute = $this->params()->fromRoute();
        $id = $paramsRoute['entityId'];
        // if($id === null)
        //     return $this->redirect()->toRoute('administrator/default', array('controller' => $this->controllerName));
        $entity = $this->entityManager->getRepository(static::ENTITY_CLASS)->findOneBy(['id' => $id]);
        // if(is_null($entity))
        //     return $this->redirect()->toRoute('administrator/default', array('controller' => $this->controllerName));
        $dir = static::UPLOAD_PATH . '/'. $entity->getId() . '/';
        if(is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
        $redirect = $this->composeCreateRedirect($entity, $paramsRoute);
        return $this->redirect()->toRoute($redirect['route'], $redirect['params']);      
    }
}