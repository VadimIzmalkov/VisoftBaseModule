<?php 

namespace VisoftBaseModule\Controller;

use Zend\Mvc\Controller\AbstractActionController,
	Zend\View\Model\ViewModel;

abstract class AbstractCrudController extends AbstractActionController
{
	// page titles. Can be overrided in child class
	const CREATE_PAGE_TITLE = 'Create entity';
	const EDIT_PAGE_TITLE = 'Edit entity';
	
	// messages. Can be overrided in child class
	const CREATE_SUCCESS_MESSAGE = 'Entity successfully created';
	const EDIT_SUCCESS_MESSAGE = 'Entity successfully updated';

	protected $entityManager;
	
	protected $entity = null;
	protected $entityClass = null;
	protected $entityRepository = null;

	protected $layouts = null;
	protected $templates = null;
	protected $uploadPath = null;
	protected $imageStorage = null;
	
	protected $viewModel = null;
	protected $post;
	
	// forms
	protected $createForm = null;
	protected $editForm = null;
	
	// inputFilters
	protected $createInputFilter = null;
	protected $editInputFilter = null;

	//services
    protected $crudService;
	protected $authenticationService = null;
	protected $slugService = null;
	protected $thumbnailer;

	public function __construct($entityManager, $entityClass)
	{
        $this->entityManager = $entityManager;

        if(!is_null($entityClass)) {
            $this->entityClass = $entityClass;
            $this->entityRepository = $this->entityManager->getRepository($entityClass);
        }
	}

	public function createAction()
	{
        // entity
        $this->entity = $this->getEntity();

        // get form for create
        $this->createForm = $this->getCreateForm();
        // action for form should be same as current action
        $this->createForm->setAttributes(['action' => $this->request->getRequestUri()]);
		$this->createForm->bind($this->entity);

        // start POST
		if($this->request->isPost()) {
			$this->post = array_merge_recursive(
                $this->request->getPost()->toArray(),           
                $this->request->getFiles()->toArray()
            );
            $images = $this->params()->fromFiles();
            $this->setCreateInputFilter();
            $this->createForm->setData($this->post);
            if($this->createForm->isValid()) {
                // die('Abstract CRUD controller. Form errors');
            	$data = $this->createForm->getData();
            	if(!is_null($this->identity()))
            		$this->entity->setCreatedBy($this->identity());
            	$this->entityManager->persist($this->entity);
            	$this->entityManager->flush();
            	if(!empty($images)) 
            		$this->saveFiles($images);
            	$this->setExtra();
            	$this->entityManager->persist($this->entity);
	            $this->entityManager->flush();
	            $this->flashMessenger()->addSuccessMessage(static::CREATE_SUCCESS_MESSAGE);
                $this->toggleCreateActivity();
	            return $this->redirectAfterCreate();
            }
            // dump the form, find an errors
            // $errorMessages = $this->createForm->getMessages();
            // var_dump($errorMessages);
            // die('Abstract CRUD controller. Form errors');
		}
        $viewModel = $this->getViewModel([
            'form' => $this->createForm,
            'thisAction' => 'create',
            'pageTitle' => static::CREATE_PAGE_TITLE,
        ]);
		$this->addCreateViewModelVariables($viewModel);
		return $viewModel;
	}

	public function editAction()
	{
        // check additional permission 
        // examples: member can edit only his own companies, blog posts
        $this->checkPermissions();

        // getEntity() can be overrided and used to generate entity (if one not exists yet)
		$this->entity = $this->getEntity();

        // if form depends on some parameter (exp. entity) edit form can be custom 
		$this->editForm = $this->getEditForm();

        // start POST
		if($this->getRequest()->isPost()) {
			$this->post = array_merge_recursive(
                $this->request->getPost()->toArray(),           
                $this->request->getFiles()->toArray()
            );
            $files = $this->params()->fromFiles();
            $this->setEditInputFilter();
            // here can be binding issue
            // for "title image" the names of upload element and entity field should be different 
            // TODO: generate exeption before binding
            $this->editForm->bind($this->entity);
            $this->editForm->setData($this->post);
            if($this->editForm->isValid()) {
            	$data = $this->editForm->getData();
            	// empty if files has not been uploaded
            	if(!empty($files)) 
            		$this->saveFiles($files);
            	$this->setExtra();
            	$this->entityManager->persist($this->entity);
	            $this->entityManager->flush();
	            $this->flashMessenger()->addSuccessMessage(static::EDIT_SUCCESS_MESSAGE);
                $this->toggleEditActivity();
	            return $this->redirectAfterEdit();
            }
		} 

        // request not POST
        // bind entity to form
		$this->editForm->bind($this->entity);
        // bind title image coordinates
        if($this->imageStorage === 'object')
            $this->bindImageTitleCoordinates();
		$this->bindExtra();
        
		$this->viewModel = $this->getViewModel([
			'form' => $this->editForm,
			'entity' => $this->entity,
			'thisAction' => 'edit',
			'pageTitle' => static::EDIT_PAGE_TITLE,
		]);

		$this->addEditViewModelVariables();
		return $this->viewModel;
	}

    public function deleteAction()
    {
        $this->entity = $this->getEntity();
        // $paramsRoute = $this->params()->fromRoute();
        // $id = $paramsRoute['entityId'];
        // if($id === null)
        //     return $this->redirect()->toRoute('administrator/default', array('controller' => $this->controllerName));
        // $entity = $this->entityManager->getRepository(static::ENTITY_CLASS)->findOneBy(['id' => $id]);
        // if(is_null($entity))
        //     return $this->redirect()->toRoute('administrator/default', array('controller' => $this->controllerName));
        // $dir = static::UPLOAD_PATH . '/'. $entity->getId() . '/';
        $entityDir = $this->uploadPath . '/'. $this->entity->getId() . '/';
        if(is_dir($entityDir)) {
            $objects = scandir($entityDir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($entityDir . '/' . $object) == "dir") 
                        rmdir($dir. '/' . $object); 
                    else 
                        unlink($dir. '/' . $object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
        $this->entityManager->remove($this->entity);
        $this->entityManager->flush();
        return $this->redirectAfterDelete();
        // $redirect = $this->composeCreateRedirect($entity, $paramsRoute);
        // return $this->redirect()->toRoute($redirect['route'], $redirect['params']); 
    }



	protected function setEditInputFilter()
	{
		if(isset($this->editInputFilter))
			$this->editForm->setInputFilter($this->editInputFilter);
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


    protected function redirectAfterDelete() 
    {
        $routeMatch = $this->getEvent()->getRouteMatch();
        $route = $routeMatch->getMatchedRouteName();
        $parameters['controller'] = $routeMatch->getParam('__CONTROLLER__');
        $parameters['action'] = 'index';
        return $this->redirect()->toRoute($route, $parameters);
    }

    protected function checkPermissions()
    {
        
    }

    protected function getEntity()
    {
    	$routeParams = $this->params()->fromRoute();
        if(isset($routeParams['entityId'])) {
            // ID from route
            $entityId = $routeParams['entityId'];
            $entity = $this->entityManager->find($this->getEntityClassName(), $entityId);
        } elseif(is_null($this->entity)) {
            // create entity
            $entityClassName = $this->getEntityClassName();
            $entity = new $entityClassName();
        } else {
            // just return exists entity
            $entity = $this->entity; 
        }
    	return $entity;
    }

    // override this method if name of the class can be depend
    protected function getEntityClassName()
    {
        if($this->entityClass === null)
            throw new \Exception("Entity class name not defined in \"crud_controller\" specification (\"module.config.php\"). Define the class name or override method \"getEntityClassName()\" for depends entity", 1);
        return $this->entityClass;   
    }

    // override this method if form depends on parameter (entity, layout etc.)
    protected function getCreateForm()
    {
        // check if form is defined in "crud_controller" config (module.config.php). 
        // form depends on the action and the user role
        // parameter that select actions is defined in "crud_controller" config
        // For each of the roles can be configured special type of the form (3d parameter of the form constructor - $identity)
        // Objet creater - "AbstractCrudControllerFactory"
        if(is_null($this->createForm))
            throw new \Exception("Create form name not defined in \"crud_controller\" specification (\"module.config.php\"). Define the class name or override method \"getEntityClassName()\" for depends entity", 1);
        // $this->editForm->setAttributes(['action' => $this->request->getRequestUri()]);
        return $this->createForm;
    }

    protected function setCreateInputFilter()
    {
        if(isset($this->createInputFilter))
            $this->createForm->setInputFilter($this->createInputFilter);
    }

    protected function redirectAfterCreate() 
    {
        $routeMatch = $this->getEvent()->getRouteMatch();
        $route = $routeMatch->getMatchedRouteName();
        $parameters['controller'] = $routeMatch->getParam('__CONTROLLER__');
        $parameters['action'] = 'index';
        return $this->redirect()->toRoute($route, $parameters);
    }

    // override this method if action for the form should be changed
    // or 
    // if form depends on parameters (entity, for example)
    protected function getEditForm()
    {
    	$this->editForm->setAttributes(['action' => $this->request->getRequestUri()]);
    	return $this->editForm;
    }

    protected function getViewModel(array $variables = null)
    {
        if(is_null($this->viewModel)) {
        	$routeMatch = $this->getEvent()->getRouteMatch();
        	$action = $routeMatch->getParam('action');
    		$this->viewModel = new ViewModel();
    		if(isset($this->templates[$action]))
    			$this->viewModel->setTemplate($this->templates[$action]);
            // TODO: move handling layouts to own method 
    		if(isset($this->layouts[$action]))
    			$this->layout($this->layouts[$action]);
        }
        if(!is_null($variables))
            $this->viewModel->setVariables($variables);
		return $this->viewModel;
    }

    protected function getEntityRepository()
    {
        if(is_null($this->entityRepository)) {
            $entityClassName = $this->getEntityClassName();
            $this->entityRepository = $this->entityManager->getRepository($entityClassName);
        }
        return $this->entityRepository;
    }

    protected function addCreateViewModelVariables() { }
    protected function addEditViewModelVariables() { }

    protected function toggleCreateActivity() { }
    protected function toggleEditActivity() { }

    protected function saveFiles($files) 
    {
    	if(!is_null($this->imageStorage)) {
    		switch ($this->imageStorage) {
    			case 'inline':
                    $image = array_shift($files);
    				$this->saveImagesInline($image);
    				break;
    			case 'multiple-inline':
    				# code...
    				break;
    			case 'object':
    				$this->saveImagesObject($files);
    				break;
    			case 'multiple-objects':
    				$this->saveImagesMultipleObjects($files);
    				break;
    			default:
    				# code...
    				break;
    		}
    	}
    }

    protected function saveImagesInline($image)
    {
    	if(!empty($image['name'])) {
	    	// image data:
            // - name
            // - type
            // - size
            // - temporary location
	    	$imageFileInfo = pathinfo($image['name']);

	    	// dir for files
	    	$targetDir = $this->uploadPath . '/'. $this->entity->getId() . '/';
	    	\VisoftBaseModule\Controller\Plugin\AccessoryPlugin::checkDir($targetDir);

	    	// receiver for the upload and transfer
	    	$receiver = new \Zend\File\Transfer\Adapter\Http();
	    	$receiver->setDestination($targetDir);
	    	// set target dir and random name
	    	$receiver->setFilters([
	            new \Zend\Filter\File\Rename([
	                "target" => $targetDir . 'img' . '.' . $imageFileInfo['extension'],
	                "randomize" => true,
	            ])
	        ]);
	    	// move image and get new name
	        if($receiver->receive('image'))
	            $imagePath = $receiver->getFileName('image');
	        
	        // save image with original size image
	        if($this->entity->getPictureOriginal() !== null) {
	        	// file_exist require full path 
	        	$imageFullPath = getcwd() . '/public' . $this->entity->getPictureOriginal();
	        	// if old file exists - remove
	        	if(file_exists($imageFullPath))
	                unlink($imageFullPath);
	        }
	        $this->entity->setPictureOriginal(end(explode('public', $imagePath)));
	    } else {
	    	$imagePath = 'public' . $this->entity->getPictureOriginal();
	    }

        // avoiding division by ziro on $scale parameter
        // TODO: check whole post:
        // - $this->post['xStartCrop']
        // - $this->post['yStartCrop']
        // - $this->post['heightCrop']
        // - $this->post['widthCrop']
        if(empty($this->post['widthCurrent']))
            return false;

        // cropping coordinates
        $xStartCrop = $this->post['xStartCrop'];
        $yStartCrop = $this->post['yStartCrop'];
        $heightCrop = $this->post['heightCrop'];
        $widthCrop = $this->post['widthCrop'];

        // save coordinates
        // not needs to save coordinates in 'inline' case because form bind to entity

        // create thumb
        $thumb = $this->thumbnailer->create($imagePath, $options = [], $plugins = []);

		// crop image
        $currentDimantions = $thumb->getCurrentDimensions();
        $scale = $currentDimantions['width'] / $this->post['widthCurrent'];
        $thumb->crop(
            $xStartCrop * $scale, 
            $yStartCrop * $scale, 
            $widthCrop * $scale, 
            $heightCrop * $scale
        );

        // expold image path for rename
        $explodedImagePath = explode('/', $imagePath);
        $imageName = end($explodedImagePath); // last is image name
        // $imageName = basename($imagePath);
        $imageNameKey = key($explodedImagePath); // key for image name 

		// save large 
        $thumb->resize(960, 960);
        $newImageName = 'large_' . $imageName;
        $explodedImagePath[$imageNameKey] = $newImageName;
        $newImagePath = implode("/", $explodedImagePath);
        $thumb->save($newImagePath);
        if($this->entity->getPictureL() !== null)
            if(file_exists($this->entity->getPictureL()))
                unlink('public' . $this->entity->getPictureL());
        $this->entity->setPictureL(end(explode('public', $newImagePath)));

        // set medium
        $thumb->resize(480, 480);
        $newImageName = 'medium_' . $imageName;
        $explodedImagePath[$imageNameKey] = $newImageName;
        $newImagePath = implode("/", $explodedImagePath);
        $thumb->save($newImagePath);
        if($this->entity->getPictureM() !== null)
            if(file_exists($this->entity->getPictureM()))
                unlink('public' . $this->entity->getPictureM());
        $this->entity->setPictureM(end(explode('public', $newImagePath)));

        // set small
        $thumb->resize(240, 240);
        $newImageName = 'small_' . $imageName;
        $explodedImagePath[$imageNameKey] = $newImageName;
        $newImagePath = implode("/", $explodedImagePath);
        $thumb->save($newImagePath);
        if($this->entity->getPictureS() !== null)
            if(file_exists($this->entity->getPictureS()))
                unlink('public' . $this->entity->getPictureS());
        $this->entity->setPictureS(end(explode('public', $newImagePath)));

        // set x-small
        $thumb->resize(60, 60);
        $newImageName = 'xsmall_' . $imageName;
        $explodedImagePath[$imageNameKey] = $newImageName;
        $newImagePath = implode("/", $explodedImagePath);
        $thumb->save($newImagePath);
        if($this->entity->getPictureXS() !== null)
            if(file_exists($this->entity->getPictureXS()))
                unlink('public' . $this->entity->getPictureXS());
        $this->entity->setPictureXS(end(explode('public', $newImagePath)));

        // set mail
        $thumb->resize(240, 240);
        $newImageName = 'xsmall_' . $imageName;
        $explodedImagePath[$imageNameKey] = $newImageName;
        $newImagePath = implode("/", $explodedImagePath);
        $thumb->save($newImagePath);
        if($this->entity->getPictureMail() !== null)
            if(file_exists($this->entity->getPictureMail()))
                unlink('public' . $this->entity->getPictureMail());
        $this->entity->setPictureMail(end(explode('public', $newImagePath)));

        // save image
        $this->entityManager->persist($this->entity);
        $this->entityManager->flush();
    }

    protected function saveImagesObject($images) 
    {        
        $imageTitleEntity = $this->entity->getImageTitle();
        
        // create new image object or get exists from entity
        if(empty($imageTitleEntity)) {
            $imageTitleEntity = new \VisoftBaseModule\Entity\Image();
            $this->entity->setImageTitle($imageTitleEntity);
        } 

        if(!empty($images['image-title-upload-element']['name'])) {
            // image data - name, type, size, temporary location
            $imageFileInfo = pathinfo($images['image-title-upload-element']['name']);
            
            // transfer image and get new path
            $imageOriginalPath = $this->transferImage('image-title-upload-element', $imageFileInfo);
        } else {
            // return beacause image not uploaded and not exists before
            if($imageTitleEntity->getOriginalSize() === null)
                return ;

            // image not uploaded but exist and continue in order to update cropping
            $imageOriginalPath = 'public' . $imageTitleEntity->getOriginalSize();
        }

        // avoiding division by ziro on $scale parameter
        // TODO: check whole post:
        // - $this->post['xStartCrop']
        // - $this->post['yStartCrop']
        // - $this->post['heightCrop']
        // - $this->post['widthCrop']
        if(empty($this->post['widthCurrent']))
            return false;
        
        // cropping coordinates
        $xStartCrop = $this->post['xStartCrop'];
        $yStartCrop = $this->post['yStartCrop'];
        $heightCrop = $this->post['heightCrop'];
        $widthCrop = $this->post['widthCrop'];

        // save coordinates
        $imageTitleEntity->setXStartCrop($xStartCrop);
        $imageTitleEntity->setYStartCrop($yStartCrop);
        $imageTitleEntity->setHeightCrop($heightCrop);
        $imageTitleEntity->setWidthCrop($widthCrop);
        $imageTitleEntity->setWidthCurrent($this->post['widthCurrent']);
        $imageTitleEntity->setHeightCurrent($this->post['heightCurrent']);

        // image name
        $explodedImagePath = explode('/', $imageOriginalPath);
        $imageName = end($explodedImagePath); // last is image name
        $imageNameKey = key($explodedImagePath); // key for image name value in array
            
        // create thumb
        $thumb = $this->thumbnailer->create($imageOriginalPath, $options = [], $plugins = []);

        // crop image
        $currentDimantions = $thumb->getCurrentDimensions();
        $scale = $currentDimantions['width'] / $this->post['widthCurrent'];
        // scaled coordinates
        $xStartCropScaled = $xStartCrop * $scale;
        $yStartCropScaled = $yStartCrop * $scale;
        $widthCropScaled = $widthCrop * $scale;
        $heightCropScaled = $heightCrop * $scale;
        // crop
        $thumb->crop($xStartCropScaled, $yStartCropScaled, $widthCropScaled, $heightCropScaled);

        // save original image
        if(!is_null($imageTitleEntity->getOriginalSize()))
            if(file_exists($imageTitleEntity->getOriginalSize()))
                unlink('public' . $imageTitleEntity->getOriginalSize());
        $imageTitleEntity->setOriginalSize(end(explode('public', $imageOriginalPath)));
            
        // save large 
        $thumb->resize(960, 960);
        $newImageName = 'large_' . $imageName;
        $explodedImagePath[$imageNameKey] = $newImageName;
        $newImagePath = implode("/", $explodedImagePath);
        $thumb->save($newImagePath);
        if($imageTitleEntity->getLSize() !== null)
            if(file_exists($imageTitleEntity->getLSize()))
                unlink('public' . $imageTitleEntity->getLSize());
        $imageTitleEntity->setLSize(end(explode('public', $newImagePath)));

        // save medium
        $thumb->resize(480, 480);
        $newImageName = 'medium_' . $imageName;
        $explodedImagePath[$imageNameKey] = $newImageName;
        $newImagePath = implode("/", $explodedImagePath);
        $thumb->save($newImagePath);
        if($imageTitleEntity->getMSize() !== null)
            if(file_exists($imageTitleEntity->getMSize()))
                unlink('public' . $imageTitleEntity->getMSize());
        $imageTitleEntity->setMSize(end(explode('public', $newImagePath)));

        // set small
        $thumb->resize(240, 240);
        $newImageName = 'small_' . $imageName;
        $explodedImagePath[$imageNameKey] = $newImageName;
        $newImagePath = implode("/", $explodedImagePath);
        $thumb->save($newImagePath);
        if($imageTitleEntity->getSSize() !== null)
            if(file_exists($imageTitleEntity->getSSize()))
                unlink('public' . $imageTitleEntity->getSSize());
        $imageTitleEntity->setSSize(end(explode('public', $newImagePath)));

        // set x-small
        $thumb->resize(60, 60);
        $newImageName = 'xsmall_' . $imageName;
        $explodedImagePath[$imageNameKey] = $newImageName;
        $newImagePath = implode("/", $explodedImagePath);
        $thumb->save($newImagePath);
        if($imageTitleEntity->getXsSize() !== null)
            if(file_exists($imageTitleEntity->getXsSize()))
                unlink('public' . $imageTitleEntity->getXsSize());
        $imageTitleEntity->setXsSize(end(explode('public', $newImagePath)));

        // save image
        $this->entityManager->persist($imageTitleEntity);
    }

    protected function saveImagesMultipleObjects($images) 
    {
    	// dir for files
    	$targetDir = $this->uploadPath . '/'. $this->entity->getId() . '/';
    	$this->checkDir($targetDir);

    	// receiver of the upload
    	$receiver = new \Zend\File\Transfer\Adapter\Http();
    	$receiver->setDestination($targetDir);

    	foreach ($images as $element => $image) {
    		// find indx for element number
    		preg_match_all('!\d+!', $element, $matches);
    		$indx = implode('', $matches[0]); // last character is number - $image1
    		// saving data to image entity
    		$getImageFunctionName = 'getImg' . $indx;
    		$setImageFunctionName = 'setImg' . $indx;
    		// get image from original entity
    		$image = $this->entity->$getImageFunctionName();
    		if(empty($image)) 
    			// if image not set (null) - create new entity for image
    			$image = new \VisoftBaseModule\Entity\Image();

    		// get image path 
    		if(!empty($this->post[$element]['name'])) {
    			// if uploaded - move image to target dir, rename and get new file path
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
    			// if not uploaded check if image has been uploaded before
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

    protected function setCreateExtra()
    {
    	
    }

    protected function setExtra()
    {
    	
    }

    protected function bindExtra() 
    {
		// for ($indx = 1; $indx < 5; $indx++) { 
    		// $getImageFunctionName = 'getImg';
    		// $image = $this->getEntity()->getImage();
    		// // var_dump($image);
    		// // die('dd');
    		// if(!empty($image)) {
    		// 	$this->editForm->get('xStartCrop')->setValue($image->getXStartCrop());
	    	// 	$this->editForm->get('yStartCrop')->setValue($image->getYStartCrop());
	    	// 	$this->editForm->get('heightCrop')->setValue($image->getHeightCrop());
	    	// 	$this->editForm->get('widthCrop')->setValue($image->getWidthCrop());
	    	// 	$this->editForm->get('heightCurrent')->setValue($image->getHeightCurrent());
	    	// 	$this->editForm->get('widthCurrent')->setValue($image->getWidthCurrent());
    		// }
    		
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

	public function setInputFilters($inputFilters)
	{
		$this->createInputFilter = isset($inputFilters['create']) ? $inputFilters['create'] : null;
		$this->editInputFilter = isset($inputFilters['edit']) ? $inputFilters['edit'] : null;
		return $this;
	}

	public function setImageStorage($imageStorage)
	{
		$this->imageStorage = $imageStorage;
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

	public function setSlugService($slugService)
	{
		$this->slugService = $slugService;
		return $this;
	}

    private function transferImage($elementName, $imageFileInfo) 
    {
        // dir for files
        $targetDir = $this->uploadPath . '/'. $this->entity->getId() . '/';
        \VisoftBaseModule\Controller\Plugin\AccessoryPlugin::checkDir($targetDir);

        // receiver of the upload
        $receiver = new \Zend\File\Transfer\Adapter\Http();
        $receiver->setDestination($targetDir);

        // set target dir and random name
        $receiver->setFilters([
            new \Zend\Filter\File\Rename([
                "target" => $targetDir . 'img' . '.' . $imageFileInfo['extension'],
                "randomize" => true,
            ])
        ]);

        // move image and get new name
        if($receiver->receive($elementName))
            return $imagePath = $receiver->getFileName($elementName);

        return false;
    }

    private function bindImageTitleCoordinates() 
    {
        $imageTitleEntity = $this->entity->getImageTitle();
        if(!empty($imageTitleEntity)) {
            $this->editForm->get('xStartCrop')->setValue($imageTitleEntity->getXStartCrop());
            $this->editForm->get('yStartCrop')->setValue($imageTitleEntity->getYStartCrop());
            $this->editForm->get('heightCrop')->setValue($imageTitleEntity->getHeightCrop());
            $this->editForm->get('widthCrop')->setValue($imageTitleEntity->getWidthCrop());
            $this->editForm->get('heightCurrent')->setValue($imageTitleEntity->getHeightCurrent());
            $this->editForm->get('widthCurrent')->setValue($imageTitleEntity->getWidthCurrent());
        }
    }

    protected function redirectToRefer()
    {
        $scheme = $this->request->getHeader('Referer')->uri()->getScheme();
        $host = $this->request->getHeader('Referer')->uri()->getHost();
        $path = $this->request->getHeader('Referer')->uri()->getPath();
        $port = $this->request->getHeader('Referer')->uri()->getPort();
        $port = is_null($port) ? null : ':' . $port;
        $query = $this->request->getHeader('Referer')->uri()->getQuery();
        $redirectUrl = $scheme . '://' . $host  . $port . $path . '?' . $query;
        return $this->redirect()->toUrl($redirectUrl);
    }
}
