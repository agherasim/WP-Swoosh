<?php

class Swoosh_Print {

  public static function ln() {
    $args = func_get_args();
    if (! empty($args)) {
      echo implode(' ', $args), "\n";
    }
  }

  public static function in() {
    $args = func_get_args();
    if (! empty($args)) {
      echo implode(' ', $args);
    }
  }

}