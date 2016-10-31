<?php

namespace VisoftBaseModule\Form;

use Zend\Form\Element;

abstract class VsiAbstractForm extends \Zend\Form\Form
{
	protected $title = '';

	public function getTitle() { return $this->title; }

	protected function addElementEmail($parameters)
	{
		$this->add([
		    'type' => Element\Email::class,
		    'name' => $parameters['name'] ?? 'e-mail',
		    'options' => [ 
		        'label' => $parameters['label'] ?? 'Email Address',
		    ],
		    'attributes' => [
		    	'class' => 'form-control',
            ]
		]);
	}

	protected function addElementPassword($parameters)
	{
		$this->add([
		    'type' => Element\Password::class,
		    'name' => $parameters['name'] ?? 'password',
		    'options' => [
		        'label' => $parameters['label'] ?? 'Password',
		    ],
		    'attributes' => [
		    	'class' => 'form-control',
            ]
		]);
	}

	protected function addElementSubmit($parameters)
	{
		$this->add([
		    'type' => Element\Submit::class,
		    'name' => $parameters['name'] ?? 'submit',
		    'attributes' => [
		    	'class' => 'btn btn-default btn-block',
		    	'value' => $parameters['value'] ?? 'Submit'
            ]
		]);
	}
}
