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
        // var_dump($requestedName);
        // var_dump($config);
        // die('1');
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
        $config = $parentLocator->get('config');
        $config = $config['crud_controllers'][$requestedName];
        if (isset($config['controller_class']))
            $controllerClass = $config['controller_class'];
        else
            $controllerClass = $requestedName;
        if (!class_exists($controllerClass))
            if ('Controller' !== substr($requestedName, -10))
                $controllerClass .= 'Controller';
        // $fm = $parentLocator->get('FormElementManager');
        $entityClass = isset($config['entityClass']) ? $config['entityClass'] : null;
        $uploadPath = isset($config['uploadPath']) ? $config['uploadPath'] : null;
        // $form = isset($config['form_class']) ? $fm->get($config['form_class']) : null;
        // $paginator = isset($config['paginator_class']) ? $parentLocator->get($config['paginator_class']) : null;
        // $templates = $this->getTemplates($config);
        // $routes = $this->getRoutes($config);
        // $repository = $parentLocator->get('Nicovogelaar\CrudController\Repository\CrudRepository');
        // $repository->setEntityClass($entityClass);
        $entityManager = $parentLocator->get('Doctrine\ORM\EntityManager');
        // var_dump($requestedName);
        // var_dump($config);
        // die('1');
        // $isEx = class_exists($controllerClass);
        // var_dump($isEx);
        // $controller =  new \Fryday\Controller\EventController($entityManager, $entityClass, $uploadPath);
        // die('2');
        // return $controller;
        $crudController = new $controllerClass($entityManager, $entityClass, $uploadPath);
        // $crudController->setAuthenticationService();
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