<?php
// must be in CIDR format
$whitelist = array('192.0.2.0/24', '172.16.1.0/27');

// make sure this dir is outside of the public_html docroot.
// must be writable by the user. no trailing slash
$upload_dir = "/home/user/domains/example.org/uploads";