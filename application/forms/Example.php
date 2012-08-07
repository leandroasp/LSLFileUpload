<?php
/**
 * LSL FileUpload released under LGPL
 * Author: Leandro Sales <leandroasp@gmail.com>
 * https://github.com/leandroasp/LSLFileUpload
 * Copyright (c) 2012
 * -------------------------------------------------------------------
 * This file is part of LSL FileUpload.
 *
 * LSL FileUpload is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * LSL FileUpload is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with LSL FileUpload.  If not, see <http://www.gnu.org/licenses/>.
 */

class Application_Form_Example extends Zend_Form
{
  private $_id = -1;

  public function init()
  {
    $name = new Zend_Form_Element_Text('name');
    $name->setRequired(true)
         ->setOptions(array('maxlength' => 50, 'class' => 'span6'))
         ->setDecorators(array(
           'ViewHelper',
           array('Errors', array('class' => 'unstyled my-errors'))))
         ->setLabel('Nome:');

    $email = new Zend_Form_Element_Text('email');
    $email->setRequired(true)
          ->setOptions(array('maxlength' => 100, 'class' => 'span6'))
          ->setDecorators(array(
            'ViewHelper',
            array('Errors', array('class' => 'unstyled my-errors'))))
          ->setLabel('e-mail:');

    $count = new Zend_Form_Element_Text('my_upload_count');
    $count->setRequired(true)
          ->setIgnore(true)
          ->setDecorators(array(array('Errors', array('class' => 'unstyled my-errors'))))
          ->addValidator('GreaterThan', false, array('min' => 0, 'messages' => array('notGreaterThan' => 'Send at least one file')));

    $this->setMethod('post')
         ->addElements(array($name, $email, $count));

    return $this;
  }
  
  public function setId($id)
  {
    $this->_id = $id;
  }
}
