<?php

if (!defined('SMF'))
    die('No direct access...');

global $smcFunc, $sourcedir;

require_once($sourcedir . '/Subs-Admin.php');
db_extend('packages');

$columns = array(
    array('name' => 'id_msg', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'null' => false),
    array('name' => 'id_topic', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'null' => false, 'default' => 0),
    array('name' => 'id_board', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'null' => false, 'default' => 0),
    array('name' => 'id_member', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'null' => false, 'default' => 0),
    array('name' => 'review_time', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'null' => false, 'default' => 0),
);
$indexes = array(
    array('type' => 'primary', 'columns' => array('id_msg')),
    array('type' => 'index', 'columns' => array('id_topic')),
    array('type' => 'index', 'columns' => array('id_board')),
);

$smcFunc['db_create_table']('{db_prefix}better_admin_message_review', $columns, $indexes, array(), 'ignore');

add_integration_function('integrate_query_message', 'betteradmin_filter_reviewed_messages', true, '$sourcedir/BetterAdmin.php');
add_integration_function('integrate_remove_message', 'betteradmin_remove_message_review', true, '$sourcedir/BetterAdmin.php');
add_integration_function('integrate_actions', 'betteradmin_actions', true, '$sourcedir/BetterAdmin.php');
add_integration_function('integrate_load_theme', 'betteradmin_load_topic_assets', true, '$sourcedir/BetterAdmin.php');
add_integration_function('integrate_prepare_display_context', 'betteradmin_prepare_display_context', true, '$sourcedir/BetterAdmin.php');
