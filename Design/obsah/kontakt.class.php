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
                        <span><?= htmlspecialchars(__('breadcrumb.contact'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                </ul>

                <h2 class="page-header__title">
                    <?= htmlspecialchars(racCmsValue('header', 'title', __('page.contact_information')), ENT_QUOTES, 'UTF-8'); ?>
                </h2>
            </div>
        </section>

        <section class="contact-two">
            <div class="container wow fadeInUp animated" data-wow-delay="300ms">
                <div class="section-title text-center">
                    <h5 class="section-title__tagline section-title__tagline--has-dots">
                        <?= htmlspecialchars(racCmsValue('form_intro', 'tagline', __('contact.form_label')), ENT_QUOTES, 'UTF-8'); ?>
                    </h5>

                    <h2 class="section-title__title">
                        <?= nl2br(htmlspecialchars(racCmsValue('form_intro', 'title', __('contact.form_title')), ENT_QUOTES, 'UTF-8'), false); ?>
                    </h2>
                </div>

                <div class="contact-one__left text-center">
                    <div class="contact-one__form-box">
                        <form action="assets/inc/sendemail.php" class="contact-one__form contact-form-validated" novalidate="novalidate">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="contact-one__input-box">
                                        <input type="text" placeholder="<?= htmlspecialchars(__('contact.name'), ENT_QUOTES, 'UTF-8'); ?>" name="name">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="contact-one__input-box">
                                        <input type="email" placeholder="<?= htmlspecialchars(__('contact.email'), ENT_QUOTES, 'UTF-8'); ?>" name="email">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="contact-one__input-box">
                                        <input type="text" placeholder="<?= htmlspecialchars(__('contact.phone'), ENT_QUOTES, 'UTF-8'); ?>" name="phone">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="contact-one__input-box">
                                        <select class="selectpicker" aria-label="<?= htmlspecialchars(__('contact.subject.creation'), ENT_QUOTES, 'UTF-8'); ?>">
                                            <option selected><?= htmlspecialchars(__('contact.subject.creation'), ENT_QUOTES, 'UTF-8'); ?></option>
                                            <option value="1"><?= htmlspecialchars(__('contact.subject.option1'), ENT_QUOTES, 'UTF-8'); ?></option>
                                            <option value="2"><?= htmlspecialchars(__('contact.subject.option2'), ENT_QUOTES, 'UTF-8'); ?></option>
                                            <option value="3"><?= htmlspecialchars(__('contact.subject.option3'), ENT_QUOTES, 'UTF-8'); ?></option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="contact-one__input-box text-message-box">
                                        <textarea name="message" placeholder="<?= htmlspecialchars(__('contact.message'), ENT_QUOTES, 'UTF-8'); ?>"></textarea>
                                    </div>

                                    <div class="contact-one__btn-box">
                                        <button type="submit" class="ogency-btn">
                                            <?= htmlspecialchars(__('contact.send'), ENT_QUOTES, 'UTF-8'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div class="result"></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="contact-info">
            <div class="container">
                <div class="contact-info__wrapper">
                    <div class="row">
                        <div class="col-xl-4 col-md-6">
                            <div class="contact-info__item">
                                <div class="contact-info__item__icon"><span class="icon-place"></span></div>
                                <h3 class="contact-info__item__title"><?= htmlspecialchars(__('contact.address'), ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p class="contact-info__item__text">
                                    <?= nl2br(htmlspecialchars(racCmsValue('contact_info', 'address', "Ulice číslo popisné\nKarvniná, ČR"), ENT_QUOTES, 'UTF-8'), false); ?>
                                </p>
                            </div>
                        </div>

                        <div class="col-xl-4 col-md-6">
                            <div class="contact-info__item">
                                <div class="contact-info__item__icon"><span class="icon-phone"></span></div>
                                <h3 class="contact-info__item__title"><?= htmlspecialchars(__('contact.contact'), ENT_QUOTES, 'UTF-8'); ?></h3>

                                <p class="contact-info__item__text">
                                    <?php
                                    $contactEmail = trim(racCmsValue('contact_info', 'email', 'stefan.rac1@gmail.com'));
                                    $contactPhone = trim(racCmsValue('contact_info', 'phone', '+42 ??????'));
                                    ?>

                                    <?php if ($contactEmail !== ''): ?>
                                        <a href="mailto:<?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($contactPhone !== ''): ?>
                                        <a href="tel:<?= htmlspecialchars(preg_replace('/[^\d+]/', '', $contactPhone), ENT_QUOTES, 'UTF-8'); ?>">
                                            <?= htmlspecialchars($contactPhone, ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>

                        <div class="col-xl-4 col-md-7">
                            <div class="contact-info__item">
                                <div class="contact-info__item__icon"><span class="icon-magnifying-glass"></span></div>
                                <h3 class="contact-info__item__title"><?= htmlspecialchars(__('contact.info'), ENT_QUOTES, 'UTF-8'); ?></h3>

                                <p class="contact-info__item__text">
                                    <?= nl2br(htmlspecialchars(racCmsValue('contact_info', 'company_info', "IČ: ??????\nDIČ: ?????"), ENT_QUOTES, 'UTF-8'), false); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>