<?php 

namespace VisoftBaseModule\Controller;

use Zend\Mvc\Controller\AbstractActionController;

abstract class BaseController extends AbstractActionController
{
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
}