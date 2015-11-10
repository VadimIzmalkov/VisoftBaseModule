<?php 

namespace VisoftBaseModule\Controller;

use Zend\Mvc\Controller\AbstractActionController,
    Zend\Log\Logger;

use Doctrine\ORM\EntityManager;

use VisoftBaseModule\Log\Writer\Doctrine as DoctrineWriter;

abstract class BaseController extends AbstractActionController
{
    private $logger = null;
    
    public function checkDir($dir)
    {
        if (!is_dir($dir)) {
            $oldmask = umask(0);
            if (!mkdir($dir, 0777, true)) {
                die('Failed to create folders' . $dir);
            }
            umask($oldmask);
        }        
    }
    
    public function downloadFile($file, $fileName)
    {
        if (file_exists($file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . $fileName);
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            // header('Content-Length: ' . filesize($filename)); // $file));
            ob_clean();
            flush();
            // readfile($file);
            readfile($file);
        } else {
            echo 'The file $fileName does not exist';
        }
        exit;
    }

    public function getLogger()
    {
        if(is_null($this->logger)) {
            $this->setLogger();
        }
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