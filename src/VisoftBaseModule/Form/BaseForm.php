<?php

namespace VisoftBaseModule\Form;

use Zend\Form\Form;

use Doctrine\ORM\EntityManager;

class BaseForm extends Form 
{
    private $title;
	protected $entityManager;

	public function __construct(EntityManager $entityManager)
	{
		parent::__construct();
        $this->entityManager = $entityManager;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function addText($name, $label = null, $labelClass = 'label', $id = null, $required = false, $placeholder = null, $disabled = false, $elementClass = 'form-control')
    {
        $this->add([
            'name' => $name,
            'type' => 'Zend\Form\Element\Text',
            'options' => [
                'label' => $label,
                'label_attributes' => [
                    'class' => $labelClass,
                ],
            ],
            'attributes' => [
                'class' => $elementClass,
                'placeholder' => $placeholder,
                'id' => $id,
                'disabled' => $disabled,
                'required' => $required,
            ],
        ]);
    }

    public function addTextarea($name, $label = null, $labelClass = 'label', $rows = 5, $id = null, $required = false, $placeholder = null, $disabled = false, $elementClass = 'form-control')
    {
        $this->add([
            'name' => $name,
            'type' => 'Zend\Form\Element\Textarea',
            'attributes' => [
                'id' => $id,
                'rows' => $rows,
                'class' => $elementClass,
                'disabled' => $disabled,
                'required' => $required,
            ],
            'options' => [
                'label' => $label,
                'label_attributes' => array(
                    'class' => $labelClass,
                ),
            ]
        ]);
    }

    public function addMultiCheckboxEntities($name, $targetClass, $property = 'name', $labelClass = null, $id = null, $isMethod = true, $label = null)
    {
        $this->add([
            'name' => $name,
            'type' => 'DoctrineModule\Form\Element\ObjectMultiCheckbox',
            'attributes' => ['id' => $id],
            'options' => [
                'label' => $label,
                'label_attributes' => ['class' => $labelClass],
                'object_manager' => $this->entityManager,
                'target_class' => $targetClass,
                'property' => $property,
                'is_method' => $isMethod,
                'find_method' => [
                    'name'   => 'findBy',
                    'params' => [
                        'criteria' => [],
                        'orderBy'  => ['name' => 'ASC'],
                    ],
                ],
            ],
        ]);
    }

    public function addCheckbox($name, $label = null, $labelClass = "", $id = null, $elementClass = "", $elementStyle = "") 
    {
        $this->add([
            'name' => $name,
            'type' => 'Zend\Form\Element\Checkbox',
            'attributes' => [
                'class' => $elementClass, //'checkbox fryday-checkbox',
                'style' => $elementStyle,//"left: 22px; position: absolute; z-index: 1",
                'id' => $id,
            ],
            'options' => [
                'label' => $label,
                'label_attributes' => array(
                    'class'  => $labelClass, //'checkbox fryday-checkbox',
                ),
                'use_hidden_element' => true,
                'checked_value' => 1,
                'unchecked_value' => 'no',
            ],
        ]);
    }

    public function addSubmit($name, $value = 'Submit', $elementClass = '', $id = null)
    {
        $this->add(array(
            'name' => $name,
            'type' => 'Zend\Form\Element\Submit',
            'attributes' => [
                'id' => $id,
                'class' => $elementClass,
                'value' => $value,
            ],
        ));             
    }
}