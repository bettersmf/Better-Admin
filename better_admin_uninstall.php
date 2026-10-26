<?php

if (!defined('SMF'))
    die('No direct access...');

$hooks = array(
    array('integrate_pre_messageindex', 'betteradmin_pre_messageindex'),
    array('integrate_message_index', 'betteradmin_message_index_query'),
    array('integrate_quick_mod_actions', 'betteradmin_quick_mod_actions'),
    array('integrate_messageindex_buttons', 'betteradmin_messageindex_finalize'),
    array('integrate_actions', 'betteradmin_actions'),
    array('integrate_display_topic', 'betteradmin_display_topic_guard'),
    array('integrate_remove_topics', 'betteradmin_remove_topics'),
    array('integrate_load_theme', 'betteradmin_load_topic_assets'),
    array('integrate_prepare_display_context', 'betteradmin_prepare_display_context'),
    array('integrate_query_message', 'betteradmin_filter_reviewed_messages'),
    array('integrate_remove_message', 'betteradmin_remove_message_review'),
    array('integrate_manage_packages', 'betteradmin_manage_packages'),
    array('integrate_manage_themes', 'betteradmin_manage_themes'),
);

foreach ($hooks as $hook)
    remove_integration_function($hook[0], $hook[1], true, '$sourcedir/BetterAdmin.php');
