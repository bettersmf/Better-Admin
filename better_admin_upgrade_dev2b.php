<?php

if (!defined('SMF'))
    die('No direct access...');

global $sourcedir;

require_once($sourcedir . '/Subs-Admin.php');
add_integration_function('integrate_prepare_display_context', 'betteradmin_prepare_display_context', true, '$sourcedir/BetterAdmin.php');
