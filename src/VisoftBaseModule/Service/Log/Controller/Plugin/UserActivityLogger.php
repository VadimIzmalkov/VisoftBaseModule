<?php
namespace VisoftBaseModule\Service\Log\Controller\Plugin;
 
use Zend\Mvc\Controller\Plugin\AbstractPlugin,
	Zend\Log\Logger;
use VisoftBaseModule\Service\Log\Writer\UserActivityWriter;
 
class UserActivityLogger extends AbstractPlugin
{
	protected $logger = null;
	protected $entityManager = null;

	public function __construct($entityManager)
	{
		$this->entityManager = $entityManager;
		$this->logger = new Logger;
		$writer = new UserActivityWriter($this->entityManager);
		$this->logger->addWriter($writer);
	}

	public function log($user, $message)
	{
		$this->logger->log(\Zend\Log\Logger::INFO, $message, ['user' => $user]);
	}

	public function getLogger()
	{

	}
}