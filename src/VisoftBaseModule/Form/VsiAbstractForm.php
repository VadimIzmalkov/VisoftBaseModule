<?php

namespace VisoftBaseModule\Form;

use Zend\Form\Element;

abstract class VsiAbstractForm extends \Zend\Form\Form
{
	protected $title = '';

	public function getTitle() { return $this->title; }

	protected function addElementText($parameters)
	{
		$this->add([
		    'type' => Element\Text::class,
		    'name' => $parameters['name'] ?? 'input',
		    'options' => [ 
		        'label' => $parameters['label'] ?? NULL,
		        'label_attributes' => [
                    'class' => $parameters['labelClass'] ?? NULL,
                ],
		    ],
		    'attributes' => [
		    	'class' => 'form-control',
            ]
		]);
	}

	protected function addElementTextarea($parameters)
	{
		$this->add([
		    'type' => Element\Textarea::class,
		    'name' => $parameters['name'] ?? 'e-mail',
		    'options' => [
		    	'rows' => $parameters['rows'] ?? 5,
		        'label' => $parameters['label'] ?? NULL,
		        'label_attributes' => [
                    'class' => $parameters['labelClass'] ?? NULL,
                ],
		    ],
		    'attributes' => [
		    	'class' => 'form-control',
            ]
		]);
	}

	protected function addElementEmail($parameters)
	{
		$this->add([
		    'type' => Element\Email::class,
		    'name' => $parameters['name'] ?? 'e-mail',
		    'options' => [ 
		        'label' => $parameters['label'] ?? 'Email Address',
		        'label_attributes' => [
                    'class' => $parameters['labelClass'] ?? NULL,
                ],
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
		    	'class' => $parameters['class'] ?? 'btn',
		    	'value' => $parameters['value'] ?? 'Submit'
            ]
		]);
	}
}
