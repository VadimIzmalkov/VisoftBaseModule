<?php

namespace VisoftBaseModule\Entity;

interface UserInterface
{
	public function getId();
	public function getToken();

	public function setFullName($fullName);
	public function getFullName();

	public function setCreatedBy(self $user);
	public function getCreatedBy();

	public function setProviderId($providerName, $providerId);
	public function getProviderId($providerName);

	public function setAvatar(Image $avatar);
	public function getAvatar();

	public function setRole(UserRole $role);
	public function getRole();
}