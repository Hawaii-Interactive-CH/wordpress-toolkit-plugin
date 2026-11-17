/**
 * Pattern Sync
 *
 * Resyncs the toolkit patterns already inserted in the content (Toolkit page > Patterns).
 * Scan the contents using the checked patterns, preview the changes, then apply them,
 * in batches to avoid PHP timeouts on large sites.
 */
(function () {
    'use strict';

    if (typeof hithtoPatternSync === 'undefined') {
        return;
    }

    var config = hithtoPatternSync;

    function init() {
        var toggle = document.getElementById('hithto-pattern-sync-toggle');
        var panel = document.getElementById('hithto-pattern-sync');
        if (!toggle || !panel) {
            return;
        }

        var previewBtn = document.getElementById('hithto-pattern-sync-preview');
        var applyBtn = document.getElementById('hithto-pattern-sync-apply');
        var spinner = document.getElementById('hithto-pattern-sync-spinner');
        var report = document.getElementById('hithto-pattern-sync-report');
        var list = document.getElementById('hithto-pattern-sync-list');

        // Contents to update, found by the last preview
        var pending = { patterns: [], ids: [] };

        toggle.addEventListener('click', function () {
            panel.hidden = !panel.hidden;
            toggle.setAttribute('aria-expanded', String(!panel.hidden));
        });

        if (!previewBtn || !list) {
            return;
        }

        // A new selection needs a new preview
        list.addEventListener('change', function () {
            applyBtn.hidden = true;
            pending = { patterns: [], ids: [] };
        });

        previewBtn.addEventListener('click', function () {
            var patterns = checkedPatterns(list);
            if (!patterns.length) {
                showMessage(report, 'warning', 'Select at least one pattern.');
                return;
            }

            busy(true);
            applyBtn.hidden = true;
            report.innerHTML = '';

            request({ step: 'scan', patterns: patterns })
                .then(function (data) {
                    return runBatches('preview', patterns, data.ids, function (done, total) {
                        showMessage(report, 'info', 'Analysing… ' + done + ' / ' + total);
                    });
                })
                .then(function (reports) {
                    renderReport(report, reports, false);
                    pending = {
                        patterns: patterns,
                        ids: reports.filter(function (r) { return r.updated > 0; }).map(function (r) { return r.id; })
                    };
                    applyBtn.hidden = pending.ids.length === 0;
                })
                .catch(function (error) {
                    showMessage(report, 'error', error.message);
                })
                .then(function () {
                    busy(false);
                });
        });

        applyBtn.addEventListener('click', function () {
            if (!pending.ids.length) {
                return;
            }

            busy(true);
            applyBtn.hidden = true;

            runBatches('apply', pending.patterns, pending.ids, function (done, total) {
                showMessage(report, 'info', 'Updating… ' + done + ' / ' + total);
            })
                .then(function (reports) {
                    renderReport(report, reports, true);
                    pending = { patterns: [], ids: [] };
                })
                .catch(function (error) {
                    showMessage(report, 'error', error.message);
                })
                .then(function () {
                    busy(false);
                });
        });

        function busy(state) {
            previewBtn.disabled = state;
            applyBtn.disabled = state;
            spinner.classList.toggle('is-active', state);
        }
    }

    function checkedPatterns(list) {
        return Array.prototype.map.call(list.querySelectorAll('input[type="checkbox"]:checked'), function (input) {
            return input.value;
        });
    }

    /**
     * Process the post IDs in batches, one request after the other.
     */
    function runBatches(step, patterns, ids, onProgress) {
        var reports = [];
        var index = 0;

        function next() {
            onProgress(index, ids.length);
            if (index >= ids.length) {
                return Promise.resolve(reports);
            }
            var batch = ids.slice(index, index + config.batch);
            index += batch.length;
            return request({ step: step, patterns: patterns, ids: batch }).then(function (data) {
                reports = reports.concat(data.reports);
                return next();
            });
        }

        return next();
    }

    function request(params) {
        var body = new URLSearchParams();
        body.append('action', config.action);
        body.append('nonce', config.nonce);
        body.append('step', params.step);
        (params.patterns || []).forEach(function (name) { body.append('patterns[]', name); });
        (params.ids || []).forEach(function (id) { body.append('ids[]', id); });

        return fetch(window.ajaxurl, { method: 'POST', credentials: 'same-origin', body: body })
            .then(function (response) {
                return response.json().catch(function () {
                    throw new Error('Unexpected server response (' + response.status + ').');
                });
            })
            .then(function (json) {
                if (!json.success) {
                    throw new Error(typeof json.data === 'string' ? json.data : 'The request failed.');
                }
                return json.data;
            });
    }

    function showMessage(container, type, text) {
        container.innerHTML = '';
        var notice = document.createElement('div');
        notice.className = 'notice inline notice-' + type;
        var p = document.createElement('p');
        p.textContent = text;
        notice.appendChild(p);
        container.appendChild(notice);
    }

    function renderReport(container, reports, applied) {
        var updated = reports.filter(function (r) { return r.updated > 0; });
        var skipped = reports.filter(function (r) { return r.skipped.length > 0; });
        var errors = reports.filter(function (r) { return r.error; });
        var patternCount = updated.reduce(function (sum, r) { return sum + r.updated; }, 0);

        var summary;
        if (applied) {
            var saved = updated.filter(function (r) { return r.saved; }).length;
            summary = saved + ' content(s) updated, ' + patternCount + ' pattern(s) resynced.';
        } else if (updated.length) {
            summary = updated.length + ' content(s), ' + patternCount + ' pattern(s) will be resynced.';
        } else {
            summary = 'Every pattern is already up to date.';
        }

        showMessage(container, errors.length ? 'error' : (skipped.length ? 'warning' : 'success'), summary);

        if (updated.length) {
            container.appendChild(section(applied ? 'Updated' : 'Will be updated', updated, function (r) {
                return r.updated + ' pattern(s)' + (r.error ? ' — ' + r.error : '');
            }));
        }

        if (skipped.length) {
            container.appendChild(section('Left as is', skipped, function (r) {
                return r.skipped.map(function (s) { return s.pattern + ': ' + s.reason; }).join(' · ');
            }));
        }

        var unchangedErrors = errors.filter(function (r) { return !r.updated; });
        if (unchangedErrors.length) {
            container.appendChild(section('Errors', unchangedErrors, function (r) { return r.error; }));
        }
    }

    function section(title, reports, detail) {
        var wrapper = document.createElement('div');
        var heading = document.createElement('h4');
        heading.textContent = title;
        wrapper.appendChild(heading);

        var ul = document.createElement('ul');
        ul.className = 'ul-disc';
        reports.forEach(function (r) {
            var li = document.createElement('li');
            var label = r.title + (r.type ? ' (' + r.type + ')' : '');
            if (r.link) {
                var a = document.createElement('a');
                a.href = r.link;
                a.target = '_blank';
                a.rel = 'noopener';
                a.textContent = label;
                li.appendChild(a);
            } else {
                li.appendChild(document.createTextNode(label));
            }
            li.appendChild(document.createTextNode(' — ' + detail(r)));
            ul.appendChild(li);
        });
        wrapper.appendChild(ul);
        return wrapper;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
