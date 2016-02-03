<?php 

namespace VisoftBaseModule\Controller;

use Zend\Mvc\Controller\AbstractActionController,
	Zend\View\Model\ViewModel;

abstract class AbstractCrudController extends AbstractActionController
{
	const CREATE_SUCCESS_MESSAGE = 'Entity successfully created';
	const EDIT_SUCCESS_MESSAGE = 'Entity successfully updated';

	protected $entityManager;
	protected $entityClass;
	protected $entity;
	protected $entityRepository;
	protected $layout = null;
	protected $templates = null;
	protected $uploadPath = null;
	protected $createForm;
	protected $editForm;
	
	protected $post;

	//services
	protected $authenticationService = null;
	/**
     * @var WebinoImageThumb\WebinoImageThumb
     */
	protected $thumbnailer;

	public function __construct($entityManager, $entityClass)
	{
		$this->entityClass = $entityClass;
		$this->entityManager = $entityManager;
		$this->entityRepository = $this->entityManager->getRepository($entityClass);
	}

	public function createAction()
	{
		$form = $this->createForm;
		$form->setAttributes(['action' => $this->request->getRequestUri()]);
		$this->entity = new $this->entityClass();
		$form->bind($this->entity);
		if($this->request->isPost()) {
			$this->post = array_merge_recursive(
                $this->request->getPost()->toArray(),           
                $this->request->getFiles()->toArray()
            );
            $images = $this->params()->fromFiles();
            $form->setData($this->post);
            if($form->isValid()) {
            	$data = $form->getData();
            	$this->entity->setCreatedBy($this->identity());
            	$this->entityManager->persist($this->entity);
            	$this->entityManager->flush();
            	if(!empty($images)) {
            		//TODO: saving images;
            		$this->saveImages($images);
            	}
            }
            $this->entityManager->persist($this->entity);
            $this->entityManager->flush();
            // if(static::CREATE_SUCCESS_MESSAGE !== null)
            $this->flashMessenger()->addSuccessMessage(static::CREATE_SUCCESS_MESSAGE);
            $this->redirectAfterCreate();
		}
		$viewModel = new ViewModel();
		if(!is_null($this->templates['create']))
			$viewModel->setTemplate($this->templates['create']);
		if(!is_null($this->layout))
			$this->layout($this->layout);
		$viewModel->setVariables([
			'form' => $form,
			'thisAction' => 'create',
		]);
		return $viewModel;
	}

	public function editAction()
	{
		$this->entity = $this->getEntity();
		$form = $this->editForm;
		$form->setAttributes(['action' => $this->request->getRequestUri()]);
		if($this->getRequest()->isPost()) {
			$this->post = array_merge_recursive(
                $this->request->getPost()->toArray(),           
                $this->request->getFiles()->toArray()
            );
            $images = $this->params()->fromFiles();
            $form->bind($this->entity);
            $form->setData($this->post);
            if($form->isValid()) {
            	$data = $form->getData();
            	if(!empty($images)) {
            		//TODO: saving images;
            		$this->saveImages($images);
            	}
            }
            $this->entityManager->persist($this->entity);
            $this->entityManager->flush();
            // if(static::CREATE_SUCCESS_MESSAGE !== null)
            $this->flashMessenger()->addSuccessMessage(static::EDIT_SUCCESS_MESSAGE);
            $this->redirectAfterEdit();
		} else {
			$form->bind($this->entity);
			$this->bindExtra();
		}
		$viewModel = new ViewModel();
		if(!is_null($this->templates['edit']))
			$viewModel->setTemplate($this->templates['edit']);
		if(!is_null($this->layout))
			$this->layout($this->layout);
		$viewModel->setVariables([
			'form' => $form,
			'entity' => $this->entity,
			'thisAction' => 'edit',
		]);
		return $viewModel;
	}

    protected function redirectAfterCreate() 
    {
        $routeMatch = $this->getEvent()->getRouteMatch();
        $route = $routeMatch->getMatchedRouteName();
        return $this->redirect()->toRoute($route);
    }

    protected function redirectAfterEdit() 
    {
        $route = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        $parameters = $this->getEvent()->getRouteMatch()->getParams();
        var_dump($route);
        var_dump($parameters);
        die('kk');
        return $this->redirect()->toRoute($route, $parameters);
    }

    protected function getEntity()
    {
    	$routeParams = $this->params()->fromRoute();
    	// var_dump()
    	$entityId = isset($routeParams['entityId']) ? $routeParams['entityId'] : null;
    	$entity = $this->entityManager->find($this->entityClass, $entityId);
    	return $entity;
    }

    protected function saveImages($images) 
    {
    	// dir for files
    	$targetDir = $this->uploadPath . '/'. $this->entity->getId() . '/';
    	$this->checkDir($targetDir);

    	// receiver of the upload
    	$receiver = new \Zend\File\Transfer\Adapter\Http();
    	$receiver->setDestination($targetDir);

    	foreach ($images as $element => $image) {
    		// find index for element number
    		preg_match_all('!\d+!', $element, $matches);
    		$indx = (int)implode('', $matches[0]);

    		// saving cropping coordinates
    		$xStartCrop = $this->post['xStartCrop' . $indx];
    		$yStartCrop = $this->post['yStartCrop' . $indx];
    		$heightCrop = $this->post['heightCrop' . $indx];
    		$widthCrop = $this->post['widthCrop' . $indx];

    		// set image entity
    		$getImageFunctionName = 'getImg' . $indx;
    		$setImageFunctionName = 'setImg' . $indx;
    		if(empty($this->entity->$getImageFunctionName())) {
    			$image = new \VisoftBaseModule\Entity\Image();
    			$this->entity->$setImageFunctionName($image);
    		} else {
    			$image = $this->entity->$getImageFunctionName();
    		}
    		$image->setXStartCrop($xStartCrop);
    		$image->setYStartCrop($yStartCrop);
    		$image->setHeightCrop($heightCrop);
    		$image->setWidthCrop($widthCrop);
    		$image->setWidthCurrent($this->post['widthCurrent' . $indx]);
    		$image->setHeightCurrent($this->post['heightCurrent' . $indx]);

    		// save coordinates and skip next if image not uploded 
    		if(empty($this->post[$element]['name'])) {
    			$this->entityManager->persist($image);
	        	$this->entity->$setImageFunctionName($image);
                continue;
    		}
    		
    		// transfer file to target dir
    		$imageInfo = pathinfo($this->post[$element]['name']);
    		$receiver->setFilters([
                new \Zend\Filter\File\Rename([
                    "target" => $targetDir . 'image_' . '.' . $imageInfo['extension'],
                    "randomize" => true,
                ]),
            ]);
            $receiver->receive($element);
            $imagePath = $receiver->getFileName($element);

            // get image name
            $explodedImagePath = explode('/', $imagePath);
        	$imageName = end($explodedImagePath); // last is image name
        	$imageNameKey = key($explodedImagePath); // index for image name in array
            
            // create thumb
            $thumb = $this->thumbnailer->create($imagePath, $options = [], $plugins = []);

            // crop image
            $currentDimantions = $thumb->getCurrentDimensions();
            $scale = $currentDimantions['width'] / $this->post['widthCurrent' . $indx];
            $thumb->crop(
	            $xStartCrop * $scale, 
	            $yStartCrop * $scale, 
	            $widthCrop * $scale, 
	            $heightCrop * $scale
	        );

        	// set original image
        	if($image->getOriginalSize() !== null) 
        		unlink('public' . $image->getOriginalSize());
            $image->setOriginalSize(end(explode('public', $imagePath)));
	        
	        // set large 
	        $thumb->resize(960, 960);
	        $newImageName = 'large_' . $imageName;
	        $explodedImagePath[$imageNameKey] = $newImageName;
	        $newImagePath = implode("/", $explodedImagePath);
	        $thumb->save($newImagePath);
	        if($image->getLSize() !== null)
        		unlink('public' . $image->getLSize());
	        $image->setLSize(end(explode('public', $newImagePath)));

	        // set medium
	        $thumb->resize(480, 480);
	        $newImageName = 'medium_' . $imageName;
	        $explodedImagePath[$imageNameKey] = $newImageName;
	        $newImagePath = implode("/", $explodedImagePath);
	        $thumb->save($newImagePath);
	        if($image->getMSize() !== null)
        		unlink('public' . $image->getMSize());
	        $image->setMSize(end(explode('public', $newImagePath)));

	        // set small
	        $thumb->resize(240, 240);
	        $newImageName = 'small_' . $imageName;
	        $explodedImagePath[$imageNameKey] = $newImageName;
	        $newImagePath = implode("/", $explodedImagePath);
	        $thumb->save($newImagePath);
	        if($image->getSSize() !== null)
        		unlink('public' . $image->getSSize());
	        $image->setSSize(end(explode('public', $newImagePath)));

	        // set x-small
	        $thumb->resize(60, 60);
	        $newImageName = 'xsmall_' . $imageName;
	        $explodedImagePath[$imageNameKey] = $newImageName;
	        $newImagePath = implode("/", $explodedImagePath);
	        $thumb->save($newImagePath);
	        if($image->getXsSize() !== null)
        		unlink('public' . $image->getXsSize());
	        $image->setXsSize(end(explode('public', $newImagePath)));

	        // save image
	        $this->entityManager->persist($image);
    	}
    }

	public function setAuthenticationService($authenticationService)
	{
		// TODO: add validation
		$this->authenticationService = $authenticationService;
	}

	public function getAuthenticationService()
	{
		if(is_null($this->authenticationService))
			return $this->getServiceLocator()->get('Zend\Authentication\AuthenticationService');
		else
			return $this->$authenticationService;
	}

	public function setForms($forms)
	{
		$this->createForm = isset($forms['create']) ? $forms['create'] : null;
		$this->editForm = isset($forms['edit']) ? $forms['edit'] : null;
		return $this;
	}

	public function setLayout($layout)
	{
		$this->layout = $layout;
		return $this;
	}

	public function setTemplates(array $templates)
	{
		$this->templates = $templates;
		return $this;
	}

	public function setUploadPath($uploadPath)
	{
		$this->uploadPath = $uploadPath;
		return $this;
	}

	public function setThumbnailer($thumbnailer)
	{
		$this->thumbnailer = $thumbnailer;
		return $this;
	}
}
