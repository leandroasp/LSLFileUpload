<?php
class IndexController extends Zend_Controller_Action
{
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
      $thumbnails = array(
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
      $names = $this->getHelper('FileUpload')->getUploadNames(
        $form->uploader_count->getValue(),
        realpath(APPLICATION_PATH . "/../public/files"),
        realpath(APPLICATION_PATH . "/../public/files/temp"),
        $thumbnails);

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
