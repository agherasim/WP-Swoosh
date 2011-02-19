<?php

abstract class Swoosh_Base_Command_Abstract implements SplObserver {

  abstract public function getRules();

  abstract public function helpAction($eventName, $extraArguments);

  public function update(SplSubject $subject) {
    $command = $subject->getEventQueue()->current();
    $method = $command . 'Action';

    if (method_exists($this, $method)) {
      $this->$method($command, $subject->getEventArguments());
      return true;
    } else {
      return false;
    }
  }
}
