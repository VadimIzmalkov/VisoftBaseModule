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
            // if(static::CREATE_SUCCESS_MESSAGE !== null)
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
		$this->entity = $this->getEntity(); // here entity can be overrided
		$this->editForm = $this->getEditForm(); // here form can be overrided
		if($this->getRequest()->isPost()) {
			$this->post = array_merge_recursive(
                $this->request->getPost()->toArray(),           
                $this->request->getFiles()->toArray()
            );
            $images = $this->params()->fromFiles();
            $this->editForm->bind($this->entity);
            $this->editForm->setData($this->post);
            if($this->editForm->isValid()) {
            	$data = $this->editForm->getData();
            	// var_dump($images);
            	// var_dump(empty($images));
            	// die('FFF');
            	if(!empty($images))
            		$this->saveImages($images);
            	$this->entityManager->persist($this->entity);
	            $this->entityManager->flush();
	            // if(static::CREATE_SUCCESS_MESSAGE !== null)
	            $this->flashMessenger()->addSuccessMessage(static::EDIT_SUCCESS_MESSAGE);
	            $this->redirectAfterEdit();
            }
		} else {
			// var_dump($this->editForm);
			// var_dump($this->entity);
			// die('ff');
			$this->editForm->bind($this->entity);
			$this->bindExtra();
		}
		$viewModel = $this->getViewModel([
			'form' => $this->editForm,
			'entity' => $this->entity,
			'thisAction' => 'edit',
			'pageTitle' => static::EDIT_PAGE_TITLE,
		]);
		// $this->returnViewModel([
		// 	'form' => $this->editForm,
		// 	'entity' => $this->entity,
		// 	'thisAction' => 'edit',
		// ]);
		// $viewModel = new ViewModel();
		// if(isset($this->templates['edit']))
		// 	$viewModel->setTemplate($this->templates['edit']);
		// if(isset($this->layouts['edit']))
		// 	$this->layout($this->layouts['edit']);
		// $viewModel->setVariables([
		// 	'form' => $this->editForm,
		// 	'entity' => $this->entity,
		// 	'thisAction' => 'edit',
		// 	'pageTitle' => static::EDIT_PAGE_TITLE,
		// ]);
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
    	$targetDir = $this->uploadPath . '/'. $this->entity->getId() . '/';
    	$this->checkDir($targetDir);

    	// receiver of the upload
    	// die('ff');
    	$receiver = new \Zend\File\Transfer\Adapter\Http();
    	// die('ff');
    	$receiver->setDestination($targetDir);

    	foreach ($images as $element => $image) {
    		// find index for element number
    		preg_match_all('!\d+!', $element, $matches);
    		$indx = (int)implode('', $matches[0]);

    		// cropping coordinates
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

    protected function bindExtra() 
    {

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
