/* RAC / BOS24 behavior bridge, version 2026-10-12-01 */
(function () {
    'use strict';
    var toggle = document.getElementById('sidebarToggle');
    if (toggle) {
        var updateExpanded = function () {
            var collapsed = document.body.classList.contains('sidebar-collapsed');
            toggle.setAttribute('aria-expanded', String(!collapsed));
        };
        updateExpanded();
        toggle.addEventListener('click', function () { window.requestAnimationFrame(updateExpanded); });
    }
    // Bootstrap handles tabs when available. Fallback for missing Bootstrap assets.
    if (!window.bootstrap || !window.bootstrap.Tab) {
        document.querySelectorAll('.billing-content [data-bs-toggle="tab"]').forEach(function (button) {
            button.addEventListener('click', function () {
                var container = button.closest('.tabs');
                if (!container) return;
                var selector = button.getAttribute('data-bs-target');
                if (!selector || selector.charAt(0) !== '#') return;
                var pane = document.getElementById(selector.substring(1));
                if (!pane) return;
                container.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (tab) {
                    tab.classList.remove('active'); tab.setAttribute('aria-selected', 'false');
                });
                container.querySelectorAll('.tab-pane').forEach(function (item) {
                    item.classList.remove('active', 'show');
                });
                button.classList.add('active'); button.setAttribute('aria-selected', 'true');
                pane.classList.add('active', 'show');
            });
        });
    }
})();
