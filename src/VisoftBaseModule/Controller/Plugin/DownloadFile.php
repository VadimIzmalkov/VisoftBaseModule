<?php
namespace VisoftBaseModule\Controller\Plugin;

class DownloadFile extends \Zend\Mvc\Controller\Plugin\AbstractPlugin;
{
	public function __invoke($filePath) 
	{
		if (file_exists($filePath)) {
			$response = new \Zend\Http\Response\Stream();
	        $response->setStream(fopen($filePath, 'r'));
	        $response->setStatusCode(200);
	        $response->setStreamName(basename($filePath));
	        $headers = new \Zend\Http\Headers();
	        $headers->addHeaders(array(
	            'Content-Disposition' => 'attachment; filename="' . basename($filePath) .'"',
	            'Content-Type' => 'application/octet-stream',
	            'Content-Length' => filesize($filePath),
	            'Expires' => '@0', // @0, because zf2 parses date as string to \DateTime() object
	            'Cache-Control' => 'must-revalidate',
	            'Pragma' => 'public'
	        ));
	        $response->setHeaders($headers);
	        return $response;
        } else {
        	die('File not exist');
        }
	}
}
