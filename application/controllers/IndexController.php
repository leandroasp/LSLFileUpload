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
      $names = $this->getHelper('FileUpload')->getUploadNames(
        $form->uploader_count->getValue(),
        realpath(APPLICATION_PATH . "/../public/files"),
        realpath(APPLICATION_PATH . "/../public/files/temp"));
      
    } else {
      $this->view->form = $form;
      return $this->render('index');
    }
    print_r($_REQUEST);
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