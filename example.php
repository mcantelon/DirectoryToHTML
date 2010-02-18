<?php

session_start();

include('include/directory_to_html.inc.php');

if (array_key_exists('toggle', $_GET) && $_GET['toggle']) {

  // make sure state session variable is initialized
  $_SESSION['state'] = ($_SESSION['state']) ? $_SESSION['state'] : array();

  // toggle state
  $path = ($_GET['path'] && $_GET['path'] != '/') ? $_GET['path'] : '';
  $path .= $_GET['toggle'];
  $_SESSION['state'][$path] = !$_SESSION['state'][$path];
}

// instantiate at current working directory
try {

  $directory = new DirectoryToHTML(
    getcwd(),
    getcwd() .'/'. $_GET['dir'],
    array(
      'directory_prefix_html' => '<a href="{script}?dir='. urlencode($_GET['dir']) .'&path={path}&toggle={entry}"><img src="images/folder_{state}.png"></a><a href="{script}?dir={path}{entry}"><img src="images/folder-icon.png" border="0">&nbsp;',
      'directory_suffix_html' => '</a>',
      'default_state' => 'closed',
      'state' => $_SESSION['state']
    )
  );

  include('template/example.html');
} catch (Exception $e) {

  print 'Error: '. $e->getMessage();
}

?>
