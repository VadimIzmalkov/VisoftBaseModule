<?php

namespace VisoftBaseModule\Form;

use Zend\Form\Element;

abstract class VsiAbstractForm extends \Zend\Form\Form
{
	protected $title = '';

	public function getTitle() { return $this->title; }

	private function arrayExchange($parametersInput) 
	{
		$parametersOutput['name'] = isset($parametersInput['name']) ? $parametersInput['name'] : NULL;
		$parametersOutput['label'] = isset($parametersInput['label']) ? $parametersInput['label'] : NULL;
		$parametersOutput['labelClass'] = isset($parametersInput['labelClass']) ? $parametersInput['labelClass'] : NULL;
		$parametersOutput['rows'] = isset($parametersInput['rows']) ? $parametersInput['rows'] : 5;
		$parametersOutput['class'] = isset($parametersInput['class']) ? $parametersInput['class'] : NULL;
		$parametersOutput['value'] = isset($parametersInput['value']) ? $parametersInput['value'] : 'Submit';

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
		    	'class' => 'form-control',
            ]
		]);
	}

	protected function addElementTextarea($parameters)
	{
		$parameters = $this->arrayExchange($parameters);

		$this->add([
		    'type' => Element\Textarea::class,
		    'name' => $parameters['name'],
		    'options' => [
		    	'rows' => $parameters['rows'],
		        'label' => $parameters['label'],
		        'label_attributes' => [
                    'class' => $parameters['labelClass'],
                ],
		    ],
		    'attributes' => [
		    	'class' => 'form-control',
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
            ]
		]);
	}
}
