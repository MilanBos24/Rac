(function () {
    var toggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('adminSidebar');

    if (!toggle || !sidebar) {
        return;
    }

    toggle.addEventListener('click', function () {
        document.body.classList.toggle('sidebar-collapsed');
    });
})();
