<?php

class Application_Form_Example extends Zend_Form
{
  public function init()
  {
    $name = new Zend_Form_Element_Text('name');
    $name->setRequired(true)
         ->setOptions(array('maxlength' => 50))
         ->setDecorators(array(array('Errors', array('class' => 'unstyled my-errors'))))
         ->setLabel('Nome:');

    $email = new Zend_Form_Element_Text('email');
    $email->setRequired(true)
          ->setOptions(array('maxlength' => 100))
          ->setDecorators(array(array('Errors', array('class' => 'unstyled my-errors'))))
          ->setLabel('e-mail:');

    $count = new Zend_Form_Element_Text('uploader_count');
    $count->setRequired(true)
          ->setIgnore(true)
          ->setDecorators(array(array('Errors', array('class' => 'unstyled my-errors'))))
          ->addValidator('GreaterThan', false, array('min' => 0));

    $this->setMethod('post')
         ->addElements(array($name, $email, $count));

    return $this;
  }
}