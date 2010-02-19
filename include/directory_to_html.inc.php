<?php

/*------------------------------------------------------------------------

Copyright 2010 Mike Cantelon
 
DirectoryToHTML is free software: you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the Free
Software Foundation, either version 3 of the License, or (at your option)
any later version.
 
Usermine is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
more details.
 
You should have received a copy of the GNU General Public License along
with Usermine. If not, see http://www.gnu.org/licenses/.

-------------------------------------------------------------------------*/

class DirectoryToHTML {

  function __construct($base_path, $start_path, $options = array()) {

    // throw exception if paths aren't legit
    $this->base_path            = $this->_check_path($base_path);
    $this->start_path           = $this->_check_path($start_path);

    $this->relative_path        = $this->absolute_to_relative_path($start_path);
    $this->parent_relative_path = $this->_parent_relative_path();

    // set things up according to specified options
    $this->options = $options;
    $this->_process_options();

    // load directory tree
    $this->tree = $this->directory_tree_as_array($this->start_path);
  }

  function directory_tree_as_array($current_directory, $current_depth = 0) {

    $directory_array = array();

    foreach($this->directory_as_array($current_directory) as $entry) {

      $absolute_path = $this->_normalize_path($current_directory) .'/'. $entry;

      $directory_array[$entry] = array(
        'type' => ((is_dir($absolute_path)) ? 'directory' : 'file')
      );

      // traverse directory if it's not set to be closed
      $directory_array[$entry]['children'] = (is_dir($absolute_path) && $this->_directory_should_be_traversed($absolute_path))
        ? $this->directory_tree_as_array($absolute_path, $current_depth + 1)
        : TRUE;
    }

    return $directory_array;
  }

  function directory_as_array($directory) {

    $directory_entries = array();

    if ($handle = opendir($directory)) {

      while (false !== ($file = readdir($handle))) {

        if ($file != '.' && $file != '..') {
          $directory_entries[] = $file;
        }
      }

      closedir($handle);
    }

    return $directory_entries;
  }

  function as_html() {

    return $this->render_directory_array($this->tree);
  }

  function render_directory_array($directory_array, $current_depth = 0, $current_path = '') {

    $output = '';

    foreach($directory_array as $entry => $data) {

      // context is sent to tag replacement functions
      $relative_path_to_entry = ($this->relative_path) ? $this->relative_path .'/' : '';

      $context = array(
        'entry' => $entry,
        'path' => $relative_path_to_entry
      );

      if ($data['type'] == 'directory') {
        $absolute_path = $this->_normalize_path($this->start_path) .'/';
        $absolute_path .= ($current_path) ? $current_path : '';
        $absolute_path .= $entry;
        $context['state'] = ($this->_directory_should_be_traversed($absolute_path)) ? 'open' : 'closed';
      }

      // add intendation
      for($index = 0; $index < $current_depth; $index++) {

        $output .= $this->_replace_tags($this->options['indent_html'], $context);
      }

      // add prefix, if displaying a directory
      if ($data['type'] == 'directory') {

        $output .= $this->_replace_tags($this->options['directory_prefix_html'], $context);
      }

      // display entry name and break
      $output .= $entry . $this->_replace_tags($this->options['break_html'], $context);

      // add suffix and display children, if displaying a directory
      if ($data['type'] == 'directory') {

        $output .= $this->_replace_tags($this->options['directory_suffix_html'], $context);

        if (sizeof($data['children']) && $this->_directory_should_be_traversed($absolute_path)) {
          $output .= $this->render_directory_array($data['children'], $current_depth + 1, $current_path . $entry .'/');
        }
      }
    }

    return $output;
  }

  // remove part of path shared between base_path and a child path
  function absolute_to_relative_path($path) {

    $base_path_length = strlen($this->_normalize_path($this->base_path));

    $rel_path_length = strlen($this->_normalize_path($path)) - $base_path_length;

    return substr($this->_normalize_path($path), $base_path_length + 1, $rel_path_length); exit();
  }

  private function _check_path($path) {

    if (!is_dir($path)) throw new Exception('Path does not exist.');

    if (is_numeric(strpos($path, '..'))) throw new Exception('Path contains tomfoolery.');

    return $path;
  }

  private function _process_options() {

    $defaults = array(
      'default_state' => 'open',
      'indent_html' => '&nbsp;&nbsp;&nbsp;&nbsp;',
      'break_html' => "<br/>",
      'directory_prefix_html' => '<img src="images/folder-icon.png">&nbsp;'
    );

    // use defaults to set options
    foreach($defaults as $option => $default) {

      // if user has specified an option, use it... otherwise use default
      $this->options[$option] = (array_key_exists($option, $this->options))
        ? $this->options[$option]
        : $default;
    }

    // directory state allows user to set which directories should be
    // traversed so make note of which directories should be open/closed
    // (whether open or closed depends on default_state option)
    $this->_initialize_state($this->options['state']);
  }

  private function _initialize_state($initial_state = array()) {

    // make directory state use absolute paths
    $this->state = array();

    // set state according to user specification
    foreach($initial_state as $relative_path => $state) {

      if ($state) {
        $this->state[$this->_normalize_path($this->start_path) .'/'. $relative_path] = TRUE;
      }
    }
  }

  private function _directory_should_be_traversed($absolute_path) {

    return ($this->options['default_state'] == 'open' && !(array_key_exists($absolute_path, $this->state)))
      || ($this->options['default_state'] == 'closed' && array_key_exists($absolute_path, $this->state));
  }

  private function _replace_tags($text, $context = array()) {

    foreach($context as $tag => $value) {

      $text = (isset($context[$tag])) ? str_replace('{'. $tag .'}', $context[$tag], $text) : $text;
    }

    $text = str_replace('{parent}', $this->_parent_relative_path, $text);

    return str_replace('{script}', basename($_SERVER["SCRIPT_NAME"]), $text);
  }

  private function _parent_relative_path() {

    $path_info = pathinfo($this->start_path);

    $parent_path = $path_info['dirname'];

    return $this->absolute_to_relative_path($parent_path);
  }

  // removes trailing slash
  private function _normalize_path($path) {

    $path_info = pathinfo($path);

    return $path_info['dirname'] .'/'. $path_info['basename'];
  }
}

?>
