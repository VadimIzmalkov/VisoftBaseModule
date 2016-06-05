<?php
namespace VisoftBaseModule\Controller\Plugin;

class ActivityPlugin extends \Zend\Mvc\Controller\Plugin\AbstractPlugin
{
	protected $activityService;

	public function __construct($activityService)
	{
		$this->activityService = $activityService;
	}

	public function toggle($message, $user, $entityId, $extra = null)
	{
		return $this->activityService->toggle($message, $user, $entityId, $extra);
	}
}
