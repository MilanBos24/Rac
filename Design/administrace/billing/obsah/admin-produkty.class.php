<?php
/**
 * RAC Billing - vzhled administrace produktu
 * verze 2026-09-16-20.45
 */

function bp_type_label(string $type): string
{
    return $type === 'subscription' ? 'Předplatné' : 'Jednorázová platba';
}

function bp_interval_label(array $product): string
{
    if ((string)$product['payment_type'] !== 'subscription') {
        return '—';
    }

    $count = max(1, (int)$product['billing_interval_count']);
    $interval = (string)$product['billing_interval'];

    if ($interval === 'month') {
        return $count === 1 ? 'měsíčně' : 'každých ' . $count . ' měsíců';
    }

    if ($interval === 'year') {
        return $count === 1 ? 'ročně' : 'každé ' . $count . ' roky';
    }

    if ($interval === 'week') {
        return $count === 1 ? 'týdně' : 'každých ' . $count . ' týdnů';
    }

    if ($interval === 'day') {
        return $count === 1 ? 'denně' : 'každých ' . $count . ' dní';
    }

    return '—';
}
?>
<?php $pageTitle = 'Billing – RAC'; include __DIR__ . '/../../inc/header.class.php'; ?>
<?php include __DIR__ . '/../../inc/leve-meny.class.php'; ?>
<main class="admin-content">
<div class="billing-content">
    <header class="page-header">
        <h2>Platby – produkty</h2>
        <div class="right-wrapper text-end">
            <ol class="breadcrumbs">
                <li><a href="/administrace/billing/admin.php"><i class="bx bx-home-alt"></i></a></li>
                <li><span>Platby</span></li>
                <li><span>Produkty</span></li>
            </ol>
            <a class="sidebar-right-toggle" data-open="sidebar-right"><i class="fas fa-chevron-left"></i></a>
        </div>
    </header>

    <div class="row align-items-center pt-2 mb-3">
        <div class="col-12 d-flex flex-wrap">
            <a href="/administrace/billing/admin.php" class="btn btn-default me-2 mb-2">
                <i class="fas fa-cog me-1"></i> Nastavení
            </a>
            <a href="/administrace/billing/admin-produkty.php" class="btn btn-primary me-2 mb-2">
                <i class="fas fa-box me-1"></i> Produkty
            </a>
        </div>
    </div>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?= bp_e($success) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= bp_e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="tabs">
        <ul class="nav nav-tabs tabs-primary">
            <li class="nav-item">
                <button class="nav-link active"
                        data-bs-target="#tab-prehled"
                        data-bs-toggle="tab"
                        type="button">
                    Přehled produktů
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link"
                        data-bs-target="#tab-formular"
                        data-bs-toggle="tab"
                        type="button">
                    <?= $editId > 0 ? 'Upravit produkt' : 'Nový produkt' ?>
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <div id="tab-prehled" class="tab-pane fade show active">
                <section class="card mb-0">
                    <header class="card-header">
                        <h2 class="card-title">Produkty a tarify</h2>
                    </header>

                    <div class="card-body">
                        <?php if (!$products): ?>
                            <p class="text-muted mb-0">Zatím nejsou vloženy žádné produkty.</p>
                        <?php else: ?>

                            <div class="dashboard-responsive-section">
                                <section class="card mb-3 dashboard-desktop-header">
                                    <div class="card-body">
                                        <div class="row form-group pb-0">
                                            <div class="col-xl-2"><label>Název / kód</label></div>
                                            <div class="col-xl-2"><label>Typ</label></div>
                                            <div class="col-xl-2"><label>Cena</label></div>
                                            <div class="col-xl-2"><label>Interval</label></div>
                                            <div class="col-xl-2"><label>Stripe Price ID</label></div>
                                            <div class="col-xl-1"><label>Stav</label></div>
                                            <div class="col-xl-1"></div>
                                        </div>
                                    </div>
                                </section>

                                <div class="dashboard-grid">
                                    <?php foreach ($products as $product): ?>
                                        <section class="card mb-1 border-0 shadow-none dashboard-item-card">
                                            <div class="card-body py-1">
                                                <div class="row form-group pb-0 align-items-center">
                                                    <div class="col-xl-2 col-lg-12 mb-2">
                                                        <label class="dashboard-mobile-label">Název / kód</label>
                                                        <input class="form-control"
                                                               readonly
                                                               value="<?= bp_e($product['name']) ?>">
                                                        <small class="text-muted"><?= bp_e($product['code']) ?></small>
                                                    </div>

                                                    <div class="col-xl-2 col-lg-12 mb-2">
                                                        <label class="dashboard-mobile-label">Typ</label>
                                                        <input class="form-control"
                                                               readonly
                                                               value="<?= bp_e(bp_type_label((string)$product['payment_type'])) ?>">
                                                    </div>

                                                    <div class="col-xl-2 col-lg-12 mb-2">
                                                        <label class="dashboard-mobile-label">Cena</label>
                                                        <input class="form-control"
                                                               readonly
                                                               value="<?= bp_e(bp_minor_to_amount((int)$product['amount_minor'], (string)$product['currency'])) ?> <?= bp_e(strtoupper((string)$product['currency'])) ?>">
                                                    </div>

                                                    <div class="col-xl-2 col-lg-12 mb-2">
                                                        <label class="dashboard-mobile-label">Interval</label>
                                                        <input class="form-control"
                                                               readonly
                                                               value="<?= bp_e(bp_interval_label($product)) ?>">
                                                    </div>

                                                    <div class="col-xl-2 col-lg-12 mb-2">
                                                        <label class="dashboard-mobile-label">Stripe Price ID</label>
                                                        <input class="form-control"
                                                               readonly
                                                               value="<?= bp_e($product['provider_price_id']) ?>">
                                                    </div>

                                                    <div class="col-xl-1 col-lg-12 mb-2">
                                                        <label class="dashboard-mobile-label">Stav</label>
                                                        <input class="form-control"
                                                               readonly
                                                               value="<?= (int)$product['active'] === 1 ? 'Aktivní' : 'Neaktivní' ?>">
                                                    </div>

                                                    <div class="col-xl-1 col-lg-12 mb-2">
                                                        <div class="d-flex flex-wrap">
                                                            <a class="btn btn-default btn-sm mb-1 mt-1 me-1"
                                                               href="admin-produkty.php?edit=<?= (int)$product['id'] ?>#tab-formular"
                                                               title="Upravit produkt"
                                                               aria-label="Upravit produkt">
                                                                <i class="fas fa-pencil-alt"></i>
                                                            </a>

                                                            <form method="post"
                                                                  action="admin-produkty.php"
                                                                  class="d-inline">
                                                                <input type="hidden"
                                                                       name="csrf"
                                                                       value="<?= bp_e($_SESSION['csrf_billing_products']) ?>">
                                                                <input type="hidden" name="action" value="toggle">
                                                                <input type="hidden"
                                                                       name="product_id"
                                                                       value="<?= (int)$product['id'] ?>">
                                                                <button class="btn btn-default btn-sm mb-1 mt-1"
                                                                        type="submit"
                                                                        title="<?= (int)$product['active'] === 1 ? 'Deaktivovat produkt' : 'Aktivovat produkt' ?>"
                                                                        aria-label="<?= (int)$product['active'] === 1 ? 'Deaktivovat produkt' : 'Aktivovat produkt' ?>">
                                                                    <i class="fas <?= (int)$product['active'] === 1 ? 'fa-toggle-on text-success' : 'fa-toggle-off text-muted' ?>"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </section>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <div id="tab-formular" class="tab-pane fade">
                <section class="card mb-0">
                    <header class="card-header">
                        <h2 class="card-title">
                            <?= $editId > 0 ? 'Upravit produkt' : 'Přidat produkt' ?>
                        </h2>
                    </header>

                    <form method="post" action="admin-produkty.php<?= $editId > 0 ? '?edit=' . (int)$editId : '' ?>" autocomplete="off">
                        <input type="hidden"
                               name="csrf"
                               value="<?= bp_e($_SESSION['csrf_billing_products']) ?>">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="product_id" value="<?= (int)$editId ?>">

                        <div class="card-body">
                            <div class="row form-group pb-3">
                                <div class="col-xl-3">
                                    <label class="form-label">Interní kód</label>
                                    <input class="form-control"
                                           type="text"
                                           name="code"
                                           value="<?= bp_e($form['code']) ?>"
                                           <?= $editId > 0 ? 'readonly' : 'required' ?>
                                           placeholder="např. bos24_standard_month">
                                    <?php if ($editId > 0): ?>
                                        <small class="text-muted">Kód se po vytvoření nemění kvůli historii plateb.</small>
                                    <?php endif; ?>
                                </div>

                                <div class="col-xl-5">
                                    <label class="form-label">Název produktu</label>
                                    <input class="form-control"
                                           type="text"
                                           name="name"
                                           value="<?= bp_e($form['name']) ?>"
                                           required
                                           placeholder="např. BOS24 Standard – měsíčně">
                                </div>

                                <div class="col-xl-2">
                                    <label class="form-label">Typ platby</label>
                                    <select class="form-control"
                                            name="payment_type"
                                            id="payment_type"
                                            onchange="billingProductTypeChanged()">
                                        <option value="one_off" <?= $form['payment_type'] === 'one_off' ? 'selected' : '' ?>>
                                            Jednorázová
                                        </option>
                                        <option value="subscription" <?= $form['payment_type'] === 'subscription' ? 'selected' : '' ?>>
                                            Předplatné
                                        </option>
                                    </select>
                                </div>

                                <div class="col-xl-2">
                                    <label class="form-label">Pořadí</label>
                                    <input class="form-control"
                                           type="number"
                                           name="sort_order"
                                           value="<?= bp_e($form['sort_order']) ?>">
                                </div>
                            </div>

                            <div class="row form-group pb-3">
                                <div class="col-xl-6">
                                    <label class="form-label">Popis</label>
                                    <textarea class="form-control"
                                              name="description"
                                              rows="3"
                                              placeholder="Krátký popis produktu"><?= bp_e($form['description']) ?></textarea>
                                </div>

                                <div class="col-xl-3">
                                    <label class="form-label">Stripe Product ID</label>
                                    <input class="form-control"
                                           type="text"
                                           name="provider_product_id"
                                           value="<?= bp_e($form['provider_product_id']) ?>"
                                           required
                                           placeholder="prod_...">
                                </div>

                                <div class="col-xl-3">
                                    <label class="form-label">Stripe Price ID</label>
                                    <input class="form-control"
                                           type="text"
                                           name="provider_price_id"
                                           value="<?= bp_e($form['provider_price_id']) ?>"
                                           required
                                           placeholder="price_...">
                                </div>
                            </div>

                            <div class="row form-group pb-3">
                                <div class="col-xl-3">
                                    <label class="form-label">Cena</label>
                                    <input class="form-control"
                                           type="text"
                                           inputmode="decimal"
                                           name="amount"
                                           value="<?= bp_e($form['amount']) ?>"
                                           required
                                           placeholder="20.00">
                                    <small class="text-muted">Zadává se běžná částka, ne haléře.</small>
                                </div>

                                <div class="col-xl-2">
                                    <label class="form-label">Měna</label>
                                    <input class="form-control"
                                           type="text"
                                           maxlength="3"
                                           name="currency"
                                           value="<?= bp_e($form['currency']) ?>"
                                           required
                                           placeholder="CZK">
                                </div>

                                <div class="col-xl-3 billing-subscription-only">
                                    <label class="form-label">Interval</label>
                                    <select class="form-control" name="billing_interval">
                                        <option value="month" <?= $form['billing_interval'] === 'month' ? 'selected' : '' ?>>Měsíc</option>
                                        <option value="year" <?= $form['billing_interval'] === 'year' ? 'selected' : '' ?>>Rok</option>
                                        <option value="week" <?= $form['billing_interval'] === 'week' ? 'selected' : '' ?>>Týden</option>
                                        <option value="day" <?= $form['billing_interval'] === 'day' ? 'selected' : '' ?>>Den</option>
                                    </select>
                                </div>

                                <div class="col-xl-2 billing-subscription-only">
                                    <label class="form-label">Počet intervalů</label>
                                    <input class="form-control"
                                           type="number"
                                           min="1"
                                           max="36"
                                           name="billing_interval_count"
                                           value="<?= bp_e($form['billing_interval_count']) ?>">
                                    <small class="text-muted">Např. 6 × měsíc.</small>
                                </div>

                                <div class="col-xl-2">
                                    <label class="form-label">Stav</label>
                                    <div class="checkbox-custom checkbox-default mt-2">
                                        <input type="checkbox"
                                               id="active"
                                               name="active"
                                               value="1"
                                               <?= $form['active'] === '1' ? 'checked' : '' ?>>
                                        <label for="active">Aktivní</label>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info mb-0">
                                Tato administrace pouze eviduje produkt, který už existuje ve Stripe.
                                Při změně ceny vytvořte ve Stripe nový <strong>Price</strong> a zde aktualizujte jeho
                                <strong>price_…</strong> ID. Staré platby tím zůstanou zachované.
                            </div>
                        </div>

                        <footer class="card-footer text-end">
                            <?php if ($editId > 0): ?>
                                <a href="admin-produkty.php#tab-prehled" class="btn btn-default me-1">
                                    Zrušit úpravu
                                </a>
                            <?php endif; ?>

                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-save me-1"></i>
                                <?= $editId > 0 ? 'Uložit změny' : 'Vytvořit produkt' ?>
                            </button>
                        </footer>
                    </form>
                </section>
            </div>
        </div>
    </div>
</div>
</main>
<?php include __DIR__ . '/../../inc/footer.class.php'; ?>
