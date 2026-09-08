<?php
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
?>
<body class="custom-cursor">

    <div class="custom-cursor__cursor"></div>
    <div class="custom-cursor__cursor-two"></div>

    <div class="preloader">
        <div class="preloader__image" style="background-image: url(assets/images/loader.png);"></div>
    </div>
    <!-- /.preloader -->
    <div class="page-wrapper">
        <header class="main-header">
            <nav class="main-menu">
                <div class="container-fluid">
                    <div class="main-menu__logo">
                        <a href="<?= htmlspecialchars(racUrl('index.php'), ENT_QUOTES, 'UTF-8'); ?>">
                            <img src="assets/images/logo-light.png" width="34" height="34" alt="RAC">
                        </a>
                    </div><!-- /.main-menu__logo -->

                    <div class="main-menu__nav">
                        <ul class="main-menu__list">
                            <li<?= $currentPage === 'index.php' ? ' class="current"' : ''; ?>>
                                <a href="<?= htmlspecialchars(racUrl('index.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(__('menu.home'), ENT_QUOTES, 'UTF-8'); ?></a>
                            </li>
                            <li<?= $currentPage === 'o-autorovi.php' ? ' class="current"' : ''; ?>>
                                <a href="<?= htmlspecialchars(racUrl('o-autorovi.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(__('menu.author'), ENT_QUOTES, 'UTF-8'); ?></a>
                            </li>
                            <li<?= $currentPage === 'knihy.php' ? ' class="current"' : ''; ?>>
                                <a href="<?= htmlspecialchars(racUrl('knihy.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(__('menu.books'), ENT_QUOTES, 'UTF-8'); ?></a>
                            </li>
                            <li<?= $currentPage === 'pripravujeme.php' ? ' class="current"' : ''; ?>>
                                <a href="<?= htmlspecialchars(racUrl('pripravujeme.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(__('menu.upcoming'), ENT_QUOTES, 'UTF-8'); ?></a>
                            </li>
                            <li<?= $currentPage === 'kontakt.php' ? ' class="current"' : ''; ?>>
                                <a href="<?= htmlspecialchars(racUrl('kontakt.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(__('menu.contact'), ENT_QUOTES, 'UTF-8'); ?></a>
                            </li>
                        </ul>
                    </div><!-- /.main-menu__nav -->

                    <div class="main-menu__right">
                        <a href="#" class="main-menu__toggler mobile-nav__toggler">
                            <i class="fa fa-bars"></i>
                        </a><!-- /.mobile menu btn -->

                        <a href="#" class="main-menu__search search-toggler" aria-label="<?= htmlspecialchars(__('action.search'), ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="icon-magnifying-glass"></i>
                        </a><!-- /.search btn -->

                        <a href="<?= htmlspecialchars(racUrl('kosik.php'), ENT_QUOTES, 'UTF-8'); ?>" class="main-menu__cart cart-toggler" aria-label="<?= htmlspecialchars(__('cart.title'), ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="icon-shopping-cart"></i>
                        </a><!-- /.cart btn -->

                        <details class="main-menu__language">
                            <summary class="main-menu__language-toggle" aria-label="<?= htmlspecialchars(__('language.label'), ENT_QUOTES, 'UTF-8'); ?>">
                                <i class="fas fa-globe"></i>
                                <span><?= htmlspecialchars(strtoupper($currentLanguage), ENT_QUOTES, 'UTF-8'); ?></span>
                            </summary>
                            <ul class="main-menu__language-menu">
                                <?php foreach (SUPPORTED_LANGUAGES as $languageCode): ?>
                                    <li>
                                        <a href="<?= htmlspecialchars(racLanguageUrl($languageCode), ENT_QUOTES, 'UTF-8'); ?>"<?= $languageCode === $currentLanguage ? ' class="is-active" aria-current="true"' : ''; ?>>
                                            <span><?= htmlspecialchars(LANGUAGE_NAMES[$languageCode] ?? strtoupper($languageCode), ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span class="main-menu__language-code"><?= htmlspecialchars(strtoupper($languageCode), ENT_QUOTES, 'UTF-8'); ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </details>
                    </div><!-- /.main-menu__right -->
                </div><!-- /.container-fluid -->
            </nav>
            <!-- /.main-menu -->
        </header><!-- /.main-header -->