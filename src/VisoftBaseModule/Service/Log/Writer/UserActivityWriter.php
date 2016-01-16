<?php
namespace VisoftBaseModule\Service\Log\Writer;

use Zend\Log\Writer\AbstractWriter;
use Doctrine\ORM\EntityManager;
use VisoftBaseModule\Entity;

class UserActivityWriter extends AbstractWriter
{
    private $logEntity;
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    protected function doWrite(array $event)
    {        
        $this->logEntity = new Entity\UserActivityLog();
        $this->logEntity->setMessage($event['message']);
        $this->logEntity->setUser($event['extra']['user']);
        $this->entityManager->persist($this->logEntity);
        $this->entityManager->flush();
    }
}