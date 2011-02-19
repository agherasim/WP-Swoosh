<?php
/**
 * Command line utility for managing worpdress installations
 * Copyright (C) 2010  Andrei Gherasim <gherasimandrei84@gmail.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Swoosh_Core
 * @package Swoosh
 * @version 0.1
 */

class Swoosh_Core implements SplSubject {
  /**
   * Declare constants
   */
  const VERSION = '0.1';
  /**
   * Instance
   * @var Swoosh
   */
  private static $instance = null;
  /**
   * Contains all commands
   * @var array
   */
  protected $commands = array();
  /**
   * Contains defined rules by commands
   * @var array
   */
  protected $rules = array();
  /**
   * Console instance
   * @var Zend_Console_Getopt $console
   */
  protected $console = null;
  /**
   * Event queue
   * @var SplQueue $eventQueue
   */
  protected $eventQueue = null;
  /**
   * 
   */
  protected $event = null;
  /**
   *
   */
  protected $eventArguments = null;
  /**
   * Attach a Command to the event observers list
   * @param Swoosh_Base_Command_Abstract $obj
   * @return Swoosh
   */
  public function attach(SplObserver $obj) {
    $this->commands[get_class($obj)] = $obj;
    $this->getConsole()->addRules($obj->getRules());

    return $this;
  }

  /**
   * Detach a Command from the event observer list
   * @param Swoosh_Base_Command_Abstract $obj
   * @return Swoosh
   */
  public function detach(SplObserver $obj) {
    unset($this->commands[get_class($obj)]);
    return $this;
  }

  /**
   * Return console
   * @return Zend_Console_Getopt
   */
  public function getConsole() {
    if (is_null($this->console)) {
      $this->console = new Zend_Console_Getopt('');
    }
    return $this->console;
  }

  /**
   * Return the event queue
   * @return SplQueue
   */
  public function getEventQueue() {
    if (is_null($this->eventQueue)) {
      $this->eventQueue = new ArrayIterator();
    }

    return $this->eventQueue;
  }

  public function setEvent($value) {
    $this->event = $value;
  }

  public function getEvent() {
    return $this->event;
  }

  public function setEventArguments(Array $value) {
    $this->eventArguments = $value;
  }

  public function getEventArguments() {
    return $this->eventArguments;
  }

  /**
   * Execute Swoosh
   */
  public function run() {
    try {
      // let's load all available commands
      $this->load();
      $this->eventArguments = $this->getConsole()->getRemainingArgs();
      // check if we passed at least 1 option or argument
      if (count($this->getConsole()->getOptions()) === 0 && count($this->eventArguments) === 0) {
        throw new Zend_Console_Getopt_Exception('!!! This command need at least one argument/option.', $this->getConsole()->getUsageMessage());
      }

      if (isset($this->getConsole()->h)) {
        Swoosh_Print::ln('Swoosh - Wordpress Shell, version', self::VERSION);
        Swoosh_Print::ln($this->getConsole()->getUsageMessage(), "\n", 'Available commands:');
        $this->getEventQueue()->append('help');
      } else {
        $this->getEventQueue()->append($this->eventArguments[0]);
      }
      while ($this->getEventQueue()->valid()) {
        $this->notify();
        $this->getEventQueue()->next();
      }
    } catch (Zend_Console_Getopt_Exception $e) {
      Swoosh_Print::ln($e->getMessage(), "\n", $e->getUsageMessage());
      exit(1);
    }
  }

  /**
   * Return the Swoosh instance
   * @return Swoosh_Core
   */
  public static function getInstance() {
    if (is_null(self::$instance)) {
      $c = __CLASS__;
      self::$instance = new $c;
    }
    return self::$instance;
  }

  /**
   * Notify all event observers of an event - usually a single Command should respond
   * to a message
   * @param string $eventName
   * @return Swoosh
   */
  public function notify() {
    $hasListner = false;
    /**
     * @var array
     */
    foreach ($this->commands as $command) {
      /**
       * @var Swoosh_Base_Command_Abstract
       */
      $return = $command->update($this);

      if ($return) {
        $hasListner = true;
      }
    }

    if (!$hasListner) {
      throw new Zend_Console_Getopt_Exception('!!! No event listner for event' . $extraArguments[0] . "\n");
    }
  }

  /**
   * Load all Commands from the commands directory and attaches them as event observers
   * @return Swoosh
   */
  protected function load() {
    $availableCommands = glob('command/Command_*.php');
    if (!empty($availableCommands)) {
      foreach ($availableCommands as $availableCommand) {
        $commandClassName = str_replace('.php', '', basename($availableCommand));
        $this->attach(new $commandClassName());
      }
    }

    return $this;
  }

  /**
   * This is a singleton, so the constructor is private
   */
  private function __construct() {
    $this->getConsole();
    $this->getConsole()->addRules(
      array(
          'help|h'    => 'Print this help message',
          'quiet|q'   => 'Don\'t print any output',
          'yes|y'     => 'Assume yes for all steps',
          'verbose|v' => 'Print more output',
          'debug'     => 'Print debug output',
      )
    );
    $this->getEventQueue();
  }
}