(function () {
    'use strict';

    function initBetterAdminReplySelection() {
        var cfg = window.BetterAdminReplies;
        var form = document.getElementById('quickModForm');

        if (!cfg || !form)
            return;

        // SMF renders each post as a direct child of quickModForm with an id
        // beginning with "msg". Do not depend on theme-specific or injected
        // CSS classes here; the first post is always the topic starter.
        var posts = Array.prototype.slice.call(form.querySelectorAll('[id^=\"msg\"]')).filter(function (node) {
            return node && node.id && /^msg\d+$/.test(node.id);
        });

        if (posts.length < 2)
            return;

        var starter = posts[0];
        var replies = posts.slice(1);

        // Label every reply counter clearly: Reply #1, Reply #2, etc.
        replies.forEach(function (post) {
            var number = post.querySelector('.page_number');
            if (!number)
                return;

            var text = number.textContent.trim();
            if (text.indexOf(cfg.replyLabel + ' ') !== 0)
                number.textContent = cfg.replyLabel + ' ' + text;
        });

        if (!cfg.enabled)
            return;

        var selectable = [];

        replies.forEach(function (post) {
            var number = post.querySelector('.keyinfo .page_number');
            if (!number || post.querySelector('.better_admin_reply_select'))
                return;

            var messageId = post.id.replace(/^msg/, '');

            var label = document.createElement('label');
            label.className = 'better_admin_reply_select';
            label.title = cfg.selectAllLabel;

            var input = document.createElement('input');
            input.type = 'checkbox';
            input.className = 'better_admin_reply_checkbox';
            input.name = 'better_admin_replies[]';
            input.value = messageId;
            input.setAttribute('aria-label', cfg.replyLabel + ' ' + number.textContent.replace(/^Reply\s*/i, ''));
            input.dataset.underReview = Array.isArray(cfg.reviewedReplies) && cfg.reviewedReplies.indexOf(parseInt(messageId, 10)) !== -1 ? '1' : '0';

            label.appendChild(input);
            // Keep the checkbox inside the floated Reply #X span so it
            // stays immediately to the right of the reply number instead
            // of falling back to the left edge of the keyinfo row.
            number.appendChild(label);
            selectable.push(input);
        });

        // Bulk reply management only appears when there is more than one
        // reply. Remove the temporary per-reply selectors as well so a
        // single-reply topic remains unchanged.
        if (selectable.length < 2) {
            replies.forEach(function (post) {
                var selector = post.querySelector('.better_admin_reply_select');
                if (selector)
                    selector.remove();
            });
            return;
        }

        if (document.getElementById('better_admin_reply_bulk_bar'))
            return;

        var bar = document.createElement('div');
        bar.id = 'better_admin_reply_bulk_bar';
        bar.className = 'better_admin_reply_bulk_bar';

        var selectLabel = document.createElement('label');
        selectLabel.className = 'better_admin_select_all_replies';

        var selectAll = document.createElement('input');
        selectAll.type = 'checkbox';
        selectAll.id = 'better_admin_select_all_replies';

        var text = document.createElement('span');
        text.textContent = cfg.selectAllLabel;

        selectLabel.appendChild(selectAll);
        selectLabel.appendChild(text);
        bar.appendChild(selectLabel);

        var deleteButton = document.createElement('button');
        deleteButton.type = 'button';
        deleteButton.className = 'button better_admin_delete_selected_replies';
        deleteButton.textContent = cfg.deleteLabel;
        deleteButton.disabled = true;
        bar.appendChild(deleteButton);

        var reviewButton = null;
        if (cfg.canReview) {
            reviewButton = document.createElement('button');
            reviewButton.type = 'button';
            reviewButton.className = 'button better_admin_review_selected_replies';
            reviewButton.textContent = cfg.reviewLabel;
            reviewButton.disabled = true;
            bar.appendChild(reviewButton);
        }

        var restoreButton = null;
        if (cfg.canReview) {
            restoreButton = document.createElement('button');
            restoreButton.type = 'button';
            restoreButton.className = 'button better_admin_restore_selected_replies';
            restoreButton.textContent = cfg.restoreLabel;
            restoreButton.disabled = true;
            bar.appendChild(restoreButton);
        }

        // This placement is deliberate: immediately after the topic starter,
        // before Reply #1, so the starter can never be mistaken for a reply.
        starter.parentNode.insertBefore(bar, starter.nextSibling);

        function updateSelectionState() {
            var selected = selectable.filter(function (item) { return item.checked; });
            var anyChecked = selected.length > 0;
            var hasNormal = selected.some(function (item) { return item.dataset.underReview !== '1'; });
            var hasReviewed = selected.some(function (item) { return item.dataset.underReview === '1'; });

            selectAll.checked = selectable.every(function (item) { return item.checked; });
            selectAll.indeterminate = !selectAll.checked && anyChecked;
            deleteButton.disabled = !anyChecked;
            if (reviewButton)
                reviewButton.disabled = !hasNormal;
            if (restoreButton)
                restoreButton.disabled = !hasReviewed;
        }

        selectAll.addEventListener('change', function () {
            selectable.forEach(function (box) {
                box.checked = selectAll.checked;
            });
            updateSelectionState();
        });

        selectable.forEach(function (box) {
            box.addEventListener('change', updateSelectionState);
        });

        function submitSelected(actionUrl, confirmation) {
            var selected = selectable.filter(function (box) { return box.checked; });
            if (!selected.length) {
                window.alert(cfg.selectAtLeastOne);
                return;
            }

            if (!window.confirm(confirmation))
                return;

            var actionForm = document.createElement('form');
            actionForm.method = 'post';
            actionForm.action = actionUrl;
            actionForm.style.display = 'none';

            selected.forEach(function (box) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'better_admin_replies[]';
                input.value = box.value;
                actionForm.appendChild(input);
            });

            var session = document.createElement('input');
            session.type = 'hidden';
            session.name = cfg.sessionVar;
            session.value = cfg.sessionId;
            actionForm.appendChild(session);

            document.body.appendChild(actionForm);
            actionForm.submit();
        }

        deleteButton.addEventListener('click', function () {
            submitSelected(cfg.deleteUrl, cfg.confirmDelete);
        });

        if (reviewButton) {
            reviewButton.addEventListener('click', function () {
                submitSelected(cfg.reviewUrl, cfg.confirmReview);
            });
        }

        if (restoreButton) {
            restoreButton.addEventListener('click', function () {
                submitSelected(cfg.restoreUrl, cfg.confirmRestore);
            });
        }

        updateSelectionState();
    }

    if (document.readyState === 'loading')
        document.addEventListener('DOMContentLoaded', initBetterAdminReplySelection);
    else
        initBetterAdminReplySelection();
})();
