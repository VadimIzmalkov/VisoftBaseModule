<?php

namespace VisoftBaseModule\Form;

use Zend\Form\Form;

use Doctrine\ORM\EntityManager;

class BaseForm extends Form 
{
    private $title;
    private $formType;
	protected $entityManager;

	public function __construct(EntityManager $entityManager)
	{
		parent::__construct();
        $this->entityManager = $entityManager;
    }

    public function getTitle() { return $this->title; }
    public function setTitle($title) { $this->title = $title; }

    public function getFormType() { return $this->formType; }
    public function setFormType($formType) { $this->formType = $formType; }

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

    public function addPassword($name, $label = null, $labelClass = 'label', $id = null, $required = false, $placeholder = null, $disabled = false, $readonly = false, $elementClass = 'form-control')
    {
        $this->add([
            'name' => $name,
            'type' => 'Zend\Form\Element\Password',
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

    public function addHidden($name, $id = null, $value = null, $attributes = null)
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

    public function addSelectVenue($name, $id = null, $label = "Select Venue", $required = false)
    {
        $this->add([
            'name' => $name,
            'attributes' => [
                'class' => 'form-control',
                'id' => $id,
                'required' => $required,
            ],
            'type' => 'DoctrineModule\Form\Element\ObjectSelect',
            'options' => array(
                'label' => $label,
                'label_attributes' => [
                    'class' => 'label',
                ],
                'object_manager' => $this->entityManager,
                'target_class' => 'Admin\Entity\VenuePartner',
                'property' => 'name',
                'display_empty_item' => true,
                'empty_item_label' => '-- Select Venue --',
                'is_method' => true,
                'find_method' => array(
                    'name'   => 'findBy',
                    'params' => array(
                        'criteria' => array(),
                        'orderBy'  => array('name' => 'ASC'),
                    ),
                ),
            ),
        ]); 
    }

    public function addRadioEventType($name, $id = null, $label = "Select event type", $required = false)
    {
        $this->add([
            'name' => $name,
            'attributes' => [
                'id' => $id,
                'required' => $required,
            ],
            'type' => 'DoctrineModule\Form\Element\ObjectRadio',
            'options' => array(
                'label' => $label,
                'label_attributes' => [
                    'class' => 'radio fryday-radio'
                ],
                'object_manager' => $this->entityManager,
                'target_class' => 'Admin\Entity\EventType',
                'property' => 'name',
                'is_method' => true,
                'find_method' => array(
                    'name'   => 'findBy',
                    'params' => array(
                        'criteria' => array(),
                        'orderBy'  => array('name' => 'ASC'),
                    ),
                ),
            ),
        ]);
    }

    public function addSelectSpeaker($name, $id = null, $label = "Select speaker", $required = false)
    {
        $this->add([
            'name' => $name,
            'attributes' => [
                'class' => 'form-control',
                'id' => $id,
                'required' => $required,
            ],
            'type' => 'DoctrineModule\Form\Element\ObjectSelect',
            'options' => array(
                'label' => $label,
                'label_attributes' => [
                    'class' => 'label'
                ],
                'object_manager' => $this->entityManager,
                'target_class' => 'Admin\Entity\SpeakerPartner',
                'label_generator' => function($targetEntity) {
                    return $targetEntity->getFirstName() . ' ' . $targetEntity->getLastName();
                },
                'display_empty_item' => true,
                'empty_item_label' => '-- Select Speaker --',
                'is_method' => true,
                'find_method' => array(
                    'name'   => 'findBy',
                    'params' => array(
                        'criteria' => array(),
                        'orderBy'  => array('firstName' => 'ASC'),
                    ),
                ),
            ),
        ]);
    }

    public function addSelectCompany($name, $label = "Select company", $id = null, $required = false)
    {
        $this->add([
            'name' => $name,
            'attributes' => [
                'class' => 'form-control',
                'id' => $id,
                'required' => $required,
            ],
            'type' => 'DoctrineModule\Form\Element\ObjectSelect',
            'options' => array(
                'label' => $label,
                'label_attributes' => [
                    'class' => 'label'
                ],
                'object_manager' => $this->entityManager,
                'target_class' => 'Admin\Entity\SponsorPartner',
                'label_generator' => function($targetEntity) {
                    return $targetEntity->getName();
                },
                'display_empty_item' => true,
                'empty_item_label' => '-- Select Company --',
                'is_method' => true,
                'find_method' => array(
                    'name'   => 'findBy',
                    'params' => array(
                        'criteria' => array(),
                        'orderBy'  => array('name' => 'ASC'),
                    ),
                ),
            ),
        ]);
    }

    public function addSelectPartner($name) 
    {
        $this->add([
           'name' => $name,
           'type' => 'DoctrineModule\Form\Element\ObjectSelect',
           'attributes' => [
                'class' => 'form-control',
                'id' => 'partner-select',
           ],
           'options' => [
                'label' => 'Select Partner',
                'label_attributes' => [
                    'class' => 'label'
                ], 
                'object_manager' => $this->entityManager,
                'target_class' => 'Admin\Entity\FrydayPartner',
                'label_generator' => function($targetEntity) {
                    if($targetEntity instanceof \Admin\Entity\SpeakerPartner)
                        return $targetEntity->getFirstName() . ' ' . $targetEntity->getLastName();
                    else
                        return $targetEntity->getName();
                },
                'display_empty_item' => true,
                'empty_item_label' => '-- Select Partner --',
                'is_method' => true,
                'find_method' => array(
                    'name'   => 'findBy',
                    'params' => array(
                        'criteria' => array(),
                        'orderBy'  => array('slug' => 'ASC'),
                    ),
                ),
            ], 
        ]);
    }

    public function addCurrencySelect($name, $id = null, $label = 'Currency')
    {
        $this->add([
            'name' => $name,
            'attributes' => [
                'class' => 'form-control',
                'id' => $id,
            ],
            'type' => 'DoctrineModule\Form\Element\ObjectSelect',
            'options' => array(
                'label' => $label,
                'label_attributes' => [
                    'class' => 'label'
                ],
                'object_manager' => $this->entityManager,
                'target_class' => 'Fryday\Entity\Iso4217',
                'label_generator' => function($targetEntity) {
                    return $targetEntity->getCode() . ' - ' . $targetEntity->getCurrency();
                },
                'display_empty_item' => true,
                'empty_item_label' => '-- Select Currency --',
                'is_method' => true,
                'find_method' => array(
                    'name'   => 'findBy',
                    'params' => array(
                        'criteria' => array(),
                        'orderBy'  => array('code' => 'ASC'),
                    ),
                ),
            ),
        ]);
    }

    public function addSelectVenueType($name, $id = null, $required = false, $label = 'Select venue type')
    {
        $this->add([
           'name' => $name,
           'type' => 'DoctrineModule\Form\Element\ObjectSelect',
           'attributes' => [
                'class' => 'form-control',
                'id' => $id,
                'required' => $required,
           ],
           'options' => [
                'label' => $label,
                'label_attributes' => array(
                    'class' => 'label'
                ),
                'object_manager' => $this->entityManager,
                'target_class' => 'Admin\Entity\VenueType',
                'property' => 'name',
                'display_empty_item' => true,
                'empty_item_label' => '-- Select Venue Type --',
                'find_method'    => array(
                    'name'   => 'findBy',
                    'params' => array(
                        'criteria' => array(),
                        'orderBy'  => array('name' => 'ASC'),
                    ),
                ),
            ], 
        ]);
    }

    public function addSelectCity($name, $id = null, $required = false, $label = 'Select city', $elementClass = 'form-control')
    {
        $this->add([
           'name' => $name,
           'type' => 'DoctrineModule\Form\Element\ObjectSelect',
           'attributes' => [
                'class' => $elementClass,
                'id' => $id,
                'required' => $required,
           ],
           'options' => [
                'label' => $label,
                'label_attributes' => array(
                    'class' => 'label'
                ),
                'object_manager' => $this->entityManager,
                'target_class' => 'Fryday\Entity\City',
                'property' => 'name',
                'display_empty_item' => true,
                'empty_item_label' => '-- Select City --',
                'find_method'    => array(
                    'name'   => 'findBy',
                    'params' => array(
                        'criteria' => array(),
                        'orderBy'  => array('name' => 'ASC'),
                    ),
                ),
            ], 
        ]);
    }

    public function addCountrySelect($name, $id = null, $label = 'Select country')
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
                'label_attributes' => [
                    'class' => 'label',
                ], 
                'label_generator' => function($targetEntity) {
                    return $targetEntity->getName();
                },
                'object_manager' => $this->entityManager,
                'target_class' => 'Admin\Entity\Country',
                'display_empty_item' => true,
                'empty_item_label' => '-- Select Country --',
                'is_method' => true,
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

    public function addIndustrySelect($name, $id = null, $label = 'Select Industry')
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
                'label_attributes' => [
                    'class' => 'label',
                ], 
                'label_generator' => function($targetEntity) {
                    return $targetEntity->getName();
                },
                'object_manager' => $this->entityManager,
                'target_class' => 'Fryday\Entity\Industry',
                'display_empty_item' => true,
                'empty_item_label' => '-- Select Industry --',
            ], 
        ]);
    }

    public function addInterestSelect($name, $label = 'Why you are ineterested in Fryday', $id = null)
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
                'label_attributes' => [
                    'class' => 'label',
                ], 
                'label_generator' => function($targetEntity) {
                    return $targetEntity->getName();
                },
                'object_manager' => $this->entityManager,
                'target_class' => 'Fryday\Entity\Interest',
                'display_empty_item' => true,
                'empty_item_label' => '-- Why you are interest in Fryday --',
            ], 
        ]);
    }

    public function addCitiesMultiCheckbox($name, $id = null, $label = 'Select city', $labelClass = 'checkbox fryday-checkbox col-md-3 col-sm-4 col-xs-6')
    {
        $this->add([
            'name' => $name,
            'type' => 'DoctrineModule\Form\Element\ObjectMultiCheckbox',
            'attributes' => [
                'id' => $id,
            ],
            'options' => array(
                'label' => $label,
                'label_attributes' => array(
                    'class'  => $labelClass,
                ),
                'object_manager' => $this->entityManager,
                'target_class' => 'Fryday\Entity\City',
                'property' => 'name',
                'is_method' => true,
                'find_method' => array(
                    'name'   => 'findBy',
                    'params' => array(
                        'criteria' => array(),
                        'orderBy'  => array('name' => 'ASC'),
                    ),
                ),
            ),
        ]);
    }

    public function addSelectRole($name, $labelClass = 'label', $label = 'Select role', $id = null, $elementClass = 'form-control')
    {
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
                'target_class' => 'VisoftBaseModule\Entity\UserRole',
                'property' => 'name',
                'display_empty_item' => true,
                'empty_item_label' => '-- select role --',
                'is_method' => true,
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

    public function addSelectState($name, $labelClass = 'label', $label = 'Select state', $id = null, $elementClass = 'form-control')
    {
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
                'target_class' => 'VisoftMailerModule\Entity\ContactState',
                'property' => 'name',
                'display_empty_item' => true,
                'empty_item_label' => '-- select state --',
                'is_method' => true,
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

    public function addCall2ActionSelect($name)
    {
        $this->add([
           'name' => $name,
           'type' => 'DoctrineModule\Form\Element\ObjectSelect',
           'attributes' => [
                'class' => 'form-control',
                'id' => 'select-call-2-action',
           ],
           'options' => [
                'label' => 'Select call to action',
                'label_attributes' => [
                    'class' => 'label'
                ], 
                'object_manager' => $this->entityManager,
                'target_class' => 'Fryday\Entity\Call2Action',
                'label_generator' => function($targetEntity) {
                        return $targetEntity->getTitle();
                },
                'display_empty_item' => true,
                'empty_item_label' => '-- Select call to action --',
                'is_method' => true,
                'find_method' => array(
                    'name'   => 'findBy',
                    'params' => array(
                        'criteria' => array(),
                        'orderBy'  => array('title' => 'ASC'),
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
                'label' => $label . $num,
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

    public function addFile($name, $label = null, $id = null, $required = false, $labelClass = 'label', $multiple = false)
    {
        $this->add([
            'name' => $name,
            'type' => 'Zend\Form\Element\File',
            'attributes' => [
                'id' => $id,
                'required' => $required,
                'multiple' => $multiple,
            ],
            'options' => [
                'label' => $label,
                'label_attributes' => [
                    'class' => $labelClass,
                ],
            ]
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