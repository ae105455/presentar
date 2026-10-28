/* ==========================================================================
   AUREAMED - INTERACCIONES JS PRINCIPALES
   Control de menú lateral, submenús, confirmaciones SweetAlert2 y botones
   ========================================================================== */

$(document).ready(function() {

    var $menu = $('.nav-lateral-menu');
    if ($menu.length) {
        var currentPage = window.location.pathname.split('/').pop() || 'panel.php';
        $.get('./sidebar_menu.php', { page: currentPage })
            .done(function(menuHtml) {
                $menu.html(menuHtml);
            });
    }

    /* --- 1. Toggle de Submenús en el Menú Lateral --- */
    $(document).on('click', '.nav-btn-submenu', function(e) {
        e.preventDefault();
        var $this = $(this);
        var $subMenu = $this.next('ul');
        var $iconChevron = $this.find('.fa-chevron-down');

        if ($subMenu.hasClass('show-nav-lateral-submenu')) {
            $subMenu.slideUp(200, function() {
                $subMenu.removeClass('show-nav-lateral-submenu');
            });
            $this.removeClass('active');
            $this.attr('aria-expanded', 'false');
            $iconChevron.removeClass('fa-rotate-180');
        } else {
            $subMenu.slideDown(200, function() {
                $subMenu.addClass('show-nav-lateral-submenu');
            });
            $this.addClass('active');
            $this.attr('aria-expanded', 'true');
            $iconChevron.addClass('fa-rotate-180');
        }
    });
    $(document).on('click', '.show-nav-lateral', function(e) {
        e.preventDefault();
        var $navLateral = $('.nav-lateral');
        var $pageContent = $('.page-content');
        var $backdrop = $('.nav-lateral-bg');

        if ($navLateral.hasClass('active')) {
            $navLateral.removeClass('active');
            $pageContent.removeClass('active');
            $backdrop.removeClass('show-nav-lateral-active');
        } else {
            $navLateral.addClass('active');
            $pageContent.addClass('active');
            $backdrop.addClass('show-nav-lateral-active');
        }
    });


    /* --- 2. Toggle del Menú Lateral (Sidebar) --- */
    // Cerrar sidebar al hacer clic en el backdrop (en móvil)
    $(document).on('click', '.nav-lateral-bg', function() {
        $('.nav-lateral').removeClass('active');
        $('.page-content').removeClass('active');
        $(this).removeClass('show-nav-lateral-active');
    });

    /* --- 3. Confirmación de Cierre de Sesión --- */
    $(document).on('click', '.btn-exit-system', function(e) {
        e.preventDefault();
        var logoutUrl = $(this).attr('href') || 'logout.php';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Cerrar sesión?',
                text: "Estás a punto de salir del sistema de AureaMed.",
                icon: 'question',
                type: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0056b3',
                cancelButtonColor: '#dc3545',
                confirmButtonText: '<i class="fas fa-sign-out-alt"></i> Sí, salir',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then(function(result) {
                if (result.isConfirmed || result.value === true || result.value) {
                    window.location.href = logoutUrl;
                }
            });
        } else {
            if (confirm("¿Estás seguro de cerrar sesión y salir del sistema?")) {
                window.location.href = logoutUrl;
            }
        }
    });

    /* --- 4. Tooltips y Efectos de Clic --- */
    $('[data-toggle="tooltip"]').tooltip();

});