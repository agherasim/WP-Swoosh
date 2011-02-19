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
 * @version 0.1
 */
/**
 * Register include paths
 * @param string $path
 */
function addIncludePath($path) {
  set_include_path(get_include_path() . PATH_SEPARATOR . $path);
}
/**
 * Set include paths for Zend, Swoosh and commands
 */
addIncludePath(dirname(__FILE__) . '/lib');
addIncludePath(dirname(__FILE__) . '/command');
addIncludePath('~/.swoosh/command');
/**
 * Define autoloading method for commands
 */
function __autoload($className) {
  try {
    if (strpos($className, 'Command') === 0) {
      include 'command/' . $className . '.php';
    } else {
      include str_replace('_', '/', $className) . '.php';
    }
  } catch (Exception $e) {
    echo $e->getMessage(), "\n";
  }
}

$swoosh = Swoosh_Core::getInstance()->run();