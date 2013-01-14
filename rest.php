<?php
function returnJson($data) {
  return $_REQUEST['callback'].'('.json_encode(utf8_encode($data)).')';
}
//error_reporting(0);
//echo $_REQUEST['callback'].'('.json_encode(array()).')';
if (!empty($_REQUEST['callback'])) {
  $get = $_REQUEST;
  $get['_'] = null;
  $get['callback'] = null;
  $get['url'] = null;
  $query = http_build_query($get);
  $data = file_get_contents($_REQUEST['url'].'?'.$query);
  echo returnJson($data);
  //file_put_contents('restdata/cached.js', returnJson($data));
  //header('Location: restdata/cached.js');
}
?>