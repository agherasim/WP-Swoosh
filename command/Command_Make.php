<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Command_Make extends Swoosh_Base_Command_Abstract {

  public function __construct() {
    $this->command = 'make';
  }

  public function helpAction($eventName, $extraArguments) {
    Swoosh_Print::ln("\t", 'make: use a make file to create a new project');
  }

  public function getRules() {
    return array(
      
    );
  }

  protected function makeFile() {
    $swoosh = Swoosh::getInstance();
    $makeConfig = json_decode(file_get_contents($this->commandArguments[1]));

    if (is_object($makeConfig)) {
      
       
    } else {
      throw new Exception('Invalid make file');
    }
  }

}