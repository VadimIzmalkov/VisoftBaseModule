<?php
namespace VisoftBaseModule\Controller\Factory;
 
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\AbstractPluginManager;

class AbstractCrudControllerFactory implements AbstractFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        if (!$serviceLocator instanceof AbstractPluginManager) {
            throw new \BadMethodCallException('This abstract factory is meant to be used only with a plugin manager');
        }
        $parentLocator = $serviceLocator->getServiceLocator();
        $config = $parentLocator->get('config');
        return isset($config['crud_controllers'][$requestedName]);
    }
 
    /**
     * {@inheritDoc}
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        if (!$this->canCreateServiceWithName($serviceLocator, $name, $requestedName)) 
            throw new \BadMethodCallException('This abstract factory can\'t create service "' . $requestedName . '"');
        
        $parentLocator = $serviceLocator->getServiceLocator();
        $entityManager = $parentLocator->get('Doctrine\ORM\EntityManager');
        $authenticationService = $parentLocator->get('Zend\Authentication\AuthenticationService');
        $identity = $authenticationService->getIdentity();

        $config = $parentLocator->get('config');
        $config = $config['crud_controllers'][$requestedName];
        
        if (isset($config['controller_class']))
            $controllerClass = $config['controller_class'];
        else
            $controllerClass = $requestedName;
        if (!class_exists($controllerClass))
            if ('Controller' !== substr($requestedName, -10))
                $controllerClass .= 'Controller';
        $entityClass = isset($config['entityClass']) ? $config['entityClass'] : null;
        $uploadPath = isset($config['uploadPath']) ? $config['uploadPath'] : null;

        $crudController = new $controllerClass($entityManager, $entityClass);

        if(isset($config['forms'])) {
            $formParameters = $config['forms'];
            $formClass = $formParameters['class'];
            
            // form for create action
            if(isset($formParameters['options']['create'])) {
                $formType = $formParameters['options']['create'];
                $form = new $formClass($entityManager, $formType, $identity);
                $forms['create'] = $form;
            }
            
            // form for edit action
            if(isset($formParameters['options']['edit'])) {
                $formType = $formParameters['options']['edit'];
                $form = new $formClass($entityManager, $formType, $identity);
                $forms['edit'] = $form;
            }

            // set form
            $crudController->setForms($forms);

            if(isset($formParameters['inputFilters'])) {
                $inputFiltersParameters = $config['inputFilters'];
                // input filter for create action
                if(isset($inputFiltersParameters['options']['create'])) {
                    $inputFilterClass = $inputFiltersParameters['class'];
                    $inputFilterType = $inputFiltersParameters['options']['create'];
                    $inputFilter = new $inputFilterClass($entityManager, $inputFilterType, $identity);
                    $inputFilters['create'] = $inputFilter;
                }

                // input filter for edit action
                if(isset($inputFiltersParameters['options']['edit'])) {
                    $inputFilterClass = $inputFiltersParameters['class'];
                    $inputFilterType = $inputFiltersParameters['options']['edit'];
                    $inputFilter = new $inputFilterClass($entityManager, $inputFilterType, $identity);
                    $inputFilters['edit'] = $inputFilter;
                }

                // set input filters
                $crudController->setInputFilters($inputFilters);
            }
        }

        if(isset($config['imageStorage']))
            $crudController->setImageStorage($config['imageStorage']);

        if(isset($config['templates']))
            $crudController->setTemplates($config['templates']);

        if(isset($config['layouts']))
            $crudController->setLayouts($config['layouts']);

        if(isset($config['uploadPath']))
            $crudController->setUploadPath($config['uploadPath']);

        $thumbnailer = $parentLocator->get('WebinoImageThumb');
        $crudController->setThumbnailer($thumbnailer);

        $slugService = $parentLocator->get('SeoUrl\Slug');
        $crudController->setSlugService($slugService);

        return $crudController;
    }
    /**
     * Get templates
     * 
     * @param array $config Crud controller config
     * 
     * @return array
     */
    // protected function getTemplates(array $config)
    // {
    //     $templates = array();
    //     if (isset($config['template_prefix'])) {
    //         $prefix = $config['template_prefix'];
    //         $templates['prefix'] = $prefix;
    //         foreach (array('list', 'new', 'edit') as $name) {
    //             $templates[$name] = $prefix . '/' . $name;
    //         }
    //     }
    //     if (isset($config['templates'])) {
    //         $templates = array_merge($templates, $config['templates']);
    //     }
    //     return $templates;
    // }
    /**
     * Get routes
     * 
     * @param array $config Crud controller config
     * 
     * @return array
     */
    // protected function getRoutes(array $config)
    // {
    //     $routes = array();
    //     if (isset($config['route_prefix'])) {
    //         $prefix = $config['route_prefix'];
    //         $routes['prefix'] = $prefix;
    //         foreach (array('list', 'new', 'edit', 'delete') as $name) {
    //             $routes[$name] = $prefix . '/' . $name;
    //         }
    //     }
    //     if (isset($config['routes'])) {
    //         $routes = array_merge($routes, $config['routes']);
    //     }
    //     return $routes;
    // }
}