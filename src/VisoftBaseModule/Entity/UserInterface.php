<?php

namespace VisoftBaseModule\Entity;

interface UserInterface
{
	public function getId();
	public function getToken();

	public function setFullName($fullName);

	// OAuth2
	// public function setFacebookId($facebookId);

	// public function setAvatar(Image $avatar);
	// public function getAvatar();
}