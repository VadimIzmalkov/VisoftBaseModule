<?php

namespace VisoftBaseModule\Options;

class ModuleOptions extends \Zend\Stdlib\AbstractOptions
{
	// roles
	// protected $roleGuest = null;
	// protected $roleSuperUser = 1;
	// protected $roleUser = 2;
	// protected $roleMember = 3;
	protected $roleSubscriberId = 4;
    // protected $signInRedirectRoute = 'account';
    // protected $signUpRedirectRoute = 'sign-up/profile-complete';

    protected $templates;
    protected $layouts;
    protected $forms;

    protected $redirects;

    public function __construct($options)
    {
    	parent::__construct($options);
    }

    public function getRoleSubscriberId() { return $this->roleSubscriberId; }
    public function setRoleSubscriberId($roleSubscriberId) { 
    	$this->roleSubscriberId = $roleSubscriberId;
        return $this;
    }

    // public function getSignInRedirectRoute() { return $this->signInRedirectRoute; }
    // public function setSignInRedirectRoute($signInRedirectRoute) {
    //     $this->signInRedirectRoute = $signInRedirectRoute;
    //     return $this;
    // }

    // public function getSignUpRedirectRoute() { return $this->signUpRedirectRoute; }
    // public function setSignUpRedirectRoute($signUpRedirectRoute) {
    //     $this->signUpRedirectRoute = $signUpRedirectRoute;
    //     return $this;
    // }  

    public function getTemplates() { return $this->templates; }
    public function setTemplates($templates) {
        $this->templates = $templates;
        return $this;
    }

    public function getLayouts() { return $this->layouts; }
    public function setLayouts($layouts) {
        $this->layouts = $layouts;
        return $this;
    }

    public function getForms() { return $this->forms; }
    public function setForms($forms) {
        $this->forms = $forms;
        return $this;
    }

    public function getRedirects() { return $this->redirects; }
    public function setRedirects($redirects) {
        $this->redirects = $redirects;
        return $this;
    }  
}