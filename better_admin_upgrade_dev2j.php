<?php

if (!defined('SMF'))
    die('No direct access...');

global $sourcedir;

require_once($sourcedir . '/Subs-Admin.php');

// Stage 2B assets must be registered before templates output the page head.
add_integration_function('integrate_load_theme', 'betteradmin_load_topic_assets', true, '$sourcedir/BetterAdmin.php');

// Keep the Stage 2A per-post action hook in place as well.
add_integration_function('integrate_prepare_display_context', 'betteradmin_prepare_display_context', true, '$sourcedir/BetterAdmin.php');
