<?php
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
  public function _initHelpers()
  {
    Zend_Controller_Action_HelperBroker::addPath(APPLICATION_PATH . '/controllers/helpers');
  }

}