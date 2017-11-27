<?php
// upload_max_filesize and post_max_size must be set in php.ini
require_once('config.php');


# https://gist.github.com/tott/7684443
function ip_in_range($ip, $cidrs, &$match = null) {
  foreach((array) $cidrs as $cidr) {
    list($subnet, $mask) = explode('/', $cidr);
    if(((ip2long($ip) & ($mask = ~ ((1 << (32 - $mask)) - 1))) == (ip2long($subnet) & $mask))) {
      $match = $cidr;
      return true;
    }
  }
  return false;
}

function gen_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
        mt_rand( 0, 0xffff ),
        mt_rand( 0, 0x0fff ) | 0x4000,
        mt_rand( 0, 0x3fff ) | 0x8000,
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}

function check_remote_ip($whitelist) { 
	$found = 0;
  if(!ip_in_range($_SERVER['REMOTE_ADDR'], $whitelist)) {
    header("HTTP/1.1 403 Forbidden");
    echo "{'status':'unauthorized'}";
    die();
  }
}

check_remote_ip($whitelist);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $results = array();
  foreach ($_FILES as $key => $value) {
    $uuid = gen_uuid();
    $orig_filename = preg_replace('/[^a-zA-Z0-9-.]/', '_', $value['name']);
    $actual_link = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    move_uploaded_file($value['tmp_name'], $upload_dir . '/' . $uuid . "." . $orig_filename);
    array_push($results, $actual_link . '?d=' . $uuid . "." . $orig_filename);
  }
  foreach ($results as $key => $value) {
    echo "{'status':'success','link': " . $value . "','expires':'now+30days'}\n";
  }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['d'])) {
  $uuid = preg_replace('/[^a-zA-Z0-9-.]/', '_', $_GET['d']);
  $filename = realpath($upload_dir . '/' . $uuid);
  print_r($filename);
  if(file_exists($filename)) {
    # prevent Allowed memory size exhausted
    if (ob_get_level()) {
      ob_end_clean();
    }
    # https://secure.php.net/manual/en/function.readfile.php
    header('Content-Description: File Transfer');
    header('Content-Type: ' .  mime_content_type($filename));
    header('Content-Disposition: attachment; filename="' . $uuid . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filename));
    readfile($filename);
    die();
  } else {
    header("HTTP/1.1 403 Forbidden");
    echo "{'status':'unauthorized'}";
    die();
  }
} 
?>
