<?php 

namespace VisoftBaseModule\Service;

class ActivityService
{
	protected $entityManager;

	public function __construct($entityManager)
	{
		$this->entityManager = $entityManager;
	}

	public function toggle($activityType, $user, $entity, $extra = null)
	{
		// - sign-in/sign-up
		// - facebook
		// - linked
		// die('11111');
	}
}