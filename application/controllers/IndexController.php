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

class IndexController extends Zend_Controller_Action
{

  private $_thumbnails = array(
    array(
      'append' => 'p',
      'width'  => 40,
      'height' => 60
    ),
    array(
      'append' => 'g',
      'width'  => 400,
      'height' => 300
    )
  );

  public function init()
  {
    /* Initialize action controller here */
  }

  public function indexAction()
  {
    $form = new Application_Form_Example();
    $this->view->form = $form;
  }

  public function createAction()
  {
    $form = new Application_Form_Example();
    if ($form->isValid($this->getRequest()->getPost())) {
      $names = $this->getHelper('FileUpload')->getUploadNames(
        $form->uploader_count->getValue(),
        realpath(APPLICATION_PATH . "/../public/files"),
        realpath(APPLICATION_PATH . "/../public/files/temp"),
        $this->_thumbnails);

      echo '<pre>';
      print_r($names);
      echo '</pre>';
    } else {
      $this->view->form = $form;
      return $this->render('index');
    }
    echo '<pre>';
    print_r($_REQUEST);
    echo '</pre>';
  }

  public function editAction()
  {
    //simulando dados vindo do banco de dados
    $row = array(
      array('img' => '201208/p173soqujqthsgli15i31avc18dm3.jpg','caption' => 'Teste1'),
      array('img' => '201208/p173v7ssb716cgfnootnmn51jsj3g.jpg','caption' => 'Teste2'),
    );

    $form = new Application_Form_Example();
    if ($this->getRequest()->isPost()) {
      if ($form->isValid($this->getRequest()->getPost())) {
        $names = $this->getHelper('FileUpload')->getUploadNames(
          $form->uploader_count->getValue(),
          realpath(APPLICATION_PATH . "/../public/files"),
          realpath(APPLICATION_PATH . "/../public/files/temp"),
          $this->_thumbnails);

        echo '<pre>';
        print_r($names);
        echo '</pre>';
        echo '<pre>';
        print_r($_REQUEST);
        echo '</pre>';
        return $this;
      } else {

      }
    } else {
      $form->populate(array(
        'name'  => 'Leandro Sales',
        'email' => 'leandroasp@gmail.com'
      ));
    }

    $this->view->thumbnails = $this->getHelper('FileUpload')->getEditUploaded($row, array('name' => 'img'));

    $this->view->form = $form;
  }

  public function uploadAction()
  {
    $this->getHelper('layout')->disableLayout();
    $this->getHelper('viewRenderer')->setNoRender();

    return $this->getHelper('FileUpload')->makeUpload(array(
      'targetDir' => realpath(APPLICATION_PATH . "/../public/files/temp"),
      'cleanupTargetDir' => true,
      'maxFileAge' => 3600
    ));
  }
}
