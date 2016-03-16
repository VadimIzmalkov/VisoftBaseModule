<?php
namespace VisoftBaseModule\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class AccessoryPlugin extends AbstractPlugin
{
	public static function checkDir($dir) 
	{
		if (!is_dir($dir)) {
            $oldmask = umask(0);
            if (!mkdir($dir, 0777, true))
                die('Failed to create folders' . $dir);
            umask($oldmask);
        }
	}
}
