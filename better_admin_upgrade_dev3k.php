<?php

if (!defined('SMF'))
    die('No direct access...');

// Stage 4B is presentation-only; keep the Theme Settings integration hook registered.
add_integration_function('integrate_manage_themes', 'betteradmin_manage_themes', true, '$sourcedir/BetterAdmin.php');
