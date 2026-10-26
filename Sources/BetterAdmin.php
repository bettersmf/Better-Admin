<?php

/**
 * Better Admin - Stage 1: Bulk Topic Management
 *
 * @package BetterAdmin
 * @version 0.1.0-dev3k
 */

if (!defined('SMF'))
    die('No direct access...');

function betteradmin_load_language()
{
    global $txt;

    loadLanguage('BetterAdmin');

    // A generated/custom theme can retain an older theme-local copy of the
    // language file. SMF will prefer that copy over Themes/default, which can
    // leave newly-added Better Admin strings undefined after an upgrade. Keep
    // the runtime resilient and backfill every label used by the hooks.
    $fallbacks = array(
        'better_admin_name' => 'Better Admin',
        'better_admin_delete_selected' => 'Delete selected',
        'better_admin_move_selected' => 'Move selected',
        'better_admin_review_selected' => 'Disable for Review',
        'better_admin_restore_selected' => 'Restore from Review',
        'better_admin_selected_actions' => 'Selected topics',
        'better_admin_under_review' => 'Under Review',
        'better_admin_select_topic' => 'Select at least one topic first.',
        'better_admin_delete_confirm' => 'Delete the selected topics and all replies they contain?',
        'better_admin_move_confirm' => 'Move the selected topics to the chosen board?',
        'better_admin_review_confirm' => 'Hide the selected topics from regular members and mark them Under Review?',
        'better_admin_restore_confirm' => 'Restore the selected topics from review and make them visible again?',
        'better_admin_edit' => 'Edit',
        'better_admin_delete' => 'Delete',
        'better_admin_report' => 'Report',
        'better_admin_select_all_replies' => 'Select All Replies',
        'better_admin_reply_label' => 'Reply',
        'better_admin_delete_selected_replies' => 'Delete Selected Replies',
        'better_admin_select_reply' => 'Select at least one reply first.',
        'better_admin_delete_replies_confirm' => 'Delete the selected replies? This cannot be undone.',
        'better_admin_review_selected_replies' => 'Put Selected Under Review',
        'better_admin_review_replies_confirm' => 'Hide the selected replies from regular members and place them Under Review?',
        'better_admin_restore_selected_replies' => 'Restore Selected Replies',
        'better_admin_restore_replies_confirm' => 'Restore the selected reviewed replies and make them visible to regular members again?',
        'better_admin_reply_under_review_action' => 'Under Review',
        'better_admin_reply_restore_action' => 'Restore',
        'better_admin_reply_under_review_badge' => 'Under Review',
        'better_admin_reply_review_confirm' => 'Hide this reply from regular members and place it Under Review?',
        'better_admin_reply_restore_confirm' => 'Restore this reply and make it visible to regular members again?',
        'better_admin_package_install_success' => '%1$s %2$s installed successfully.',
        'better_admin_package_upgrade_success' => '%1$s %2$s upgraded successfully.',
        'better_admin_package_uninstall_success' => '%1$s uninstalled successfully.',
        'better_admin_package_delete_success' => '%1$s was deleted from Package Manager.',
        'better_admin_package_operation_failed' => '%1$s did not complete successfully. Review the package details and try again.',
        'better_admin_package_failed_steps' => '%1$s completed with one or more failed steps. Review the package before continuing.',
        'better_admin_package_delete_failed' => '%1$s could not be deleted from Package Manager.',
        'better_admin_dismiss_notice' => 'Dismiss',
        'better_admin_theme_bulk_title' => 'Bulk Theme Cleanup',
        'better_admin_theme_bulk_desc' => 'Select installed themes you no longer need and remove them together. Protected themes cannot be selected or removed.',
        'better_admin_theme_select_all' => 'Select All Unused Themes',
        'better_admin_theme_clear_all' => 'Clear Selection',
        'better_admin_theme_delete_selected' => 'Delete Selected Themes',
        'better_admin_theme_none' => 'There are no removable themes right now.',
        'better_admin_theme_curve_protected' => 'Core Theme · Protected',
        'better_admin_theme_current_protected' => 'Current Forum Theme · Protected',
        'better_admin_theme_select_one' => 'Select at least one removable theme first.',
        'better_admin_theme_confirm_title' => 'Confirm Theme Deletion',
        'better_admin_theme_confirm_desc' => 'The following themes and their theme directories will be permanently removed. This cannot be undone.',
        'better_admin_theme_confirm_button' => 'Delete These Themes',
        'better_admin_theme_cancel' => 'Cancel',
        'better_admin_theme_removed' => '%1$d theme(s) deleted successfully.',
        'better_admin_theme_protected_attempt' => 'One or more selected themes are protected and cannot be deleted.',
    );

    foreach ($fallbacks as $key => $value)
        if (!isset($txt[$key]))
            $txt[$key] = $value;
}

function betteradmin_is_staff_for_topics()
{
    global $user_info;

    return !empty($user_info['is_admin'])
        || allowedTo('remove_any')
        || allowedTo('move_any')
        || allowedTo('approve_posts');
}

function betteradmin_can_review_topics()
{
    global $user_info;

    return !empty($user_info['is_admin'])
        || allowedTo('remove_any')
        || allowedTo('approve_posts');
}

/**
 * Force checkbox quick moderation for staff on board listings with >1 topic.
 * Also correct pagination counts for non-staff when topics are quarantined.
 */
function betteradmin_pre_messageindex(&$sort_methods, &$sort_methods_table)
{
    global $options, $board_info, $board, $smcFunc, $context;

    betteradmin_load_language();

    $staff = betteradmin_is_staff_for_topics();

    if (!$staff && !empty($board))
    {
        $request = $smcFunc['db_query']('', '
            SELECT COUNT(*)
            FROM {db_prefix}better_admin_topic_review
            WHERE id_board = {int:current_board}',
            array('current_board' => (int) $board)
        );
        list ($hidden_count) = $smcFunc['db_fetch_row']($request);
        $smcFunc['db_free_result']($request);

        if (!empty($hidden_count) && isset($board_info['total_topics']))
            $board_info['total_topics'] = max(0, (int) $board_info['total_topics'] - (int) $hidden_count);
    }

    if ($staff && !empty($board_info['total_topics']) && (int) $board_info['total_topics'] > 1)
    {
        // Request-only override. We do not alter the member's saved preference.
        $options['display_quick_mod'] = 1;
        $context['better_admin_bulk_topics'] = true;

        loadCSSFile('better_admin.css', array(
        'minimize' => true,
        'default_theme' => true,
        'seed' => 'betteradmin-dev3i',
    ), 'better_admin_dev3i');
        loadJavaScriptFile('better_admin.js', array('defer' => true, 'minimize' => true), 'better_admin');
    }
}

/**
 * Hide quarantined topics from ordinary members at the query level.
 */
function betteradmin_message_index_query(&$selects, &$tables, &$parameters, &$wheres, &$topic_ids, &$topic_wheres)
{
    if (betteradmin_is_staff_for_topics())
        return;

    $topic_wheres[] = 'NOT EXISTS (
        SELECT 1
        FROM {db_prefix}better_admin_topic_review AS better_admin_review
        WHERE better_admin_review.id_topic = t.id_topic
    )';
}

/**
 * Reduce SMF's quick-mod menu to Better Admin Stage 1 actions only.
 */
function betteradmin_quick_mod_actions()
{
    global $context, $txt;

    if (empty($context['better_admin_bulk_topics']) || !betteradmin_is_staff_for_topics())
        return;

    betteradmin_load_language();

    $context['can_review'] = betteradmin_can_review_topics();
    $context['can_review_restore'] = betteradmin_can_review_topics();

    $actions = array();

    if (!empty($context['can_remove']))
        $actions[] = 'remove';

    if (!empty($context['can_move']))
        $actions[] = 'move';

    if (!empty($context['can_review']))
        $actions[] = 'review';

    if (!empty($context['can_review_restore']))
        $actions[] = 'review_restore';

    $context['qmod_actions'] = $actions;

    // Cleaner wording than SMF's defaults.
    $txt['quick_mod_remove'] = $txt['better_admin_delete_selected'];
    $txt['quick_mod_move'] = $txt['better_admin_move_selected'];
    $txt['quick_mod_review'] = $txt['better_admin_review_selected'];
    $txt['quick_mod_review_restore'] = $txt['better_admin_restore_selected'];
    $txt['quick_mod_selected'] = $txt['better_admin_selected_actions'];
}

/**
 * Mark staff-visible quarantined topics and provide JS configuration.
 */
function betteradmin_messageindex_finalize(&$normal_buttons)
{
    global $context, $smcFunc, $board, $scripturl, $txt;

    if (empty($context['better_admin_bulk_topics']) || empty($context['topics']) || !betteradmin_is_staff_for_topics())
        return;

    betteradmin_load_language();

    $topic_ids = array_map('intval', array_keys($context['topics']));
    if (!empty($topic_ids))
    {
        $request = $smcFunc['db_query']('', '
            SELECT id_topic, id_member, review_time
            FROM {db_prefix}better_admin_topic_review
            WHERE id_topic IN ({array_int:topic_ids})',
            array('topic_ids' => $topic_ids)
        );

        while ($row = $smcFunc['db_fetch_assoc']($request))
        {
            $id_topic = (int) $row['id_topic'];
            if (!isset($context['topics'][$id_topic]))
                continue;

            $context['topics'][$id_topic]['better_admin_under_review'] = true;
            $context['topics'][$id_topic]['css_class'] .= ' better_admin_under_review';
            $context['topics'][$id_topic]['first_post']['link'] .= ' <span class="better_admin_review_badge">' . $txt['better_admin_under_review'] . '</span>';
        }
        $smcFunc['db_free_result']($request);

    }

    $context['html_headers'] .= "\n<script>window.BetterAdminBulk = " . json_encode(array(
        'url' => $scripturl . '?action=betteradminbulk;board=' . (int) $board . '.' . (int) $context['start'],
        'reviewActions' => array('review', 'review_restore'),
        'selectAtLeastOne' => $txt['better_admin_select_topic'],
        'confirmDelete' => $txt['better_admin_delete_confirm'],
        'confirmMove' => $txt['better_admin_move_confirm'],
        'confirmReview' => $txt['better_admin_review_confirm'],
        'confirmRestore' => $txt['better_admin_restore_confirm'],
    )) . ';</script>';
}

/**
 * Add our custom bulk endpoint.
 */
function betteradmin_actions(&$actionArray)
{
    $actionArray['betteradminbulk'] = array('BetterAdmin.php', 'betteradmin_bulk_action');
    $actionArray['betteradminreplies'] = array('BetterAdmin.php', 'betteradmin_bulk_reply_action');
    $actionArray['betteradminreplyreview'] = array('BetterAdmin.php', 'betteradmin_reply_review_action');
    $actionArray['betteradminrepliesreview'] = array('BetterAdmin.php', 'betteradmin_bulk_reply_review_action');
}

/**
 * Quarantine / restore selected topics.
 */
function betteradmin_bulk_action()
{
    global $board, $context, $smcFunc, $user_info;

    betteradmin_load_language();
    checkSession('request');

    if (!betteradmin_can_review_topics())
        fatal_lang_error('no_access', false);

    $requested_board = isset($_REQUEST['board']) ? (int) $_REQUEST['board'] : (int) $board;
    if (empty($requested_board) || $requested_board !== (int) $board)
        fatal_lang_error('no_access', false);

    $action = isset($_REQUEST['qaction']) ? (string) $_REQUEST['qaction'] : '';
    if (!in_array($action, array('review', 'review_restore'), true))
        redirectexit('board=' . $board . '.0');

    $topics = !empty($_REQUEST['topics']) && is_array($_REQUEST['topics'])
        ? array_values(array_unique(array_filter(array_map('intval', $_REQUEST['topics']))))
        : array();

    if (empty($topics))
        redirectexit('board=' . $board . '.' . (int) ($_REQUEST['start'] ?? 0));

    // Only act on topics that actually belong to the current board.
    $valid_topics = array();
    $request = $smcFunc['db_query']('', '
        SELECT id_topic
        FROM {db_prefix}topics
        WHERE id_board = {int:current_board}
            AND id_topic IN ({array_int:topic_ids})',
        array(
            'current_board' => (int) $board,
            'topic_ids' => $topics,
        )
    );
    while ($row = $smcFunc['db_fetch_assoc']($request))
        $valid_topics[] = (int) $row['id_topic'];
    $smcFunc['db_free_result']($request);

    if (!empty($valid_topics))
    {
        if ($action === 'review')
        {
            foreach ($valid_topics as $id_topic)
            {
                $smcFunc['db_insert']('replace',
                    '{db_prefix}better_admin_topic_review',
                    array(
                        'id_topic' => 'int',
                        'id_board' => 'int',
                        'id_member' => 'int',
                        'review_time' => 'int',
                    ),
                    array(
                        $id_topic,
                        (int) $board,
                        (int) $user_info['id'],
                        time(),
                    ),
                    array('id_topic')
                );
            }
        }
        else
        {
            $smcFunc['db_query']('', '
                DELETE FROM {db_prefix}better_admin_topic_review
                WHERE id_topic IN ({array_int:topic_ids})',
                array('topic_ids' => $valid_topics)
            );
        }
    }

    redirectexit('board=' . $board . '.' . (int) ($_REQUEST['start'] ?? 0));
}

/**
 * Block direct topic access for normal users while a topic is under review.
 */
function betteradmin_display_topic_guard(&$topic_selects, &$topic_tables, &$topic_parameters)
{
    global $topic, $smcFunc;

    if (betteradmin_is_staff_for_topics() || empty($topic))
        return;

    $request = $smcFunc['db_query']('', '
        SELECT id_topic
        FROM {db_prefix}better_admin_topic_review
        WHERE id_topic = {int:current_topic}
        LIMIT 1',
        array('current_topic' => (int) $topic)
    );
    $hidden = $smcFunc['db_num_rows']($request) > 0;
    $smcFunc['db_free_result']($request);

    if ($hidden)
        fatal_lang_error('not_a_topic', false, 404);
}

/**
 * Keep the review table clean when topics are permanently removed.
 */
function betteradmin_remove_topics($topics)
{
    global $smcFunc;

    $topics = array_values(array_unique(array_filter(array_map('intval', (array) $topics))));
    if (empty($topics))
        return;

    $smcFunc['db_query']('', '
        DELETE FROM {db_prefix}better_admin_topic_review
        WHERE id_topic IN ({array_int:topic_ids})',
        array('topic_ids' => $topics)
    );

    $smcFunc['db_query']('', '
        DELETE FROM {db_prefix}better_admin_message_review
        WHERE id_topic IN ({array_int:topic_ids})',
        array('topic_ids' => $topics)
    );
}



/**
 * Stage 2B: single-reply review is intentionally staff-only. Topic authors
 * and ordinary members do not gain a new moderation capability here.
 */
function betteradmin_can_review_replies()
{
    global $user_info;

    return !empty($user_info['is_admin'])
        || allowedTo('delete_any')
        || allowedTo('approve_posts');
}

/**
 * Hide replies placed Under Review from ordinary members before SMF fetches
 * the message rows. The first topic message is never eligible for review, so
 * the topic itself always remains renderable.
 */
function betteradmin_filter_reviewed_messages(&$selects, &$tables, &$parameters)
{
    global $smcFunc;

    if (betteradmin_can_review_replies() || empty($parameters['message_list']) || !is_array($parameters['message_list']))
        return;

    $message_ids = array_values(array_unique(array_filter(array_map('intval', $parameters['message_list']))));
    if (empty($message_ids))
        return;

    $hidden = array();
    $request = $smcFunc['db_query']('', '
        SELECT id_msg
        FROM {db_prefix}better_admin_message_review
        WHERE id_msg IN ({array_int:message_ids})',
        array('message_ids' => $message_ids)
    );
    while ($row = $smcFunc['db_fetch_assoc']($request))
        $hidden[] = (int) $row['id_msg'];
    $smcFunc['db_free_result']($request);

    if (!empty($hidden))
    {
        $parameters['message_list'] = array_values(array_diff($message_ids, $hidden));

        // Keep SMF's IN ({array_int:message_list}) query valid even if an
        // entire page consists of quarantined replies. Message ID 0 can never
        // match a real post.
        if (empty($parameters['message_list']))
            $parameters['message_list'] = array(0);
    }
}

/**
 * Place one reply Under Review or restore it. This never accepts the topic
 * starter, even from a crafted request.
 */
function betteradmin_reply_review_action()
{
    global $smcFunc, $user_info;

    betteradmin_load_language();
    checkSession('request');

    if (!betteradmin_can_review_replies())
        fatal_lang_error('no_access', false);

    $topic_id = (int) ($_REQUEST['topic'] ?? 0);
    $message_id = (int) ($_REQUEST['msg'] ?? 0);
    $start = max(0, (int) ($_REQUEST['start'] ?? 0));
    $mode = isset($_REQUEST['sa']) ? (string) $_REQUEST['sa'] : 'review';

    if (empty($topic_id) || empty($message_id) || !in_array($mode, array('review', 'restore'), true))
        fatal_lang_error('no_access', false);

    $request = $smcFunc['db_query']('', '
        SELECT t.id_first_msg, t.id_board, m.id_msg
        FROM {db_prefix}topics AS t
            INNER JOIN {db_prefix}messages AS m ON (m.id_topic = t.id_topic)
        WHERE t.id_topic = {int:topic}
            AND m.id_msg = {int:message}
        LIMIT 1',
        array(
            'topic' => $topic_id,
            'message' => $message_id,
        )
    );
    $row = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);

    if (empty($row) || (int) $row['id_first_msg'] === $message_id)
        fatal_lang_error('no_access', false);

    if ($mode === 'review')
    {
        $smcFunc['db_insert']('replace',
            '{db_prefix}better_admin_message_review',
            array(
                'id_msg' => 'int',
                'id_topic' => 'int',
                'id_board' => 'int',
                'id_member' => 'int',
                'review_time' => 'int',
            ),
            array(
                $message_id,
                $topic_id,
                (int) $row['id_board'],
                (int) $user_info['id'],
                time(),
            ),
            array('id_msg')
        );
    }
    else
    {
        $smcFunc['db_query']('', '
            DELETE FROM {db_prefix}better_admin_message_review
            WHERE id_msg = {int:message}',
            array('message' => $message_id)
        );
    }

    redirectexit('topic=' . $topic_id . '.' . $start . ';msg=' . $message_id . '#msg' . $message_id);
}

/**
 * Stage 2B: place selected replies Under Review or restore selected reviewed
 * replies in one action.
 *
 * This remains a staff-only moderation capability. The topic starter is
 * excluded both by the UI and again here on the server so a crafted request
 * can never quarantine or restore the first post/topic itself.
 */
function betteradmin_bulk_reply_review_action()
{
    global $smcFunc, $user_info;

    betteradmin_load_language();
    checkSession('post');

    if (!betteradmin_can_review_replies())
        fatal_lang_error('no_access', false);

    $topic_id = (int) ($_REQUEST['topic'] ?? 0);
    $start = max(0, (int) ($_REQUEST['start'] ?? 0));
    $mode = isset($_REQUEST['sa']) ? (string) $_REQUEST['sa'] : 'review';

    if (empty($topic_id) || !in_array($mode, array('review', 'restore'), true))
        fatal_lang_error('no_access', false);

    $request = $smcFunc['db_query']('', '
        SELECT id_first_msg, id_board
        FROM {db_prefix}topics
        WHERE id_topic = {int:topic}
        LIMIT 1',
        array('topic' => $topic_id)
    );
    $topic_row = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);

    if (empty($topic_row))
        fatal_lang_error('not_a_topic', false);

    $messages = !empty($_POST['better_admin_replies']) && is_array($_POST['better_admin_replies'])
        ? array_values(array_unique(array_filter(array_map('intval', $_POST['better_admin_replies']))))
        : array();

    $messages = array_slice($messages, 0, 100);
    $messages = array_values(array_diff($messages, array((int) $topic_row['id_first_msg'])));

    if (empty($messages))
        redirectexit('topic=' . $topic_id . '.' . $start);

    // Only accept real replies that belong to this exact topic.
    $valid = array();
    $request = $smcFunc['db_query']('', '
        SELECT id_msg
        FROM {db_prefix}messages
        WHERE id_topic = {int:topic}
            AND id_msg IN ({array_int:messages})
            AND id_msg != {int:first_message}',
        array(
            'topic' => $topic_id,
            'messages' => $messages,
            'first_message' => (int) $topic_row['id_first_msg'],
        )
    );
    while ($row = $smcFunc['db_fetch_assoc']($request))
        $valid[] = (int) $row['id_msg'];
    $smcFunc['db_free_result']($request);

    if (empty($valid))
        redirectexit('topic=' . $topic_id . '.' . $start);

    if ($mode === 'restore')
    {
        $smcFunc['db_query']('', '
            DELETE FROM {db_prefix}better_admin_message_review
            WHERE id_topic = {int:topic}
                AND id_msg IN ({array_int:messages})',
            array(
                'topic' => $topic_id,
                'messages' => $valid,
            )
        );
    }
    else
    {
        $review_time = time();
        foreach ($valid as $message_id)
        {
            $smcFunc['db_insert']('replace',
                '{db_prefix}better_admin_message_review',
                array(
                    'id_msg' => 'int',
                    'id_topic' => 'int',
                    'id_board' => 'int',
                    'id_member' => 'int',
                    'review_time' => 'int',
                ),
                array(
                    $message_id,
                    $topic_id,
                    (int) $topic_row['id_board'],
                    (int) $user_info['id'],
                    $review_time,
                ),
                array('id_msg')
            );
        }
    }

    redirectexit('topic=' . $topic_id . '.' . $start);
}

/**
 * Keep the reply-review table clean when SMF permanently removes a message.
 */
function betteradmin_remove_message_review($message, $row, $recycle)
{
    global $smcFunc;

    $smcFunc['db_query']('', '
        DELETE FROM {db_prefix}better_admin_message_review
        WHERE id_msg = {int:message}',
        array('message' => (int) $message)
    );
}


/**
 * Stage 2B: delete selected replies while permanently excluding the topic
 * starter. SMF's native removeMessage() performs the actual deletion so board
 * counts, topic metadata, search hooks, attachments, notifications, and other
 * integrations stay in sync.
 */
function betteradmin_bulk_reply_action()
{
    global $topic, $board, $sourcedir, $smcFunc, $user_info;

    betteradmin_load_language();
    checkSession('post');

    $topic_id = isset($_REQUEST['topic']) ? (int) $_REQUEST['topic'] : (int) $topic;
    $start = max(0, (int) ($_REQUEST['start'] ?? 0));

    if (empty($topic_id))
        fatal_lang_error('no_access', false);

    $request = $smcFunc['db_query']('', '
        SELECT id_first_msg, id_board, id_member_started
        FROM {db_prefix}topics
        WHERE id_topic = {int:current_topic}
        LIMIT 1',
        array('current_topic' => $topic_id)
    );
    $topic_row = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);

    if (empty($topic_row))
        fatal_lang_error('not_a_topic', false);

    // Bulk reply deletion is intentionally a staff/topic-owner moderation
    // workflow, not a shortcut around SMF's ordinary per-message permissions.
    if (!allowedTo('delete_any')
        && !(allowedTo('delete_replies') && (int) $topic_row['id_member_started'] === (int) $user_info['id']))
        fatal_lang_error('cannot_delete_any', 'permission');

    $messages = !empty($_POST['better_admin_replies']) && is_array($_POST['better_admin_replies'])
        ? array_values(array_unique(array_filter(array_map('intval', $_POST['better_admin_replies']))))
        : array();

    // Keep requests bounded and never permit the first message to enter the
    // deletion set, even if a crafted POST tries to submit it manually.
    $messages = array_slice($messages, 0, 100);
    $messages = array_values(array_diff($messages, array((int) $topic_row['id_first_msg'])));

    if (empty($messages))
        redirectexit('topic=' . $topic_id . '.' . $start);

    $valid = array();
    $request = $smcFunc['db_query']('', '
        SELECT id_msg, id_member, subject
        FROM {db_prefix}messages
        WHERE id_topic = {int:current_topic}
            AND id_msg IN ({array_int:messages})
            AND id_msg != {int:first_message}
        ORDER BY id_msg DESC',
        array(
            'current_topic' => $topic_id,
            'messages' => $messages,
            'first_message' => (int) $topic_row['id_first_msg'],
        )
    );
    while ($row = $smcFunc['db_fetch_assoc']($request))
        $valid[(int) $row['id_msg']] = $row;
    $smcFunc['db_free_result']($request);

    if (empty($valid))
        redirectexit('topic=' . $topic_id . '.' . $start);

    require_once($sourcedir . '/RemoveTopic.php');

    // Keep SMF's native permission and cleanup path for every reply.
    foreach ($valid as $id_msg => $info)
    {
        removeMessage($id_msg);

        if (allowedTo('delete_any') && (!allowedTo('delete_own') || (int) $info['id_member'] !== (int) $user_info['id']))
            logAction('delete', array(
                'topic' => $topic_id,
                'subject' => $info['subject'],
                'member' => (int) $info['id_member'],
                'board' => (int) $topic_row['id_board'],
            ));
    }

    redirectexit('topic=' . $topic_id . '.' . $start);
}

/**
 * Stage 2B: load reply-selection assets before the page head is rendered.
 *
 * integrate_prepare_display_context fires while Display.template.php is
 * already consuming the message list, which is too late to reliably add a
 * new JavaScript file. integrate_load_theme runs before templates output the
 * head, so assets and configuration registered here are guaranteed to reach
 * the browser.
 */
function betteradmin_load_topic_assets()
{
    global $txt, $scripturl, $context, $smcFunc, $user_info;

    // Only topic display requests need the Stage 2B UI.
    if (empty($_REQUEST['topic']))
        return;

    betteradmin_load_language();

    $topic_id = (int) $_REQUEST['topic'];
    if (empty($topic_id))
        return;

    // Load the current topic's reply-review state once for the per-message
    // display hook. Staff see reviewed replies with a badge and Restore;
    // ordinary members have those replies filtered before rendering.
    $context['better_admin_reviewed_replies'] = array();
    if (betteradmin_can_review_replies())
    {
        $review_request = $smcFunc['db_query']('', '
            SELECT id_msg
            FROM {db_prefix}better_admin_message_review
            WHERE id_topic = {int:current_topic}',
            array('current_topic' => $topic_id)
        );
        while ($review_row = $smcFunc['db_fetch_assoc']($review_request))
            $context['better_admin_reviewed_replies'][(int) $review_row['id_msg']] = true;
        $smcFunc['db_free_result']($review_request);
    }

    // Resolve bulk-reply eligibility before rendering. delete_replies only
    // applies when the current member started the topic; delete_any applies
    // regardless of topic ownership. Admins naturally pass delete_any.
    $request = $smcFunc['db_query']('', '
        SELECT id_member_started
        FROM {db_prefix}topics
        WHERE id_topic = {int:current_topic}
        LIMIT 1',
        array('current_topic' => $topic_id)
    );
    $row = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);

    $can_select_replies = !empty($row)
        && (allowedTo('delete_any')
            || (allowedTo('delete_replies') && (int) $row['id_member_started'] === (int) $user_info['id']));

    loadCSSFile('better_admin.css', array(
        'minimize' => true,
        'default_theme' => true,
        'seed' => 'betteradmin-dev3i',
    ), 'better_admin_dev3i');

    loadJavaScriptFile('better_admin_stage2b.js', array(
        'defer' => true,
        'minimize' => false,
        'default_theme' => true,
        'seed' => 'betteradmin-dev2n',
    ), 'better_admin_stage2b_dev2n');

    addJavaScriptVar('BetterAdminReplies', json_encode(array(
        'enabled' => (bool) $can_select_replies,
        'selectAllLabel' => $txt['better_admin_select_all_replies'],
        'replyLabel' => $txt['better_admin_reply_label'],
        'deleteLabel' => $txt['better_admin_delete_selected_replies'],
        'reviewLabel' => $txt['better_admin_review_selected_replies'],
        'restoreLabel' => $txt['better_admin_restore_selected_replies'],
        'canReview' => (bool) betteradmin_can_review_replies(),
        'selectAtLeastOne' => $txt['better_admin_select_reply'],
        'confirmDelete' => $txt['better_admin_delete_replies_confirm'],
        'confirmReview' => $txt['better_admin_review_replies_confirm'],
        'confirmRestore' => $txt['better_admin_restore_replies_confirm'],
        'reviewUrl' => $scripturl . '?action=betteradminrepliesreview;sa=review;topic=' . $topic_id . '.' . (int) ($_REQUEST['start'] ?? 0),
        'restoreUrl' => $scripturl . '?action=betteradminrepliesreview;sa=restore;topic=' . $topic_id . '.' . (int) ($_REQUEST['start'] ?? 0),
        'reviewedReplies' => array_map('intval', array_keys($context['better_admin_reviewed_replies'])),
        'deleteUrl' => $scripturl . '?action=betteradminreplies;topic=' . $topic_id . '.' . (int) ($_REQUEST['start'] ?? 0),
        'sessionVar' => $context['session_var'],
        'sessionId' => $context['session_id'],
    )));
}

/**
 * Stage 2B foundation: determine whether the current user is allowed to
 * remove replies in bulk. This mirrors SMF's existing delete permissions;
 * no new deletion behavior is introduced yet.
 */
function betteradmin_can_bulk_replies()
{
    global $context, $user_info;

    return !empty($user_info['is_admin'])
        || allowedTo('delete_any')
        || (allowedTo('delete_replies') && !empty($context['user']['started']));
}

/**
 * Stage 2A: expose post/topic actions that SMF normally hides under More...
 * while preserving the native action URLs, permission checks, and confirms.
 */
function betteradmin_prepare_display_context(&$output, &$message, $counter)
{
    global $txt, $context, $scripturl;

    betteradmin_load_language();
    loadCSSFile('better_admin.css', array(
        'minimize' => true,
        'default_theme' => true,
        'seed' => 'betteradmin-dev3i',
    ), 'better_admin_dev3i');

    // Stage 2B assets are registered from integrate_load_theme, before the
    // page head is emitted. Keep this hook focused on per-post actions.

    if (empty($output['quickbuttons']) || !is_array($output['quickbuttons']))
        return;

    if (empty($output['quickbuttons']['more']) || !is_array($output['quickbuttons']['more']))
        return;

    $more = $output['quickbuttons']['more'];
    unset($output['quickbuttons']['more']);

    // Keep the common controls directly visible and use clearer wording.
    if (!empty($more['modify']))
    {
        $more['modify']['label'] = $txt['better_admin_edit'];
        $more['modify']['class'] = trim(($more['modify']['class'] ?? '') . ' better_admin_visible_action better_admin_edit_action');
        $output['quickbuttons']['better_admin_edit'] = $more['modify'];
        unset($more['modify']);
    }

    if (!empty($more['remove_topic']))
    {
        $more['remove_topic']['label'] = $txt['better_admin_delete'];
        $more['remove_topic']['class'] = trim(($more['remove_topic']['class'] ?? '') . ' better_admin_visible_action better_admin_delete_action');
        $output['quickbuttons']['better_admin_delete_topic'] = $more['remove_topic'];
        unset($more['remove_topic']);
    }

    if (!empty($more['remove']))
    {
        $more['remove']['label'] = $txt['better_admin_delete'];
        $more['remove']['class'] = trim(($more['remove']['class'] ?? '') . ' better_admin_visible_action better_admin_delete_action');
        $output['quickbuttons']['better_admin_delete_reply'] = $more['remove'];
        unset($more['remove']);
    }

    // Stage 2B: staff can quarantine one reply without touching the topic.
    // The starter is intentionally excluded; topic-level review remains the
    // responsibility of Stage 1.
    if (betteradmin_can_review_replies() && (int) $context['topic_first_message'] !== (int) $output['id'])
    {
        $is_under_review = !empty($context['better_admin_reviewed_replies'][(int) $output['id']]);
        $review_label = $is_under_review ? $txt['better_admin_reply_restore_action'] : $txt['better_admin_reply_under_review_action'];
        $review_confirm = $is_under_review ? $txt['better_admin_reply_restore_confirm'] : $txt['better_admin_reply_review_confirm'];
        $review_mode = $is_under_review ? 'restore' : 'review';

        $output['quickbuttons']['better_admin_reply_review'] = array(
            'label' => $review_label,
            'href' => $scripturl . '?action=betteradminreplyreview;sa=' . $review_mode . ';topic=' . (int) $context['current_topic'] . '.' . (int) $context['start'] . ';msg=' . (int) $output['id'] . ';' . $context['session_var'] . '=' . $context['session_id'],
            'javascript' => 'data-confirm="' . $review_confirm . '"',
            'class' => 'you_sure better_admin_visible_action better_admin_review_action' . ($is_under_review ? ' better_admin_restore_review_action' : ''),
            'icon' => $is_under_review ? 'restore_button' : 'unapprove_button',
            'show' => true,
        );

        if ($is_under_review)
        {
            $output['css_class'] .= ' better_admin_reply_under_review';
            $output['link'] .= ' <span class="better_admin_reply_review_badge">' . $txt['better_admin_reply_under_review_badge'] . '</span>';
        }
    }

    if (!empty($more['report']))
    {
        $more['report']['label'] = $txt['better_admin_report'];
        $more['report']['class'] = trim(($more['report']['class'] ?? '') . ' better_admin_visible_action better_admin_report_action');
        $output['quickbuttons']['better_admin_report'] = $more['report'];
        unset($more['report']);
    }

    // Do not lose any of SMF's less-common moderation controls. If SMF says
    // the action is available, expose it directly rather than hiding it again.
    foreach ($more as $key => $button)
    {
        $button['class'] = trim(($button['class'] ?? '') . ' better_admin_visible_action better_admin_secondary_action');
        $output['quickbuttons']['better_admin_' . $key] = $button;
    }
}


/**
 * Stage 3A: Better Package Manager foundation.
 *
 * Keep each uploaded package instance in a stable browse position until that
 * package file is deleted. New/re-uploaded package files are appended to the
 * end of their package-type list. Also return successful installs/upgrades to
 * the package list and highlight the package that just finished.
 */
function betteradmin_manage_packages(&$subActions)
{
    // Only replace the narrow package-manager flows Better Admin owns.
    $subActions['browse'] = 'betteradmin_package_browse';
    $subActions['install2'] = 'betteradmin_package_install';
    $subActions['uninstall2'] = 'betteradmin_package_install';
    $subActions['remove'] = 'betteradmin_package_remove';
}

/**
 * Better Admin wrapper around SMF's package install executor.
 *
 * Package-defined redirects are preserved. When SMF would otherwise leave the
 * administrator on its generic "installed" completion screen, return to the
 * package list instead and identify the package that just completed.
 */
function betteradmin_package_install()
{
    global $context, $sourcedir, $smcFunc, $txt;

    betteradmin_load_language();

    $filename = !empty($_REQUEST['package']) ? (string) $_REQUEST['package'] : '';
    $package_name = $filename !== '' ? $filename : 'Package';
    $package_version = '';
    $was_installed = false;

    // Capture the human-friendly package identity before SMF executes it so
    // the post-operation notice can say exactly what succeeded.
    if ($filename !== '')
    {
        require_once($sourcedir . '/Subs-Package.php');
        $packageInfo = getPackageInfo($filename);

        if (is_array($packageInfo))
        {
            if (!empty($packageInfo['name']))
                $package_name = un_htmlspecialchars($packageInfo['name']);
            if (!empty($packageInfo['version']))
                $package_version = un_htmlspecialchars($packageInfo['version']);

            if (!empty($packageInfo['id']))
            {
                $request = $smcFunc['db_query']('', '
                    SELECT id_install
                    FROM {db_prefix}log_packages
                    WHERE package_id = {string:package_id}
                        AND install_state != {int:not_installed}
                    LIMIT 1',
                    array(
                        'package_id' => $packageInfo['id'],
                        'not_installed' => 0,
                    )
                );
                $was_installed = (bool) $smcFunc['db_num_rows']($request);
                $smcFunc['db_free_result']($request);
            }
        }
    }

    $requested_uninstall = isset($_REQUEST['sa']) && $_REQUEST['sa'] === 'uninstall2';

    PackageInstall();

    // Respect package-authored redirects. Better Admin only replaces SMF's
    // generic completion dead-end.
    if (!empty($context['redirect_url']))
        return;

    $destination = 'action=admin;area=packages;sa=browse';
    if ($filename !== '')
        $destination .= ';betteradmin_package=' . rawurlencode($filename);

    // PackageInstall() returns only after SMF has completed the executor. Build
    // the flash message here and carry it in the redirect URL as well as the
    // session. The query-string copy makes the notice deterministic even on
    // hosts where package/cache cleanup interferes with session flash data.
    if (!empty($context['install_finished']))
    {
        if ($requested_uninstall || !empty($context['uninstalling']))
        {
            $notice_type = 'success';
            $notice_message = sprintf($txt['better_admin_package_uninstall_success'], $package_name);
        }
        else
        {
            // If SMF logged failed modification steps, keep the alert visible
            // instead of claiming an entirely clean success.
            $failed_steps = betteradmin_package_failed_step_count($filename);
            if ($failed_steps > 0)
            {
                $notice_type = 'error';
                $notice_message = sprintf($txt['better_admin_package_failed_steps'], $package_name);
            }
            elseif ($was_installed)
            {
                $notice_type = 'success';
                $notice_message = sprintf($txt['better_admin_package_upgrade_success'], $package_name, $package_version);
            }
            else
            {
                $notice_type = 'success';
                $notice_message = sprintf($txt['better_admin_package_install_success'], $package_name, $package_version);
            }
        }
    }
    else
    {
        $notice_type = 'error';
        $notice_message = sprintf($txt['better_admin_package_operation_failed'], $package_name);
    }

    betteradmin_set_package_notice($notice_type, $notice_message);
    $destination = betteradmin_add_package_notice_to_url($destination, $notice_type, $notice_message);
    redirectexit($destination);
}

/**
 * Store a one-request Package Manager flash notice in the admin session.
 */
function betteradmin_set_package_notice($type, $message)
{
    $_SESSION['better_admin_package_notice'] = array(
        'type' => $type === 'error' ? 'error' : 'success',
        'message' => (string) $message,
    );
}

/**
 * Carry the Package Manager notice through the redirect itself. This is the
 * authoritative transport; the session copy remains as a fallback.
 */
function betteradmin_add_package_notice_to_url($destination, $type, $message)
{
    $type = $type === 'error' ? 'error' : 'success';

    return $destination
        . ';betteradmin_notice_type=' . rawurlencode($type)
        . ';betteradmin_notice=' . rawurlencode(base64_encode((string) $message));
}

/**
 * Return the failed-step count from the newest log row for an uploaded file.
 */
function betteradmin_package_failed_step_count($filename)
{
    global $smcFunc;

    if ($filename === '')
        return 0;

    $request = $smcFunc['db_query']('', '
        SELECT failed_steps
        FROM {db_prefix}log_packages
        WHERE filename = {string:filename}
        ORDER BY time_installed DESC, id_install DESC
        LIMIT 1',
        array('filename' => $filename)
    );
    $row = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);

    if (empty($row['failed_steps']))
        return 0;

    $steps = $smcFunc['json_decode']($row['failed_steps'], true);
    return is_array($steps) ? count($steps) : 0;
}

/**
 * Remove a package file and retire only that uploaded package instance's
 * stable ordering record. If the package is uploaded again later, it receives
 * a new position at the end of the list.
 */
function betteradmin_package_remove()
{
    global $scripturl, $packagesdir, $smcFunc, $txt;

    betteradmin_load_language();

    checkSession('get');

    if (!isset($_GET['package']) || $_GET['package'] === 'index.php' || $_GET['package'] === 'backups')
    {
        betteradmin_set_package_notice('error', sprintf($txt['better_admin_package_delete_failed'], 'Package'));
        redirectexit('action=admin;area=packages;sa=browse');
    }

    $package = preg_replace('~[\.]+~', '.', strtr($_GET['package'], array('/' => '_', '\\' => '_')));
    $path = $packagesdir . '/' . $package;

    $valid = file_exists($path)
        && ($package !== 'backups')
        && substr($package, 0, 1) !== '.'
        && (
            is_dir($path)
            || substr(strtolower($package), -4) === '.zip'
            || substr(strtolower($package), -4) === '.tgz'
            || substr(strtolower($package), -7) === '.tar.gz'
        );

    if ($valid)
    {
        create_chmod_control(array($path), array(
            'destination_url' => $scripturl . '?action=admin;area=packages;sa=remove;package=' . $package,
            'crash_on_error' => true,
        ));

        if (is_dir($path))
            deltree($path);
        else
        {
            smf_chmod($path, 0777);
            @unlink($path);
        }

        // Only retire the slot after the file is actually gone.
        if (!file_exists($path))
        {
            $smcFunc['db_query']('', '
                DELETE FROM {db_prefix}better_admin_package_order
                WHERE package_filename = {string:package_filename}',
                array('package_filename' => $package)
            );
            $notice_type = 'success';
            $notice_message = sprintf($txt['better_admin_package_delete_success'], $package);
        }
        else
        {
            $notice_type = 'error';
            $notice_message = sprintf($txt['better_admin_package_delete_failed'], $package);
        }
    }
    else
    {
        $notice_type = 'error';
        $notice_message = sprintf($txt['better_admin_package_delete_failed'], $package);
    }

    betteradmin_set_package_notice($notice_type, $notice_message);
    redirectexit(betteradmin_add_package_notice_to_url('action=admin;area=packages;sa=browse', $notice_type, $notice_message));
}

/**
 * Render SMF's normal package-manager browse screen using Better Admin's
 * stable package-instance ordering instead of the transient filesystem ID.
 */
function betteradmin_package_browse()
{
    global $txt, $scripturl, $context, $sourcedir, $smcFunc;

    betteradmin_load_language();

    $context['page_title'] .= ' - ' . $txt['browse_packages'];
    $context['forum_version'] = SMF_FULL_VERSION;
    $context['available_packages'] = 0;
    $context['modification_types'] = array('modification', 'avatar', 'language', 'unknown', 'smiley');

    call_integration_hook('integrate_modification_types');

    require_once($sourcedir . '/Subs-List.php');

    $highlight = isset($_GET['betteradmin_package']) ? (string) $_GET['betteradmin_package'] : '';
    $context['better_admin_package_highlight'] = $highlight;

    loadCSSFile('better_admin.css', array(
        'minimize' => true,
        'default_theme' => true,
        'seed' => 'betteradmin-dev3g',
    ), 'better_admin_dev3g');

    foreach ($context['modification_types'] as $type)
    {
        $listOptions = array(
            'id' => 'packages_lists_' . $type,
            'title' => $txt[$type . '_package'],
            'no_items_label' => $txt['no_packages'],
            'get_items' => array(
                'function' => 'betteradmin_list_get_packages_stable',
                'params' => array($type),
            ),
            'base_href' => $scripturl . '?action=admin;area=packages;sa=browse;type=' . $type,
            'default_sort_col' => 'stable' . $type,
            'data_check' => array(
                'class' => function($package) use ($highlight)
                {
                    return $highlight !== '' && isset($package['filename']) && $package['filename'] === $highlight
                        ? 'better_admin_package_highlight'
                        : '';
                },
            ),
            'columns' => array(
                'stable' . $type => array(
                    'header' => array(
                        'value' => '#',
                        'style' => 'width: 52px;',
                    ),
                    'data' => array(
                        'db' => 'better_admin_order',
                    ),
                    'sort' => array(
                        'default' => 'better_admin_order',
                        'reverse' => 'better_admin_order',
                    ),
                ),
                'mod_name' . $type => array(
                    'header' => array(
                        'value' => $txt['mod_name'],
                        'style' => 'width: 25%;',
                    ),
                    'data' => array(
                        'db' => 'name',
                    ),
                    'sort' => array(
                        'default' => 'name',
                        'reverse' => 'name',
                    ),
                ),
                'version' . $type => array(
                    'header' => array('value' => $txt['mod_version']),
                    'data' => array('db' => 'version'),
                    'sort' => array('default' => 'version', 'reverse' => 'version'),
                ),
                'time_installed' . $type => array(
                    'header' => array('value' => $txt['mod_installed_time']),
                    'data' => array(
                        'function' => function($package) use ($txt)
                        {
                            return !empty($package['time_installed']) ? timeformat($package['time_installed']) : $txt['not_applicable'];
                        },
                        'class' => 'smalltext',
                    ),
                    'sort' => array('default' => 'time_installed', 'reverse' => 'time_installed'),
                ),
                'operations' . $type => array(
                    'header' => array('value' => ''),
                    'data' => array(
                        'function' => function($package) use ($context, $scripturl, $txt, $type)
                        {
                            $return = '';

                            if ($package['can_uninstall'])
                                $return = '<a href="' . $scripturl . '?action=admin;area=packages;sa=uninstall;package=' . $package['filename'] . ';pid=' . $package['installed_id'] . '" class="button floatnone">' . (isset($txt['uninstall_' . $type]) ? $txt['uninstall_' . $type] : $txt['uninstall']) . '</a>';
                            elseif ($package['can_emulate_uninstall'])
                                $return = '<a href="' . $scripturl . '?action=admin;area=packages;sa=uninstall;ve=' . $package['can_emulate_uninstall'] . ';package=' . $package['filename'] . ';pid=' . $package['installed_id'] . '" class="button floatnone">' . $txt['package_emulate_uninstall'] . ' ' . $package['can_emulate_uninstall'] . '</a>';
                            elseif ($package['can_upgrade'])
                                $return = '<a href="' . $scripturl . '?action=admin;area=packages;sa=install;package=' . $package['filename'] . '" class="button floatnone">' . $txt['package_upgrade'] . '</a>';
                            elseif ($package['can_install'])
                                $return = '<a href="' . $scripturl . '?action=admin;area=packages;sa=install;package=' . $package['filename'] . '" class="button floatnone">' . $txt['install_' . $type] . '</a>';
                            elseif ($package['can_emulate_install'])
                                $return = '<a href="' . $scripturl . '?action=admin;area=packages;sa=install;ve=' . $package['can_emulate_install'] . ';package=' . $package['filename'] . '" class="button floatnone">' . $txt['package_emulate_install'] . ' ' . $package['can_emulate_install'] . '</a>';

                            $buttons = array();

                            if ($return !== '')
                                $buttons[] = $return;

                            $buttons[] = '<a href="' . $scripturl . '?action=admin;area=packages;sa=list;package=' . $package['filename'] . '" class="button floatnone">' . $txt['list_files'] . '</a>';
                            $buttons[] = '<a href="' . $scripturl . '?action=admin;area=packages;sa=remove;package=' . $package['filename'] . ';' . $context['session_var'] . '=' . $context['session_id'] . '"' . ($package['is_installed'] && $package['is_current'] ? ' data-confirm="' . $txt['package_delete_bad'] . '"' : '') . ' class="button' . ($package['is_installed'] && $package['is_current'] ? ' you_sure' : '') . ' floatnone">' . $txt['package_delete'] . '</a>';

                            // Real HTML whitespace between inline-block buttons.  Unlike the
                            // old literal "\n" strings, this creates spacing without printing text.
                            return implode("\n", $buttons);
                        },
                        'class' => 'righttext',
                    ),
                ),
            ),
        );

        createList($listOptions);
    }

    $context['sub_template'] = 'browse';
    $context['default_list'] = 'packages_lists';

    $get_versions = $smcFunc['db_query']('', '
        SELECT data FROM {db_prefix}admin_info_files WHERE filename={string:versionsfile} AND path={string:smf}',
        array('versionsfile' => 'latest-versions.txt', 'smf' => '/smf/')
    );
    $data = $smcFunc['db_fetch_assoc']($get_versions);
    $smcFunc['db_free_result']($get_versions);

    $items = $smcFunc['json_decode']($data['data'], true);
    $context['emulation_versions'] = preg_replace('~^SMF ~', '', $items);
    $context['default_version'] = SMF_VERSION;

    if (!in_array($context['default_version'], $context['emulation_versions']))
        $context['emulation_versions'][] = $context['default_version'];

    $context['selected_version'] = preg_replace('~^SMF ~', '', $context['forum_version']);

    // Resolve the Package Manager notice. Prefer redirect-carried data because
    // it survives package/cache cleanup reliably; keep the session flash as a
    // fallback for compatibility with earlier Stage 3 builds.
    $package_notice = null;

    if (!empty($_GET['betteradmin_notice']))
    {
        $decoded_notice = base64_decode((string) $_GET['betteradmin_notice'], true);
        if ($decoded_notice !== false && $decoded_notice !== '')
        {
            $package_notice = array(
                'type' => !empty($_GET['betteradmin_notice_type']) && $_GET['betteradmin_notice_type'] === 'error' ? 'error' : 'success',
                'message' => $decoded_notice,
            );
        }
    }

    if ($package_notice === null && !empty($_SESSION['better_admin_package_notice']) && is_array($_SESSION['better_admin_package_notice']))
        $package_notice = $_SESSION['better_admin_package_notice'];

    unset($_SESSION['better_admin_package_notice']);

    if (!empty($package_notice['message']))
    {
        $context['better_admin_package_notice'] = array(
            'type' => !empty($package_notice['type']) && $package_notice['type'] === 'error' ? 'error' : 'success',
            'message' => (string) $package_notice['message'],
        );

        // Server-render the notice as its own template layer. This removes the
        // JavaScript timing dependency that prevented dev3d/dev3e notices from
        // appearing on some Package Manager pages.
        if (!in_array('better_admin_package_notice', $context['template_layers'], true))
            $context['template_layers'][] = 'better_admin_package_notice';
    }

    if ($highlight !== '')
    {
        addInlineJavaScript('\n            var row = document.querySelector(".better_admin_package_highlight");\n            if (row)\n                row.scrollIntoView({behavior: "smooth", block: "center"});', true);
    }
}

/**
 * Call SMF's package scanner, then layer a stable, persistent sequence over
 * the returned package instances.
 */
function betteradmin_list_get_packages_stable($start, $items_per_page, $sort, $type)
{
    // SMF's scanner uses the global desc flag even when we only need its raw
    // package metadata. Prevent that from affecting first-seen assignment.
    $had_desc = array_key_exists('desc', $_GET);
    $old_desc = $had_desc ? $_GET['desc'] : null;
    unset($_GET['desc']);

    $packages = list_getPackages($start, $items_per_page, 'sort_id', $type);

    if ($had_desc)
        $_GET['desc'] = $old_desc;

    betteradmin_sync_package_order($packages, $type);

    $allowed = array('better_admin_order', 'name', 'version', 'time_installed');
    $sort_field = in_array($sort, $allowed, true) ? $sort : 'better_admin_order';
    $descending = isset($_GET['desc']);

    usort($packages, function($a, $b) use ($sort_field, $descending)
    {
        $av = $a[$sort_field] ?? '';
        $bv = $b[$sort_field] ?? '';

        if ($sort_field === 'version')
            $cmp = version_compare((string) $av, (string) $bv);
        elseif (is_numeric($av) && is_numeric($bv))
            $cmp = ((float) $av <=> (float) $bv);
        else
            $cmp = strnatcasecmp((string) $av, (string) $bv);

        if ($cmp === 0)
            $cmp = strnatcasecmp((string) ($a['filename'] ?? ''), (string) ($b['filename'] ?? ''));

        return $descending ? -$cmp : $cmp;
    });

    return $packages;
}

/**
 * Assign stable package positions and clean up records whose package file has
 * disappeared. Existing rows never get renumbered.
 */
function betteradmin_sync_package_order(&$packages, $type)
{
    global $smcFunc, $packagesdir;

    if (empty($packages))
        return;

    $request = $smcFunc['db_query']('', '
        SELECT package_filename, sort_order
        FROM {db_prefix}better_admin_package_order
        WHERE package_type = {string:package_type}',
        array('package_type' => (string) $type)
    );

    $known = array();
    $max_order = 0;
    while ($row = $smcFunc['db_fetch_assoc']($request))
    {
        $known[$row['package_filename']] = (int) $row['sort_order'];
        $max_order = max($max_order, (int) $row['sort_order']);
    }
    $smcFunc['db_free_result']($request);

    $current = array();
    foreach ($packages as $package)
        if (!empty($package['filename']))
            $current[$package['filename']] = true;

    // If something was removed outside Package Manager, do not let a later
    // re-upload reclaim an old slot once we have observed that it is gone.
    foreach ($known as $filename => $order)
    {
        if (!isset($current[$filename]))
        {
            $smcFunc['db_query']('', '
                DELETE FROM {db_prefix}better_admin_package_order
                WHERE package_type = {string:package_type}
                    AND package_filename = {string:package_filename}',
                array(
                    'package_type' => (string) $type,
                    'package_filename' => $filename,
                )
            );
            unset($known[$filename]);
        }
    }

    $unseen = array();
    foreach ($packages as $index => $package)
    {
        $filename = $package['filename'] ?? '';
        if ($filename === '' || isset($known[$filename]))
            continue;

        $path = $packagesdir . '/' . $filename;
        $mtime = @filemtime($path);
        $unseen[] = array(
            'index' => $index,
            'filename' => $filename,
            'mtime' => $mtime === false ? time() : (int) $mtime,
        );
    }

    usort($unseen, function($a, $b)
    {
        if ($a['mtime'] === $b['mtime'])
            return strnatcasecmp($a['filename'], $b['filename']);
        return $a['mtime'] <=> $b['mtime'];
    });

    foreach ($unseen as $entry)
    {
        $max_order++;
        $known[$entry['filename']] = $max_order;

        $smcFunc['db_insert']('ignore',
            '{db_prefix}better_admin_package_order',
            array(
                'package_filename' => 'string-255',
                'package_type' => 'string-32',
                'sort_order' => 'int',
                'first_seen' => 'int',
            ),
            array(
                $entry['filename'],
                (string) $type,
                $max_order,
                time(),
            ),
            array('id_order')
        );
    }

    foreach ($packages as &$package)
        $package['better_admin_order'] = isset($known[$package['filename']]) ? (int) $known[$package['filename']] : 0;
    unset($package);
}

/**
 * Stage 3F Package Manager notice layer. Render feedback directly in the page
 * instead of creating it later with deferred JavaScript.
 */
function template_better_admin_package_notice_above()
{
    global $context, $txt, $smcFunc;

    if (empty($context['better_admin_package_notice']['message']))
        return;

    $type = !empty($context['better_admin_package_notice']['type']) && $context['better_admin_package_notice']['type'] === 'error' ? 'error' : 'success';
    $message = $smcFunc['htmlspecialchars']((string) $context['better_admin_package_notice']['message']);
    $dismiss = $smcFunc['htmlspecialchars']((string) $txt['better_admin_dismiss_notice']);

    echo "\n",
        '<div id="better_admin_package_notice" class="better_admin_package_notice better_admin_package_notice_', $type, '" role="', $type === 'error' ? 'alert' : 'status', '">',
        '<span class="better_admin_package_notice_text">', $message, '</span>',
        '<button type="button" class="better_admin_package_notice_close" aria-label="', $dismiss, '" onclick="this.parentNode.remove();">&times;</button>',
        '</div>';

    if ($type === 'success')
        echo "\n<script>
(function () {
    var notice = document.getElementById(\"better_admin_package_notice\");
    if (!notice) return;
    window.setTimeout(function () {
        notice.classList.add(\"better_admin_package_notice_fade\");
        window.setTimeout(function () { if (notice && notice.parentNode) notice.remove(); }, 450);
    }, 4200);
})();
</script>";
}

function template_better_admin_package_notice_below()
{
}

/**
 * Stage 4A: extend SMF's Theme Settings area with safe bulk cleanup.
 * We wrap the native list action instead of modifying SMF core templates.
 */
function betteradmin_manage_themes(&$subActions)
{
    if (!isset($subActions['list']))
        return;

    $subActions['list'] = 'betteradmin_theme_list';
    $subActions['betteradminbulkremove'] = 'betteradmin_theme_bulk_remove';
}

/**
 * Run SMF's native theme list first, then add Better Admin's cleanup panel.
 */
function betteradmin_theme_list()
{
    global $context, $modSettings;

    ThemeList();

    // When ThemeList delegates to an individual theme settings page, do not
    // inject the bulk cleanup UI there.
    if (!empty($_REQUEST['th']) || !empty($_REQUEST['id']))
        return;

    betteradmin_load_language();
    isAllowedTo('admin_forum');

    $protected = betteradmin_protected_theme_ids();
    $context['better_admin_theme_cleanup'] = array(
        'protected' => $protected,
        'themes' => array(),
        'removed' => isset($_GET['betteradmin_removed']) ? max(0, (int) $_GET['betteradmin_removed']) : 0,
    );

    foreach ((array) $context['themes'] as $id => $theme)
    {
        $id = (int) $id;
        $reason = '';

        if ($id === 1)
            $reason = 'curve';
        elseif (in_array($id, $protected, true))
            $reason = 'current';

        $context['better_admin_theme_cleanup']['themes'][$id] = array(
            'id' => $id,
            'name' => isset($theme['name']) ? (string) $theme['name'] : ('Theme ' . $id),
            'version' => isset($theme['version']) ? (string) $theme['version'] : '',
            'theme_dir' => isset($theme['theme_dir']) ? (string) $theme['theme_dir'] : '',
            'protected' => $reason !== '',
            'protected_reason' => $reason,
        );
    }

    createToken('better-admin-theme-bulk');

    loadCSSFile('better_admin.css', array('minimize' => true, 'default_theme' => true), 'better_admin_theme_bulk_dev3k');

    if (!in_array('better_admin_theme_bulk', $context['template_layers'], true))
        $context['template_layers'][] = 'better_admin_theme_bulk';
}

/**
 * IDs that Better Admin will never remove, regardless of submitted input.
 */
function betteradmin_protected_theme_ids()
{
    global $modSettings;

    $ids = array(1);

    // theme_guests is the forum-wide theme selected in Theme Administration.
    if (!empty($modSettings['theme_guests']))
        $ids[] = (int) $modSettings['theme_guests'];

    // Keep compatibility with installs that also expose theme_default.
    if (!empty($modSettings['theme_default']))
        $ids[] = (int) $modSettings['theme_default'];

    return array_values(array_unique(array_filter(array_map('intval', $ids))));
}

/**
 * Validate a submitted set against currently installed themes and the hard
 * protection list. Returns normalized theme records keyed by ID.
 */
function betteradmin_validate_theme_selection($submitted)
{
    global $context;

    require_once($GLOBALS['sourcedir'] . '/Subs-Themes.php');
    get_installed_themes();

    $protected = betteradmin_protected_theme_ids();
    $ids = array_values(array_unique(array_filter(array_map('intval', (array) $submitted))));
    $valid = array();

    foreach ($ids as $id)
    {
        if (in_array($id, $protected, true))
            fatal_lang_error('better_admin_theme_protected_attempt', false);

        if (empty($context['themes'][$id]))
            continue;

        $theme = $context['themes'][$id];
        $valid[$id] = array(
            'id' => $id,
            'name' => isset($theme['name']) ? (string) $theme['name'] : ('Theme ' . $id),
            'version' => isset($theme['version']) ? (string) $theme['version'] : '',
            'theme_dir' => isset($theme['theme_dir']) ? (string) $theme['theme_dir'] : '',
        );
    }

    return $valid;
}

/**
 * Confirmation + execution endpoint for bulk theme deletion.
 */
function betteradmin_theme_bulk_remove()
{
    global $context, $scripturl, $txt, $sourcedir;

    betteradmin_load_language();
    isAllowedTo('admin_forum');
    require_once($sourcedir . '/Subs-Themes.php');

    if (!empty($_POST['better_admin_confirm_delete']))
    {
        checkSession('post');
        validateToken('better-admin-theme-confirm');

        $themes = betteradmin_validate_theme_selection(isset($_POST['themes']) ? $_POST['themes'] : array());
        if (empty($themes))
            fatal_lang_error('better_admin_theme_select_one', false);

        $removed = 0;
        foreach ($themes as $theme)
        {
            // Re-check immediately before each destructive operation.
            if (in_array((int) $theme['id'], betteradmin_protected_theme_ids(), true))
                continue;

            if (remove_theme((int) $theme['id']))
            {
                if (!empty($theme['theme_dir']))
                    remove_dir($theme['theme_dir']);
                $removed++;
            }
        }

        cache_put_data('minimized_css', null);
        redirectexit('action=admin;area=theme;sa=list;betteradmin_removed=' . $removed . ';' . $context['session_var'] . '=' . $context['session_id']);
    }

    checkSession('post');
    validateToken('better-admin-theme-bulk');

    $themes = betteradmin_validate_theme_selection(isset($_POST['themes']) ? $_POST['themes'] : array());
    if (empty($themes))
        fatal_lang_error('better_admin_theme_select_one', false);

    loadTemplate('Themes');
    $context['better_admin_theme_confirm'] = $themes;
    $context['page_title'] = $txt['better_admin_theme_confirm_title'];
    $context['sub_template'] = 'better_admin_theme_confirm';
    createToken('better-admin-theme-confirm');
}

/**
 * Panel shown above SMF's native Theme Settings list.
 */
function template_better_admin_theme_bulk_above()
{
    global $context, $scripturl, $txt, $smcFunc;

    if (empty($context['better_admin_theme_cleanup']))
        return;

    $data = $context['better_admin_theme_cleanup'];

    if (!empty($data['removed']))
        echo '<div class="infobox better_admin_theme_notice">', sprintf($txt['better_admin_theme_removed'], (int) $data['removed']), '</div>';

    echo '<section class="better_admin_theme_bulk_panel">',
        '<div class="cat_bar better_admin_theme_bulk_heading"><h3 class="catbg">', $txt['better_admin_theme_bulk_title'], '</h3></div>',
        '<div class="information better_admin_theme_bulk_intro">', $txt['better_admin_theme_bulk_desc'], '</div>',
        '<form class="better_admin_theme_bulk_form" action="', $scripturl, '?action=admin;area=theme;sa=betteradminbulkremove" method="post" accept-charset="', $context['character_set'], '">',
        '<div class="windowbg better_admin_theme_bulk_list">';

    $removable = 0;
    foreach ($data['themes'] as $theme)
    {
        $name = $smcFunc['htmlspecialchars']($theme['name']);
        $version = $theme['version'] !== '' ? '<span class="better_admin_theme_version">' . $smcFunc['htmlspecialchars']($theme['version']) . '</span>' : '';

        echo '<div class="better_admin_theme_bulk_row', $theme['protected'] ? ' is_protected' : '', '">';

        if ($theme['protected'])
        {
            $label = $theme['protected_reason'] === 'curve' ? $txt['better_admin_theme_curve_protected'] : $txt['better_admin_theme_current_protected'];
            echo '<span class="better_admin_theme_bulk_checkbox better_admin_theme_bulk_checkbox_placeholder" aria-hidden="true"></span>',
                '<div class="better_admin_theme_bulk_name">',
                    '<div class="better_admin_theme_title"><strong>', $name, '</strong>', $version, '</div>',
                    '<span class="better_admin_theme_protected_badge">', $label, '</span>',
                '</div>';
        }
        else
        {
            $removable++;
            echo '<label class="better_admin_theme_bulk_checkbox" title="', $name, '">',
                    '<input type="checkbox" class="better_admin_theme_checkbox" name="themes[]" value="', (int) $theme['id'], '">',
                '</label>',
                '<div class="better_admin_theme_bulk_name">',
                    '<div class="better_admin_theme_title"><strong>', $name, '</strong>', $version, '</div>',
                '</div>';
        }

        echo '</div>';
    }

    if ($removable === 0)
        echo '<div class="better_admin_theme_empty">', $txt['better_admin_theme_none'], '</div>';
    else
        echo '<div class="better_admin_theme_bulk_actions">',
            '<div class="better_admin_theme_bulk_actions_left">',
                '<button type="button" class="button" id="better_admin_theme_select_all">', $txt['better_admin_theme_select_all'], '</button>',
                '<button type="button" class="button" id="better_admin_theme_clear_all">', $txt['better_admin_theme_clear_all'], '</button>',
            '</div>',
            '<button type="submit" class="button better_admin_theme_delete_button" id="better_admin_theme_delete_selected" disabled>', $txt['better_admin_theme_delete_selected'], '</button>',
        '</div>';

    echo '<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">',
        '<input type="hidden" name="', $context['better-admin-theme-bulk_token_var'], '" value="', $context['better-admin-theme-bulk_token'], '">',
        '</div></form></section>';

    if ($removable > 0)
        echo '<script>
(function () {
    var boxes = Array.prototype.slice.call(document.querySelectorAll(".better_admin_theme_checkbox"));
    var submit = document.getElementById("better_admin_theme_delete_selected");
    var selectAll = document.getElementById("better_admin_theme_select_all");
    var clearAll = document.getElementById("better_admin_theme_clear_all");
    function sync() { submit.disabled = !boxes.some(function (box) { return box.checked; }); }
    boxes.forEach(function (box) { box.addEventListener("change", sync); });
    selectAll.addEventListener("click", function () { boxes.forEach(function (box) { box.checked = true; }); sync(); });
    clearAll.addEventListener("click", function () { boxes.forEach(function (box) { box.checked = false; }); sync(); });
    sync();
})();
</script>';
}

function template_better_admin_theme_bulk_below()
{
}

/**
 * Explicit confirmation page. No deletion happens until this form is posted.
 */
function template_better_admin_theme_confirm()
{
    global $context, $scripturl, $txt, $smcFunc;

    echo '<div class="cat_bar"><h3 class="catbg">', $txt['better_admin_theme_confirm_title'], '</h3></div>',
        '<div class="information">', $txt['better_admin_theme_confirm_desc'], '</div>',
        '<form action="', $scripturl, '?action=admin;area=theme;sa=betteradminbulkremove" method="post" accept-charset="', $context['character_set'], '">',
        '<div class="windowbg better_admin_theme_confirm_box"><ul>';

    foreach ($context['better_admin_theme_confirm'] as $theme)
    {
        echo '<li><strong>', $smcFunc['htmlspecialchars']($theme['name']), '</strong>',
            $theme['version'] !== '' ? ' <span class="smalltext">(' . $smcFunc['htmlspecialchars']($theme['version']) . ')</span>' : '',
            '<input type="hidden" name="themes[]" value="', (int) $theme['id'], '"></li>';
    }

    echo '</ul><div class="better_admin_theme_confirm_actions">',
        '<button type="submit" class="button" name="better_admin_confirm_delete" value="1">', $txt['better_admin_theme_confirm_button'], '</button> ',
        '<a class="button" href="', $scripturl, '?action=admin;area=theme;sa=list">', $txt['better_admin_theme_cancel'], '</a>',
        '</div>',
        '<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">',
        '<input type="hidden" name="', $context['better-admin-theme-confirm_token_var'], '" value="', $context['better-admin-theme-confirm_token'], '">',
        '</div></form>';
}

