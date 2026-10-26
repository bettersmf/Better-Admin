<?php

if (!defined('SMF'))
    die('No direct access...');

global $smcFunc;
$smcFunc['db_drop_table']('{db_prefix}better_admin_topic_review');

$smcFunc['db_drop_table']('{db_prefix}better_admin_message_review');

$smcFunc['db_drop_table']('{db_prefix}better_admin_package_order');
