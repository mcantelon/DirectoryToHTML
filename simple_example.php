<?php

include('include/directory_to_html.inc.php');

// instantiate at current working directory
try {

  $directory = new DirectoryToHTML(
    getcwd(),
    getcwd() .'/'. $_GET['dir'],
    array(
      'directory_prefix_html' => '<a href="{script}?dir={path}{entry}"><img src="images/folder-icon.png" border="0">&nbsp;',
      'directory_suffix_html' => '</a>'
    )
  );

  include('template/example.html');
} catch (Exception $e) {

  print 'Error: '. $e->getMessage();
}

?>
