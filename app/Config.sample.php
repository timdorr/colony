<?php

$CONFIG['db_type'] = 'MySQL';
$CONFIG['db_host'] = 'localhost';
$CONFIG['db_user'] = 'user';
$CONFIG['db_pass'] = 'password';
$CONFIG['db_name'] = 'asoworx';

$CONFIG['session_type'] = "Db";
$CONFIG['session_timeout'] = 3600;
$CONFIG['session_domain'] = '';
$CONFIG['session_path'] = '/';

$CONFIG['display_backend'] = "Smarty";

$CONFIG['throw_exceptions'] = true;    // defaults to true if unset, set to false if you are in a production environment
$CONFIG['email_exceptions'] = false;   // defaults to false if unset, set to true and configure email_exceptions_address if you are in a production environment
$CONFIG['email_exceptions_address'] = 'errors@test.com';    // the email address that should receive error emails
$CONFIG['log_exceptions'] = false;     // defaults to true if unset, logs errors to var/log/exceptions.log
