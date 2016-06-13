<?php 

namespace VisoftBaseModule\Controller;

abstract class AbstractBaseController extends \Zend\Mvc\Controller\AbstractActionController
{
	private $entityManager;

	protected function setEntityManager(\Doctrine\ORM\EntityManager $entityManager)
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