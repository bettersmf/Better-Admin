<?php

if (!defined('SMF'))
    die('No direct access...');

global $smcFunc, $sourcedir;

require_once($sourcedir . '/Subs-Admin.php');
db_extend('packages');

$columns = array(
    array('name' => 'id_topic', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'null' => false),
    array('name' => 'id_board', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'null' => false, 'default' => 0),
    array('name' => 'id_member', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'null' => false, 'default' => 0),
    array('name' => 'review_time', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'null' => false, 'default' => 0),
);
$indexes = array(
    array('type' => 'primary', 'columns' => array('id_topic')),
    array('type' => 'index', 'columns' => array('id_board')),
);

$smcFunc['db_create_table']('{db_prefix}better_admin_topic_review', $columns, $indexes, array(), 'ignore');


$message_columns = array(
    array('name' => 'id_msg', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'null' => false),
    array('name' => 'id_topic', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'null' => false, 'default' => 0),
    array('name' => 'id_board', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'null' => false, 'default' => 0),
    array('name' => 'id_member', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'null' => false, 'default' => 0),
    array('name' => 'review_time', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'null' => false, 'default' => 0),
);
$message_indexes = array(
    array('type' => 'primary', 'columns' => array('id_msg')),
    array('type' => 'index', 'columns' => array('id_topic')),
    array('type' => 'index', 'columns' => array('id_board')),
);

$smcFunc['db_create_table']('{db_prefix}better_admin_message_review', $message_columns, $message_indexes, array(), 'ignore');

$package_order_columns = array(
    array('name' => 'id_order', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true, 'null' => false),
    array('name' => 'package_filename', 'type' => 'varchar', 'size' => 255, 'null' => false, 'default' => ''),
    array('name' => 'package_type', 'type' => 'varchar', 'size' => 32, 'null' => false, 'default' => 'modification'),
    array('name' => 'sort_order', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'null' => false, 'default' => 0),
    array('name' => 'first_seen', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'null' => false, 'default' => 0),
);
$package_order_indexes = array(
    array('type' => 'primary', 'columns' => array('id_order')),
    array('type' => 'unique', 'columns' => array('package_type', 'package_filename')),
    array('type' => 'index', 'columns' => array('package_type', 'sort_order')),
);

$smcFunc['db_create_table']('{db_prefix}better_admin_package_order', $package_order_columns, $package_order_indexes, array(), 'ignore');


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
    add_integration_function($hook[0], $hook[1], true, '$sourcedir/BetterAdmin.php');
