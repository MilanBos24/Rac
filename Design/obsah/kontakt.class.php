 <div class="stricky-header stricked-menu main-menu">
            <div class="sticky-header__content"></div><!-- /.sticky-header__content -->
        </div><!-- /.stricky-header -->
        <section class="page-header">
            <div class="page-header__bg"></div>
            <!-- /.page-header__bg -->
            <div class="page-header__overlay"></div>
            <!-- /.page-header__bg -->
            <div class="container">
                <ul class="page-header__breadcrumb list-unstyled">
                    <li><a href="<?= htmlspecialchars(racUrl('index.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(__('breadcrumb.home'), ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <li><span><?= htmlspecialchars(__('breadcrumb.contact'), ENT_QUOTES, 'UTF-8'); ?></span></li>
                </ul><!-- /.page-breadcrumb list-unstyled -->
                <h2 class="page-header__title"><?= htmlspecialchars(__('page.contact_information'), ENT_QUOTES, 'UTF-8'); ?></h2><!-- /.page-title -->
            </div><!-- /.container -->
        </section><!-- /.page-header -->
        <!--Main Slider End-->

         <section class="contact-two">
            <div class="container wow fadeInUp animated" data-wow-delay="300ms">
                <div class="section-title text-center">
                    <h5 class="section-title__tagline section-title__tagline--has-dots"><?= htmlspecialchars(__('contact.form_label'), ENT_QUOTES, 'UTF-8'); ?></h5>
                    <h2 class="section-title__title"><?= htmlspecialchars(__('contact.form_title'), ENT_QUOTES, 'UTF-8'); ?></h2>
                </div><!-- section-title -->
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
                                        <button type="submit" class="ogency-btn"><?= htmlspecialchars(__('contact.send'), ENT_QUOTES, 'UTF-8'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <div class="result"></div>
                    </div>
                </div>
            </div>
        </section>

       <!--Contact Info Start-->
        <section class="contact-info">
            <div class="container">
                <div class="contact-info__wrapper">
                    <div class="row">
                        <div class="col-xl-4 col-md-6">
                            <div class="contact-info__item">
                                <div class="contact-info__item__icon"><span class="icon-place"></span></div>
                                <h3 class="contact-info__item__title"><?= htmlspecialchars(__('contact.address'), ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p class="contact-info__item__text">
                                    Ulice číslo popisné<br> Karvniná, ČR
                                </p>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="contact-info__item">
                                <div class="contact-info__item__icon"><span class="icon-phone"></span></div>
                                <h3 class="contact-info__item__title"><?= htmlspecialchars(__('contact.contact'), ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p class="contact-info__item__text">
                                    <a href="mailto:stefan.rac1@gmail.com">stefan.rac1@gmail.com</a>
                                    <a href="tel:+92880048720">+42 ??????</a>
                                </p>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-7">
                            <div class="contact-info__item">
                                <div class="contact-info__item__icon"><span class="icon-magnifying-glass"></span></div>
                                <h3 class="contact-info__item__title"><?= htmlspecialchars(__('contact.info'), ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p class="contact-info__item__text">
                                    IČ: ??????<br> DIČ: ?????
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
