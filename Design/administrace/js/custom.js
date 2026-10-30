/* RESPONSIVNÍ ZOBRAZENÍ DASHBOARDU - START
   JS sleduje, jestli se text v jednotlivých položkách zalomil na více øádkù.
   Pokud ano, pøepne danou kartu i celou její sekci do kompaktního zobrazení
   a souèasnì skryje desktopovou hlavièku sloupcù. */
document.addEventListener('DOMContentLoaded', function () {
    function isWrapped(el) {
        if (!el) {
            return false;
        }

        var style = window.getComputedStyle(el);
        var lineHeight = parseFloat(style.lineHeight);

        if (!lineHeight || isNaN(lineHeight)) {
            lineHeight = parseFloat(style.fontSize) * 1.3;
        }

        return el.scrollHeight > (lineHeight * 1.55);
    }

    function checkDashboardWrapping() {
        document.querySelectorAll('.dashboard-responsive-section').forEach(function (section) {
            var sectionCompact = false;

            section.querySelectorAll('.dashboard-item-card').forEach(function (card) {
                var shouldCompact = false;

                card.classList.remove('dashboard-compact');

                card.querySelectorAll('.dashboard-watch-wrap').forEach(function (el) {
                    if (isWrapped(el)) {
                        shouldCompact = true;
                    }
                });

                if (shouldCompact) {
                    card.classList.add('dashboard-compact');
                    sectionCompact = true;
                }
            });

            if (sectionCompact) {
                section.classList.add('dashboard-section-compact');
            } else {
                section.classList.remove('dashboard-section-compact');
            }
        });
    }

    var resizeTimer = null;

    function scheduleCheck() {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(function () {
            window.requestAnimationFrame(checkDashboardWrapping);
        }, 80);
    }

    checkDashboardWrapping();
    window.addEventListener('resize', scheduleCheck);

    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(checkDashboardWrapping);
    }
});
/* RESPONSIVNÍ ZOBRAZENÍ DASHBOARDU - KONEC */
/* LEVE MENU - ULOZENI STAVU A OBNOVENI OTEVRENYCH UROVNI */
(function ($) {
    'use strict';

    $(function () {
        var $html = $('html');
        var $menu = $('#sidebar-left #menu').first();
        var desktopQuery = window.matchMedia('(min-width: 768px)');
        var storageKey = 'bos24-sidebar-left-state';
        var expandedItems = [];

        if (!$menu.length) {
            return;
        }

        function saveSidebarState(state) {
            try {
                window.localStorage.setItem(storageKey, state);
            } catch (error) {
                // Menu bude fungovat i bez dostupného localStorage.
            }
        }

        function rememberExpandedItems() {
            expandedItems = $menu.find('li.nav-expanded').toArray();
        }

        function closeExpandedItems() {
            $menu.find('li.nav-expanded')
                .removeClass('nav-expanded')
                .children('ul.nav-children')
                .stop(true, true)
                .hide();
        }

        function restoreExpandedItems() {
            closeExpandedItems();

            $(expandedItems).each(function () {
                $(this)
                    .addClass('nav-expanded')
                    .children('ul.nav-children')
                    .stop(true, true)
                    .show();
            });
        }

        $(window).on(
            'sidebar-left-toggle.customSidebarState',
            function (event, toggle) {
                if (!desktopQuery.matches || !toggle) {
                    return;
                }

                // Uživatel menu sbalil.
                if (toggle.added) {
                    rememberExpandedItems();
                    closeExpandedItems();
                    saveSidebarState('collapsed');
                }

                // Uživatel menu rozbalil.
                if (toggle.removed) {
                    restoreExpandedItems();
                    saveSidebarState('expanded');
                }
            }
        );

        // Pøi naètení sbalené stránky skryjeme také otevøená podmenu.
        if (desktopQuery.matches && $html.hasClass('sidebar-left-collapsed')) {
            rememberExpandedItems();
            closeExpandedItems();
        }
    });
})(jQuery);
