<?php

if (!defined('SMF'))
    die('No direct access...');

global $sourcedir;

require_once($sourcedir . '/Subs-Admin.php');

add_integration_function('integrate_load_theme', 'betteradmin_load_topic_assets', true, '$sourcedir/BetterAdmin.php');
add_integration_function('integrate_prepare_display_context', 'betteradmin_prepare_display_context', true, '$sourcedir/BetterAdmin.php');
add_integration_function('integrate_actions', 'betteradmin_actions', true, '$sourcedir/BetterAdmin.php');
