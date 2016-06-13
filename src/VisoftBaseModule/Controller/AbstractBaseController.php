<?php 

namespace VisoftBaseModule\Controller;

abstract class AbstractBaseController extends \Zend\Mvc\Controller\AbstractActionController
{
	private $entityManager;

	protected function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        return $this;
    }

    protected function getEntityManager()
    {
        if (null === $this->entityManager) {
            $this->setEntityManager($this->getServiceLocator()->get('Doctrine\ORM\EntityManager'));
        }
        return $this->entityManager;
    } 
}