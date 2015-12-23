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

    public function addText($name, $label = null, $labelClass = 'label', $id = null, $required = false, $placeholder = null, $disabled = false, $readonly = false, $elementClass = 'form-control')
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
                'readonly' => $readonly,
            ],
        ]);
    }

    public function addHidden($name, $id, $value = null, $attributes)
    {
        $this->add([
            'name' => $name,
            'type' => 'Zend\Form\Element\Hidden',
            'attributes' => [
                'id' => $id,
                'value' => $value,
                $attributes,
                // 'data-geo' => 'country'
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

    // TODO: move to fryday application
    public function addSelectRepresentative($name, $label = "Select Represenative", $id = 'representative-select') 
    {
        $this->add([
            'name' => $name,
            'type' => 'DoctrineModule\Form\Element\ObjectSelect',
            'attributes' => [
                'class' => 'form-control',
                'id' => $id,
            ],
            'options' => [
                'label' => $label,
                'label_attributes' => ['class' => 'label'],
                'object_manager' => $this->entityManager,
                'target_class' => 'VisoftBaseModule\Entity\UserInterface',
                'property' => 'fullName',
                'display_empty_item' => true,
                'empty_item_label' => '-- Select Represenative --',
                'is_method' => true,
                'find_method' => array(
                    'name'   => 'findBy',
                    'params' => array(
                        'criteria' => ['role' => [1, 2]],
                        'orderBy'  => array('fullName' => 'ASC'),
                    ),
                ),
            ],
        ]);
    }

    public function addSelectEntites($name, $label, $targetClass, $emtyItemLabel = null, $property = 'name', $labelClass = 'label', $id = null, $elementClass = 'form-control', $isMethod = true)
    {
        if ('emtyItemLabel' === null) 
             $emtyItemLabel = '--- ' . $label . ' ---';
        $this->add([
            'name' => $name,
            'type' => 'DoctrineModule\Form\Element\ObjectSelect',
            'attributes' => [
                'class' => $elementClass,
                'id' => $id,
           ],
           'options' => [
                'label' => $label,
                'label_attributes' => [
                    'class' => $labelClass,
                ],
                'object_manager' => $this->entityManager,
                'target_class' => $targetClass,
                'property' => $property,
                'display_empty_item' => true,
                'empty_item_label' => $emtyItemLabel,
                'is_method' => $isMethod,
                'find_method' => array(
                    'name'   => 'findBy',
                    'params' => array(
                        'criteria' => array(),
                        'orderBy'  => array('name' => 'ASC'),
                    ),
                ),
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

    public function addPictureUpload($name, $id, $label, $num = null, $required = false)
    {
        $this->add([
            'name' => $name . $num,
            'type' => 'Zend\Form\Element\File',
            'attributes' => [
                'id' => $id . $num,
                'required' => $required,
            ],
            'options' => [
                'label' => $label,
                'label_attributes' => array(
                    'class' => 'label'
                ),
            ]
        ]);

        $this->add([
            'name' => 'xStartCrop' . $num,
            'type' => 'Zend\Form\Element\Hidden',
            'attributes' => [
                'id' => 'x-start-crop' . $num,
            ],
        ]);

        $this->add([
            'name' => 'yStartCrop' . $num,
            'type' => 'Zend\Form\Element\Hidden',
            'attributes' => [
                'id' => 'y-start-crop' . $num,
            ],
        ]);

        $this->add([
            'name' => 'widthCrop' . $num,
            'type' => 'Zend\Form\Element\Hidden',
            'attributes' => [
                'id' => 'width-crop' . $num,
            ],
        ]);

        $this->add([
            'name' => 'heightCrop' . $num,
            'type' => 'Zend\Form\Element\Hidden',
            'attributes' => [
                'id' => 'height-crop' . $num,
            ],
        ]);

        $this->add([
            'name' => 'widthCurrent' . $num,
            'type' => 'Zend\Form\Element\Hidden',
            'attributes' => [
                'id' => 'width-current' . $num,
            ],
        ]);

        $this->add([
            'name' => 'heightCurrent' . $num,
            'type' => 'Zend\Form\Element\Hidden',
            'attributes' => [
                'id' => 'height-current' . $num,
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