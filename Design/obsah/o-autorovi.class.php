<div class="stricky-header stricked-menu main-menu">
            <div class="sticky-header__content"></div><!-- /.sticky-header__content -->
        </div><!-- /.stricky-header -->

        <section class="page-header">
            <div class="page-header__bg"></div>
            <div class="page-header__overlay"></div>
            <div class="container">
                <ul class="page-header__breadcrumb list-unstyled">
                    <li>
                        <a href="<?= htmlspecialchars(racUrl('index.php'), ENT_QUOTES, 'UTF-8'); ?>">
                            <?= htmlspecialchars(__('breadcrumb.home'), ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </li>
                    <li>
                        <span><?= htmlspecialchars(__('breadcrumb.author'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                </ul>

                <h2 class="page-header__title">
                    <?= htmlspecialchars(racCmsValue('header', 'title', 'Štefan Rác'), ENT_QUOTES, 'UTF-8'); ?>
                </h2>
            </div>
        </section>

        <section class="blog-page">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8 wow fadeInUp animated" data-wow-delay="200ms">
                        <div class="about-two__left">
                            <div class="section-title">
                                <h2 class="section-title__title">
                                    <?= htmlspecialchars(racCmsValue('content', 'heading', __('author.preparing')), ENT_QUOTES, 'UTF-8'); ?>
                                </h2>
                            </div>

                            <?php
                            $authorText = trim(racCmsValue('content', 'text', ''));
                            if ($authorText !== ''):
                            ?>
                                <div class="about-one__content__text-two">
                                    <?= nl2br(htmlspecialchars($authorText, ENT_QUOTES, 'UTF-8'), false); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>