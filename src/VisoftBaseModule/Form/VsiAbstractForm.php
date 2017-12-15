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

    public function __construct($name = NULL, $options = NULL)
    {
        parent::__construct($name, $options);
    }

	protected function arrayExchange($parametersInput) 
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
		$parametersOutput['multiple'] 		= isset($parametersInput['multiple']) ? $parametersInput['multiple'] : false;
		$parametersOutput['value-options'] 	= isset($parametersInput['value-options']) ? $parametersInput['value-options'] : NULL;
		$parametersOutput['empty-option'] 	= isset($parametersInput['empty-option']) ? $parametersInput['empty-option'] : NULL;

		return $parametersOutput;
	}

	public function addElementText($parameters)
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

	public function addHidden($parameters)
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

	public function addElementEmail($parameters)
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
		    	'class' => $parameters['class'],
		    	'placeholder' => $parameters['placeholder'],
		    	'required' => $parameters['required'],
            ]
		]);
	}

	public function addElementPassword($parameters)
	{
		$parameters = $this->arrayExchange($parameters);

		$this->add([
		    'type' => Element\Password::class,
		    'name' => $parameters['name'],
		    'options' => [
		        'label' => $parameters['label'],
		    ],
		    'attributes' => [
		    	'class' => $parameters['class'],
		    	'placeholder' => $parameters['placeholder'],
		    	'required' => $parameters['required'],
            ]
		]);
	}

    protected function addCheckbox($parameters) 
    {
    	$parameters = $this->arrayExchange($parameters);

        $this->add([
            'type' => Element\Checkbox::class,
            'name' => $parameters['name'],
            'attributes' => [
                'class' => $parameters['class'],
                // 'style' => "display: inline; top: 3px; margin-bottom: 0px;",
                'id' => $parameters['id'],
            ],
            'options' => [
                'label' => $parameters['label'],
                'label_attributes' => [
                	'class' => $parameters['labelClass'],
                ],
                'use_hidden_element' => true,
                'checked_value' => 1,
                'unchecked_value' => 'no',
            ],
        ]);
    }

	protected function addElementSelect($parameters)
	{
		$parameters = $this->arrayExchange($parameters);

		$this->add([
		    'type' => Element\Select::class,
		    'name' => $parameters['name'],
		    'attributes' => [
		    	'id' => $parameters['id'],
		    	'class' => $parameters['class'],
            ],
		    'options' => [
		    	'empty_option' => $parameters['empty-option'],
		        'value_options' => $parameters['value-options'],
		        'label' => $parameters['label'],
		        'label_attributes' => [
                    'class' => $parameters['labelClass'],
                ],
		    ],
		]);
	}

	public function addFileElement($parameters)
	{
		$parameters = $this->arrayExchange($parameters);

		$options = [
            'name' => $parameters['name'],
            'type' => Element\File::class,
            'attributes' => [
                'id' => $parameters['id'],
                'required' => $parameters['required'],
            ],
            'options' => [
                'label' => $parameters['label'],
                'label_attributes' => [
                	'class' => $parameters['labelClass'],
                ],
            ]
        ];

        if($parameters['multiple'])
        {
        	$options['attributes']['multiple'] = true;
        }

		$this->add($options);
	}

	public function addElementSubmit($parameters)
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
