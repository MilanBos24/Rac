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
                        <form data-required-message="<?= htmlspecialchars(__('contact.validation.required'), ENT_QUOTES, 'UTF-8'); ?>" data-failure-message="<?= htmlspecialchars(__('contact.status.failure'), ENT_QUOTES, 'UTF-8'); ?>" action="assets/inc/sendemail.php" method="post" class="contact-one__form contact-form-validated" novalidate="novalidate">
                            <?php if (empty($_SESSION['rac_contact_csrf'])) { $_SESSION['rac_contact_csrf'] = bin2hex(random_bytes(32)); } ?>
                            <input type="hidden" name="lang" value="<?= htmlspecialchars((string)$currentLanguage, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['rac_contact_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="rac-contact-hp" style="position:absolute;left:-9999px" aria-hidden="true"><label>Web <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="contact-one__input-box">
                                        <input type="text" placeholder="<?= htmlspecialchars(__('contact.name'), ENT_QUOTES, 'UTF-8'); ?>" name="name" required maxlength="120">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="contact-one__input-box">
                                        <input type="email" placeholder="<?= htmlspecialchars(__('contact.email'), ENT_QUOTES, 'UTF-8'); ?>" name="email" required maxlength="254">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="contact-one__input-box">
                                        <input type="text" placeholder="<?= htmlspecialchars(__('contact.phone'), ENT_QUOTES, 'UTF-8'); ?>" name="phone" maxlength="40">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="contact-one__input-box">
                                        <?php
                                        $racContactSubjects = array();
                                        try {
                                            $racContactDb = racFrontendPdo();
                                            if ($racContactDb instanceof PDO) {
                                                $racContactSubjects = $racContactDb->query("SELECT id, label_cs, label_sk, label_pl, label_en, label_de, label_fr FROM contact_form_subjects WHERE is_active=1 ORDER BY sort_order, id")->fetchAll(PDO::FETCH_ASSOC);
                                            }
                                        } catch (Throwable $ex) { error_log('RAC subjects: ' . $ex->getMessage()); }
                                        $racContactLang = in_array((string)$currentLanguage, array('cs','sk','pl','en','de','fr'), true) ? (string)$currentLanguage : 'cs';
                                        ?>
                                        <select class="selectpicker" name="subject" aria-label="<?= htmlspecialchars(__('contact.subject.select'), ENT_QUOTES, 'UTF-8'); ?>">
                                            <option value="creation" selected><?= htmlspecialchars(__('contact.subject.creation'), ENT_QUOTES, 'UTF-8'); ?></option>
                                            <option value="other"><?= htmlspecialchars(__('contact.subject.other'), ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php foreach ($racContactSubjects as $racSubject): ?>
                                                <?php $racSubjectLabel = trim((string)($racSubject['label_' . $racContactLang] ?? '')); if ($racSubjectLabel === '') { $racSubjectLabel = (string)$racSubject['label_cs']; } ?>
                                                <option value="custom:<?= (int)$racSubject['id'] ?>"><?= htmlspecialchars($racSubjectLabel, ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="contact-one__input-box text-message-box">
                                        <textarea name="message" required maxlength="10000" placeholder="<?= htmlspecialchars(__('contact.message'), ENT_QUOTES, 'UTF-8'); ?>"></textarea>
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
