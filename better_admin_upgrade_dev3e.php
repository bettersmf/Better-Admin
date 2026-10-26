<?php

if (!defined('SMF'))
    die('No direct access...');

global $smcFunc, $sourcedir;

require_once($sourcedir . '/Subs-Admin.php');
db_extend('packages');

$columns = array(
    array('name' => 'id_order', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true, 'null' => false),
    array('name' => 'package_filename', 'type' => 'varchar', 'size' => 255, 'null' => false, 'default' => ''),
    array('name' => 'package_type', 'type' => 'varchar', 'size' => 32, 'null' => false, 'default' => 'modification'),
    array('name' => 'sort_order', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'null' => false, 'default' => 0),
    array('name' => 'first_seen', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'null' => false, 'default' => 0),
);
$indexes = array(
    array('type' => 'primary', 'columns' => array('id_order')),
    array('type' => 'unique', 'columns' => array('package_type', 'package_filename')),
    array('type' => 'index', 'columns' => array('package_type', 'sort_order')),
);

$smcFunc['db_create_table']('{db_prefix}better_admin_package_order', $columns, $indexes, array(), 'ignore');

add_integration_function('integrate_manage_packages', 'betteradmin_manage_packages', true, '$sourcedir/BetterAdmin.php');
