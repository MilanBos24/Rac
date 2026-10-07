<footer class="main-footer" style="background-image: url(assets/images/footer-bg-1.png);">
            <div class="container">
                <div class="main-footer__top wow fadeInUp animated" data-wow-delay="100ms">
                    <a href="<?= htmlspecialchars(racUrl('index.php'), ENT_QUOTES, 'UTF-8'); ?>" class="main-footer__logo">
                        <img src="assets/images/loader-sr.png" alt="Štefan Rác" width="55" height="55">
                    </a><!-- /.footer-logo -->
                    <div class="main-footer__social">
                        <a href="https://twitter.com/"><i class="fab fa-twitter"></i></a>
                        <a href="https://www.facebook.com/"><i class="fab fa-facebook"></i></a>
                        <a href="https://www.pinterest.com/"><i class="fab fa-pinterest-p"></i></a>
                        <a href="https://www.instagram.com/"><i class="fab fa-instagram"></i></a>
                    </div><!-- /.footer-social -->
                </div><!-- footer-top -->

                <div class="row">
                    <div class="col-lg-8 col-md-6 wow fadeInUp animated" data-wow-delay="200ms">
                        <div class="main-footer__about">
                            <p class="footer-widget__text"><?= htmlspecialchars(__('footer.contact'), ENT_QUOTES, 'UTF-8'); ?></p>
                            <a href="mailto:stefan.rac1@gmail.com">stefan.rac1@gmail.com</a>
                        </div><!-- /.footer-widget -->
                    </div>

                    <div class="col-lg-2 col-md-3 wow fadeInUp animated" data-wow-delay="300ms">
                        <div class="main-footer__navmenu">
                            <ul>
                                <li><a href="#">Poznej sám sebe</a></li>
                                <li><a href="#">Láska</a></li>
                                <li><a href="#">Život</a></li>
                                <li><a href="#">Mezi psychopaty</a></li>
                            </ul><!-- /.list-unstyled -->
                        </div><!-- /.footer-widget -->
                    </div>

                    <div class="col-lg-2 col-md-3 wow fadeInUp animated" data-wow-delay="400ms">
                        <div class="main-footer__navmenu">
                            <ul>
                                <li><a href="#">Cookies</a></li>
                                <li><a href="#">GDPR</a></li>
                                <li><a href="#"><?= htmlspecialchars(__('footer.terms'), ENT_QUOTES, 'UTF-8'); ?></a></li>
                            </ul><!-- /.list-unstyled -->
                        </div><!-- /.footer-widget -->
                    </div>
                </div><!-- /.row -->

                <p class="main-footer__copyright wow fadeInUp animated" data-wow-delay="500ms">
                    © <span class="dynamic-year"></span> Štefan Rác. <?= htmlspecialchars(__('footer.all_rights_reserved'), ENT_QUOTES, 'UTF-8'); ?>
                </p>
            </div><!-- /.container -->
        </footer><!-- /.main-footer -->

    </div><!-- /.page-wrapper -->
