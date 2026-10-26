(function () {
    'use strict';

    function initBetterAdminBulk() {
        var cfg = window.BetterAdminBulk;
        var form = document.getElementById('quickModForm');
        var actions = document.getElementById('quick_actions');

        if (!cfg || !form || !actions)
            return;

        actions.classList.add('better_admin_actions');

        // SMF's stock submit button has a generic inline confirmation. Better Admin
        // replaces it with action-specific confirmations below.
        var submit = actions.querySelector('input[type="submit"]');
        if (submit)
            submit.removeAttribute('onclick');

        form.addEventListener('submit', function (event) {
            var select = form.querySelector('select[name="qaction"]');
            var checked = form.querySelectorAll('input[name="topics[]"]:checked');

            if (!select || !select.value) {
                event.preventDefault();
                return;
            }

            if (!checked.length) {
                event.preventDefault();
                window.alert(cfg.selectAtLeastOne);
                return;
            }

            var message = '';
            if (select.value === 'remove')
                message = cfg.confirmDelete;
            else if (select.value === 'move')
                message = cfg.confirmMove;
            else if (select.value === 'review')
                message = cfg.confirmReview;
            else if (select.value === 'review_restore')
                message = cfg.confirmRestore;

            if (message && !window.confirm(message)) {
                event.preventDefault();
                return;
            }

            if (cfg.reviewActions.indexOf(select.value) !== -1)
                form.action = cfg.url;
        });
    }


    function initBetterAdmin() {
        initBetterAdminBulk();
    }

    if (document.readyState === 'loading')
        document.addEventListener('DOMContentLoaded', initBetterAdmin);
    else
        initBetterAdmin();
})();
