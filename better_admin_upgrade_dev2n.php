<?php

if (!defined('SMF'))
    die('No direct access...');

global $sourcedir;

require_once($sourcedir . '/Subs-Admin.php');

$hooks = array(
    array('integrate_actions', 'betteradmin_actions'),
    array('integrate_load_theme', 'betteradmin_load_topic_assets'),
    array('integrate_prepare_display_context', 'betteradmin_prepare_display_context'),
    array('integrate_query_message', 'betteradmin_filter_reviewed_messages'),
    array('integrate_remove_message', 'betteradmin_remove_message_review'),
);

foreach ($hooks as $hook)
    add_integration_function($hook[0], $hook[1], true, '$sourcedir/BetterAdmin.php');
