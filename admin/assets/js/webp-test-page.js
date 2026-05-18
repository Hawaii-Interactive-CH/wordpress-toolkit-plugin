(function () {
    function initQueueProcessor() {
        var btn = document.getElementById('process-now-btn');
        if (!btn) return;
        var countEl = document.getElementById('queue-count');
        var notice  = document.getElementById('queue-status-notice');
        var nonce   = (typeof hithtoWebpTest !== 'undefined') ? hithtoWebpTest.nonce : '';

        function processQueue() {
            btn.disabled    = true;
            btn.textContent = '⏳ Processing...';
            fetch(ajaxurl, {
                method:  'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body:    'action=hithto_process_webp_queue&nonce=' + nonce
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    var remaining = data.data.remaining;
                    countEl.textContent = remaining;
                    if (remaining > 0) {
                        btn.disabled    = false;
                        btn.textContent = '▶ Process Queue Now (' + remaining + ' left)';
                    } else {
                        notice.style.display = 'none';
                        btn.textContent      = '✅ Queue empty — reload to refresh stats';
                    }
                } else {
                    btn.disabled    = false;
                    btn.textContent = '▶ Process Queue Now';
                }
            })
            .catch(function () {
                btn.disabled    = false;
                btn.textContent = '▶ Process Queue Now';
            });
        }

        btn.addEventListener('click', processQueue);
    }

    window.filterLogs = function (level, btn) {
        document.querySelectorAll('.log-filter-bar .button').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        document.querySelectorAll('.log-row').forEach(function (row) {
            row.style.display = (level === 'all' || row.dataset.level === level) ? '' : 'none';
        });
    };

    document.addEventListener('DOMContentLoaded', initQueueProcessor);
})();
