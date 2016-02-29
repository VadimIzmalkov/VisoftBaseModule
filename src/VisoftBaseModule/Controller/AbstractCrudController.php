<?php 

namespace VisoftBaseModule\Controller;

use Zend\Mvc\Controller\AbstractActionController,
	Zend\View\Model\ViewModel;

abstract class AbstractCrudController extends AbstractActionController
{
	// page titles
	const CREATE_PAGE_TITLE = 'Create entity';
	const EDIT_PAGE_TITLE = 'Edit entity';
	// messages
	const CREATE_SUCCESS_MESSAGE = 'Entity successfully created';
	const EDIT_SUCCESS_MESSAGE = 'Entity successfully updated';

	protected $entityManager;

	private $entity = null;
	protected $entityClass;
	protected $entityRepository;

	protected $layouts = null;
	protected $templates = null;
	protected $uploadPath = null;
	// forms
	protected $createForm = null;
	protected $editForm = null;
	// view models
	// protected $createViewModel;
	protected $viewModel;
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
		if(is_null($this->createForm))
			throw new \Exception("Create form not defined", 1);
		$this->createForm->setAttributes(['action' => $this->request->getRequestUri()]);
		$this->entity = new $this->entityClass();
		$this->createForm->bind($this->entity);
		if($this->request->isPost()) {
			$this->post = array_merge_recursive(
                $this->request->getPost()->toArray(),           
                $this->request->getFiles()->toArray()
            );
            $images = $this->params()->fromFiles();
            $this->createForm->setData($this->post);
            if($this->createForm->isValid()) {
            	$data = $this->createForm->getData();
            	if(!is_null($this->identity()))
            		$this->entity->setCreatedBy($this->identity());
            	$this->entityManager->persist($this->entity);
            	$this->entityManager->flush();
            	if(!empty($images)) 
            		$this->saveImages($images);
            }
            $this->entityManager->persist($this->entity);
            $this->entityManager->flush();
            $this->flashMessenger()->addSuccessMessage(static::CREATE_SUCCESS_MESSAGE);
            $this->redirectAfterCreate();
		}
		$viewModel = new ViewModel();
		if(isset($this->templates['create']))
			$viewModel->setTemplate($this->templates['create']);
		if(isset($this->layouts['create']))
			$this->layout($this->layouts['create']);
		$viewModel->setVariables([
			'form' => $this->createForm,
			'thisAction' => 'create',
			'pageTitle' => static::CREATE_PAGE_TITLE,
		]);
		return $viewModel;
	}

	public function editAction()
	{
		// needs to clone because after isValid() binded entity looses image object
		$this->entity = clone($this->getEntity());
		// var_dump($this->editForm);
		// die('ss');
		$this->editForm = $this->getEditForm();
		if($this->getRequest()->isPost()) {
			$this->post = array_merge_recursive(
                $this->request->getPost()->toArray(),           
                $this->request->getFiles()->toArray()
            );
            $images = $this->params()->fromFiles();
            $this->editForm->bind($this->getEntity());
            $this->editForm->setData($this->post);
            if($this->editForm->isValid()) {
            	$data = $this->editForm->getData();
            	// can be empty if InpuFile not defined
            	if(!empty($images)) 
            		$this->saveImages($images);
            	$this->entityManager->persist($this->getEntity());
	            $this->entityManager->flush();
	            $this->flashMessenger()->addSuccessMessage(static::EDIT_SUCCESS_MESSAGE);
	            $this->redirectAfterEdit();
            }
		} else {
			$this->editForm->bind($this->entity);
			$this->bindExtra();
		}
		$viewModel = $this->getViewModel([
			'form' => $this->editForm,
			'entity' => $this->entity,
			'thisAction' => 'edit',
			'pageTitle' => static::EDIT_PAGE_TITLE,
		]);
		$this->addEditViewModelVariables($viewModel);
		return $viewModel;
	}

    protected function redirectAfterCreate() 
    {
        $routeMatch = $this->getEvent()->getRouteMatch();
        $route = $routeMatch->getMatchedRouteName();
        $parameters['controller'] = $routeMatch->getParam('__CONTROLLER__');
        $parameters['action'] = 'index';
        return $this->redirect()->toRoute($route, $parameters);
    }

    protected function redirectAfterEdit() 
    {
    	$routeMatch = $this->getEvent()->getRouteMatch();
        $route = $routeMatch->getMatchedRouteName();
        $parameters = $routeMatch->getParams();
        $parameters['controller'] = $routeMatch->getParam('__CONTROLLER__');
        $parameters['action'] = $routeMatch->getParam('action');
        $parameters['entityId'] = $this->getEntity()->getId();
        return $this->redirect()->toRoute($route, $parameters);
    }

    protected function getEntity()
    {
    	$routeParams = $this->params()->fromRoute();
    	$entityId = isset($routeParams['entityId']) ? $routeParams['entityId'] : null;
    	$entity = $this->entityManager->find($this->entityClass, $entityId);
    	return $entity;
    }

    // overrides form if one depends on route, but not depends on action
    protected function getEditForm()
    {
    	$this->editForm->setAttributes(['action' => $this->request->getRequestUri()]);
    	return $this->editForm;
    }

    protected function getViewModel(array $variables = null)
    {
    	$routeMatch = $this->getEvent()->getRouteMatch();
    	$action = $routeMatch->getParam('action');
		$this->viewModel = new ViewModel();
		if(isset($this->templates[$action]))
			$this->viewModel->setTemplate($this->templates[$action]);
		if(isset($this->layouts[$action]))
			$this->layout($this->layouts[$action]);
		if(!is_null($variables))
			$this->viewModel->setVariables($variables);
		return $this->viewModel;
    }

    protected function addEditViewModelVariables(&$viewModel) { }

    protected function saveImages($images) 
    {
    	// dir for files
    	$targetDir = $this->uploadPath . '/'. $this->getEntity()->getId() . '/';
    	$this->checkDir($targetDir);

    	// receiver of the upload
    	$receiver = new \Zend\File\Transfer\Adapter\Http();
    	$receiver->setDestination($targetDir);

    	foreach ($images as $element => $image) {
    		// find indx for element number
    		preg_match_all('!\d+!', $element, $matches);
    		$indx = implode('', $matches[0]); // last character is number - $image1
    		// saving data to image entity
    		$getImageFunctionName = 'getImage' . $indx;
    		$setImageFunctionName = 'setImage' . $indx;
    		// get image from original entity
    		$image = $this->entity->$getImageFunctionName();
    		if(empty($image)) 
    			// if image not set (null) - create new entity for image
    			$image = new \VisoftBaseModule\Entity\Image();

    		// get image path 
    		if(!empty($this->post[$element]['name'])) {
    			// if image uploaded - transfer one and get new file path
	    		$imageInfo = pathinfo($this->post[$element]['name']);
	    		$receiver->setFilters([
	                new \Zend\Filter\File\Rename([
	                    "target" => $targetDir . 'image_' . '.' . $imageInfo['extension'],
	                    "randomize" => true,
	                ]),
	            ]);
	            $receiver->receive($element);
	            $imagePath = $receiver->getFileName($element);
    		} else {
    			// if image not uploaded check if image has been uploaded before
    			if(empty($image->getOriginalSize()))
    				// stop saving image because image not uploaded and was has been uploaded before
    				continue;
    			// image has been uploaded before and continue saving
    			$imagePath = 'public' . $image->getOriginalSize();
    		}

    		// cropping coordinates
    		$xStartCrop = $this->post['xStartCrop' . $indx];
    		$yStartCrop = $this->post['yStartCrop' . $indx];
    		$heightCrop = $this->post['heightCrop' . $indx];
    		$widthCrop = $this->post['widthCrop' . $indx];

    		// save coordinates
    		$image->setXStartCrop($xStartCrop);
    		$image->setYStartCrop($yStartCrop);
    		$image->setHeightCrop($heightCrop);
    		$image->setWidthCrop($widthCrop);
    		$image->setWidthCurrent($this->post['widthCurrent' . $indx]);
    		$image->setHeightCurrent($this->post['heightCurrent' . $indx]);

    		// save coordinates and skip next if image not uploded 
    		// if(!empty($this->post[$element]['name'])) {
    		// 	// $this->entityManager->persist($image);
		    //   	// $this->entity->$setImageFunctionName($image);
	     //   		// 		continue;
    		// }

            // get image name
            $explodedImagePath = explode('/', $imagePath);
        	$imageName = end($explodedImagePath); // last is image name
        	$imageNameKey = key($explodedImagePath); // key for image name value in array
            
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
        		if(file_exists($image->getOriginalSize()))
        			unlink('public' . $image->getOriginalSize());
            $image->setOriginalSize(end(explode('public', $imagePath)));
	        
	        // set large 
	        $thumb->resize(960, 960);
	        $newImageName = 'large_' . $imageName;
	        $explodedImagePath[$imageNameKey] = $newImageName;
	        $newImagePath = implode("/", $explodedImagePath);
	        $thumb->save($newImagePath);
	        if($image->getLSize() !== null)
	        	if(file_exists($image->getLSize()))
        			unlink('public' . $image->getLSize());
	        $image->setLSize(end(explode('public', $newImagePath)));

	        // set medium
	        $thumb->resize(480, 480);
	        $newImageName = 'medium_' . $imageName;
	        $explodedImagePath[$imageNameKey] = $newImageName;
	        $newImagePath = implode("/", $explodedImagePath);
	        $thumb->save($newImagePath);
	        if($image->getMSize() !== null)
	        	if(file_exists($image->getMSize()))
        			unlink('public' . $image->getMSize());
	        $image->setMSize(end(explode('public', $newImagePath)));

	        // set small
	        $thumb->resize(240, 240);
	        $newImageName = 'small_' . $imageName;
	        $explodedImagePath[$imageNameKey] = $newImageName;
	        $newImagePath = implode("/", $explodedImagePath);
	        $thumb->save($newImagePath);
	        if($image->getSSize() !== null)
	        	if(file_exists($image->getSSize()))
        			unlink('public' . $image->getSSize());
	        $image->setSSize(end(explode('public', $newImagePath)));

	        // set x-small
	        $thumb->resize(60, 60);
	        $newImageName = 'xsmall_' . $imageName;
	        $explodedImagePath[$imageNameKey] = $newImageName;
	        $newImagePath = implode("/", $explodedImagePath);
	        $thumb->save($newImagePath);
	        if($image->getXsSize() !== null)
	        	if(file_exists($image->getXsSize()))
        			unlink('public' . $image->getXsSize());
	        $image->setXsSize(end(explode('public', $newImagePath)));

	       	// image ready
	        $this->getEntity()->$setImageFunctionName($image);

	        // save image
	        $this->entityManager->persist($image);
    	}
    }

    protected function bindExtra() 
    {
		// for ($indx = 1; $indx < 5; $indx++) { 
    		// $getImageFunctionName = 'getImg';
    		$image = $this->getEntity()->getImage();
    		// var_dump($image);
    		// die('dd');
    		if(!empty($image)) {
    			$this->editForm->get('xStartCrop')->setValue($image->getXStartCrop());
	    		$this->editForm->get('yStartCrop')->setValue($image->getYStartCrop());
	    		$this->editForm->get('heightCrop')->setValue($image->getHeightCrop());
	    		$this->editForm->get('widthCrop')->setValue($image->getWidthCrop());
	    		$this->editForm->get('heightCurrent')->setValue($image->getHeightCurrent());
	    		$this->editForm->get('widthCurrent')->setValue($image->getWidthCurrent());
    		}
    		
    	// }
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

	public function setLayouts($layouts)
	{
		$this->layouts = $layouts;
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
