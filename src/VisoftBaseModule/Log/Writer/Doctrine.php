<?php
namespace VisoftBaseModule\Log\Writer;

use Zend\Log\Writer\AbstractWriter;

use Doctrine\ORM\EntityManager;

use Fryday\Entity;

/**
 * Description of Doctrine
 *
 * @author seyfer
 */
class Doctrine extends AbstractWriter
{
    /**
     *
     * @var BaseLog
     */
    private $logEntity;

    /**
     *
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Constructor
     *
     * @param string $modelClass
     * @param array $columnMap
     * @return void
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    protected function doWrite(array $event)
    {        
        $this->logEntity = new Entity\UserActivity();
        $this->logEntity->setMessage($event['message']);
        $this->logEntity->setUser($event['extra']['user']);
        $this->entityManager->persist($this->logEntity);
        $this->entityManager->flush();
    }
}