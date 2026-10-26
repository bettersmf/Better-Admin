<?php

if (!defined('SMF'))
    die('No direct access...');

// Stage 4A adds a Theme Settings integration hook. The package replaces the
// source/language/CSS files before this script runs.
add_integration_function('integrate_manage_themes', 'betteradmin_manage_themes', true, '$sourcedir/BetterAdmin.php');
