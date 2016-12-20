<?php

namespace VisoftBaseModule\Form;

use Zend\Form\Element;

abstract class VsiAbstractForm extends \Zend\Form\Form
{
	private $title = '';
	private $entityManager = NULL;
	private $identity = NULL;

	public function getTitle() { return $this->title; }
	public function setTitle($title) { $this->title = $title; }

	public function getEntityManager() { return $this->entityManager; }
	public function setEntityManager($entityManager) { $this->entityManager = $entityManager; }

	public function getIdentity() { return $this->identity; }
	public function setIdentity($identity) { $this->identity = $identity; }

	private function arrayExchange($parametersInput) 
	{
		$parametersOutput['id']				= isset($parametersInput['id']) ? $parametersInput['id'] : NULL;
		$parametersOutput['name'] 			= isset($parametersInput['name']) ? $parametersInput['name'] : NULL;
		$parametersOutput['label'] 			= isset($parametersInput['label']) ? $parametersInput['label'] : NULL;
		$parametersOutput['placeholder']	= isset($parametersInput['placeholder']) ? $parametersInput['placeholder'] : NULL;
		$parametersOutput['labelClass'] 	= isset($parametersInput['labelClass']) ? $parametersInput['labelClass'] : 'label';
		$parametersOutput['rows'] 			= isset($parametersInput['rows']) ? $parametersInput['rows'] : 5;
		$parametersOutput['class'] 			= isset($parametersInput['class']) ? $parametersInput['class'] : 'form-control';
		$parametersOutput['value'] 			= isset($parametersInput['value']) ? $parametersInput['value'] : 'Submit';
		$parametersOutput['disabled'] 		= isset($parametersInput['disabled']) ? $parametersInput['disabled'] : false;
		$parametersOutput['required'] 		= isset($parametersInput['required']) ? $parametersInput['required'] : false;

		return $parametersOutput;
	}

	protected function addElementText($parameters)
	{
		$parameters = $this->arrayExchange($parameters);

		$this->add([
		    'type' => Element\Text::class,
		    'name' => $parameters['name'],
		    'options' => [ 
		        'label' => $parameters['label'],
		        'label_attributes' => [
                    'class' => $parameters['labelClass'],
                ],
		    ],
		    'attributes' => [
		    	'class' => $parameters['class'],
		    	'disabled' => $parameters['disabled'],
		    	'id' => $parameters['id'],
		    	'placeholder' => $parameters['placeholder'],
		    	'required' => $parameters['required'],
            ]
		]);
	}

	protected function addHidden($parameters)
	{
		$parameters = $this->arrayExchange($parameters);

		$this->add([
			'type' => Element\Hidden::class,
            'name' => $parameters['name'],
            'attributes' => [
                'id' => $parameters['id'],
                'value' => $parameters['value'],
                // $attributes,
                // 'data-geo' => 'country'
            ],
        ]);
	}

	protected function addElementTextarea($parameters)
	{
		$parameters = $this->arrayExchange($parameters);

		$this->add([
		    'type' => Element\Textarea::class,
		    'name' => $parameters['name'],
		    'options' => [
		        'label' => $parameters['label'],
		        'label_attributes' => [
                    'class' => $parameters['labelClass'],
                ],
		    ],
		    'attributes' => [
		    	'class' => $parameters['class'],
		    	'rows' => $parameters['rows'],
		    	'placeholder' => $parameters['placeholder'],
		    	'required' => $parameters['required'],
            ]
		]);
	}

	protected function addElementEmail($parameters)
	{
		$parameters = $this->arrayExchange($parameters);

		$this->add([
		    'type' => Element\Email::class,
		    'name' => $parameters['name'],
		    'options' => [ 
		        'label' => $parameters['label'],
		        'label_attributes' => [
                    'class' => $parameters['labelClass'],
                ],
		    ],
		    'attributes' => [
		    	'class' => 'form-control',
		    	'required' => $parameters['required'],
            ]
		]);
	}

	protected function addElementPassword($parameters)
	{
		$parameters = $this->arrayExchange($parameters);

		$this->add([
		    'type' => Element\Password::class,
		    'name' => $parameters['name'],
		    'options' => [
		        'label' => $parameters['label'],
		    ],
		    'attributes' => [
		    	'class' => 'form-control',
            ]
		]);
	}

	protected function addElementSubmit($parameters)
	{
		$parameters = $this->arrayExchange($parameters);

		$this->add([
		    'type' => Element\Submit::class,
		    'name' => $parameters['name'],
		    'attributes' => [
		    	'class' => $parameters['class'],
		    	'value' => $parameters['value'],
		    	'id' => $parameters['id'],
            ]
		]);
	}
}
