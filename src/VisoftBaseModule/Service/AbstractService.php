<?php 

namespace VisoftBaseModule\Service;

use Zend\Log\Logger;
use Fryday\Log\Writer\Doctrine as DoctrineWriter;

abstract class AbstractService
{
	private $logger = null;

    public function getLogger()
    {
    	if(is_null($this->logger)) 
    		$this->setLogger();
    	return $this->logger;
    }

    public function setLogger($logger = null)
    {
    	if(is_null($logger)) {
    		$this->logger = new Logger;
        	$writer = new DoctrineWriter($this->entityManager);
        	$this->logger->addWriter($writer);
    	} else {
    		$this->logger = $logger;
    	}    
    }
}