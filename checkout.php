<?php

declare(strict_types=1);

$config = require __DIR__ . '/lib/bootstrap.php';
$products = require __DIR__ . '/lib/products.php';
$appName = $config['app']['name'] ?? 'NovaStore';

$productId = trim((string) ($_GET['product'] ?? ''));
$product = $products[$productId] ?? null;

if ($product === null) {
    header('Location: /');
    exit;
}

$productJson = json_encode($product, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — <?= htmlspecialchars($product['name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?php $headerMode = 'checkout'; require __DIR__ . '/includes/header.php'; ?>

<div class="checkout-page">
    <aside class="panel checkout-left">
        <h2 class="section-label">Order summary</h2>
        <div class="order-product">
            <div class="order-emoji" style="background: <?= htmlspecialchars($product['gradient']) ?>"><?= $product['emoji'] ?></div>
            <div>
                <strong><?= htmlspecialchars($product['name']) ?></strong>
                <p class="tagline" style="margin:4px 0 0;color:var(--text-muted);font-size:13px"><?= htmlspecialchars($product['tagline']) ?></p>
            </div>
        </div>
        <p style="color:var(--text-muted);font-size:14px;line-height:1.5;margin:0 0 20px"><?= htmlspecialchars($product['description']) ?></p>
        <div class="order-total">
            <span>Total</span>
            <span style="color:var(--payu-primary)">₹<?= number_format($product['price']) ?></span>
        </div>

        <h2 class="section-label" style="margin-top:28px">Payment details</h2>
        <form id="customerForm" novalidate>
            <div class="form-group">
                <label for="firstname">First name <span class="required" aria-hidden="true">*</span></label>
                <input id="firstname" name="firstname" type="text" value="" autocomplete="given-name" minlength="2" maxlength="60" required aria-describedby="firstnameError">
                <p id="firstnameError" class="field-error" role="alert"></p>
            </div>
            <div class="form-group">
                <label for="email">Email <span class="required" aria-hidden="true">*</span></label>
                <input id="email" name="email" type="email" value="" autocomplete="email" maxlength="254" required aria-describedby="emailError">
                <p id="emailError" class="field-error" role="alert"></p>
            </div>
            <div class="form-group">
                <label for="phone">Phone <span class="required" aria-hidden="true">*</span></label>
                <input id="phone" name="phone" type="tel" value="" inputmode="numeric" autocomplete="tel" maxlength="10" pattern="[6-9][0-9]{9}" required aria-describedby="phoneError">
                <p id="phoneError" class="field-error" role="alert"></p>
            </div>
        </form>
    </aside>

    <main class="panel checkout-right">
        <div class="panel-head">
            <h2>Payment options</h2>
            <span class="checkout-badge">Secured by PayU</span>
        </div>

        <div class="payment-shell">
            <div class="pay-tabs" role="tablist" aria-label="Payment methods">
                <button type="button" class="pay-tab active" data-tab="card">
                    <span class="pay-tab-icon" aria-hidden="true">💳</span>
                    <span class="pay-tab-label">Card</span>
                </button>
                <button type="button" class="pay-tab" data-tab="upi_collect">
                    <span class="pay-tab-icon" aria-hidden="true">📱</span>
                    <span class="pay-tab-label">UPI Collect</span>
                </button>
                <button type="button" class="pay-tab" data-tab="upi_intent">
                    <span class="pay-tab-icon" aria-hidden="true">📲</span>
                    <span class="pay-tab-label">UPI Intent</span>
                </button>
                <button type="button" class="pay-tab" data-tab="netbanking">
                    <span class="pay-tab-icon" aria-hidden="true">🏦</span>
                    <span class="pay-tab-label">Net Banking</span>
                </button>
            </div>

            <div class="payment-content">
        <div id="panel-card" class="pay-panel active">
            <form id="cardForm">
                <div class="form-group">
                    <label for="ccnum">Card number</label>
                    <input id="ccnum" name="ccnum" placeholder="5123 4567 8901 2346" maxlength="19" autocomplete="cc-number" required>
                </div>
                <div id="binInfo" class="bin-info" style="display:none"></div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="ccexpmon">Expiry month</label>
                        <input id="ccexpmon" name="ccexpmon" placeholder="05" maxlength="2" required>
                    </div>
                    <div class="form-group">
                        <label for="ccexpyr">Expiry year</label>
                        <input id="ccexpyr" name="ccexpyr" placeholder="2026" maxlength="4" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="ccvv">CVV</label>
                        <input id="ccvv" name="ccvv" type="password" placeholder="123" maxlength="4" required>
                    </div>
                    <div class="form-group">
                        <label for="ccname">Name on card</label>
                        <input id="ccname" name="ccname" placeholder="John Doe" required>
                    </div>
                </div>
                <button type="button" class="btn btn-primary btn-locked" id="payCardBtn" aria-disabled="true">Pay <span id="payAmountCard"></span></button>
            </form>
        </div>

        <div id="panel-upi_collect" class="pay-panel">
            <div class="alert">Enter your UPI ID to receive a collect request on your phone.</div>
            <form id="upiForm">
                <div class="form-group">
                    <label for="vpa">UPI ID (VPA)</label>
                    <input id="vpa" name="vpa" placeholder="yourname@upi" required>
                </div>
                <div id="vpaStatus" class="vpa-status" style="display:none" role="status"></div>
                <button type="button" class="btn btn-primary btn-locked" id="payUpiBtn" aria-disabled="true">Pay <span class="pay-amount"></span></button>
            </form>
        </div>

        <div id="panel-upi_intent" class="pay-panel">
            <input type="hidden" id="upi_app" name="upi_app" value="generic">

            <!-- Desktop note (hidden on mobile by JS; visible by default for no-JS fallback) -->
            <div id="upiDesktopNote" class="alert">
                A QR code will appear on the next screen — scan it with any UPI app on your phone.
            </div>

            <!-- Mobile app grid (hidden on desktop by JS) -->
            <div id="upiMobileSection">
                <div class="form-group">
                    <label>Open payment in</label>
                    <div class="upi-app-grid" id="upiAppGrid"></div>
                </div>
            </div>

            <form id="intentForm">
                <button type="button" class="btn btn-primary btn-locked" id="payIntentBtn" aria-disabled="true">Pay <span class="pay-amount"></span></button>
            </form>
        </div>

        <div id="panel-netbanking" class="pay-panel">
            <p class="section-label">Select your bank</p>
            <div class="form-group bank-search">
                <input type="search" id="bankSearch" placeholder="Search bank…">
            </div>
            <div id="bankGrid" class="bank-grid"></div>
            <input type="hidden" id="bankcode" name="bankcode" value="">
            <form id="nbForm">
                <button type="button" class="btn btn-primary btn-locked" id="nbPayBtn" aria-disabled="true">Pay <span class="pay-amount"></span></button>
            </form>
        </div>
            </div>
        </div>
    </main>
</div>

    <script>
    // ── UPI Simulator redirect interception ──────────────────────────────────
    // The UPI Simulator (and some UPI apps) redirect the browser back to the
    // merchant page after payment — often the store homepage or checkout.php —
    // regardless of the `url` param in the intent.  We store the pending txnid
    // in localStorage before dispatching the intent; if we land here while a
    // payment is in-flight, redirect straight to payment-confirming.php.
    (function() {
        try {
            var raw = localStorage.getItem('payu_pending_upi');
            if (!raw) return;
            var d = JSON.parse(raw);
            if (!d || !d.txnid || (Date.now() - (d.ts || 0)) > 600000) {
                localStorage.removeItem('payu_pending_upi');
                return;
            }
            // Clear first so a back-navigation doesn't loop endlessly
            localStorage.removeItem('payu_pending_upi');
            window.location.replace('/payment-confirming.php?txnid=' + encodeURIComponent(d.txnid));
        } catch(e) {}
    })();
    // ────────────────────────────────────────────────────────────────────────

    const product = <?= $productJson ?>;
    const amountLabel = '₹' + product.price.toLocaleString('en-IN');
    document.querySelectorAll('.pay-amount, #payAmountCard').forEach(el => { el.textContent = amountLabel; });

    let activeTab = 'card';
    let binData = null;
    let binTimer = null;
    let paying = false;

    function toast(msg, type = 'error') {
        const el = document.createElement('div');
        el.className = 'toast ' + type;
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 4000);
    }

    const customerRules = {
        firstname(value) {
            const v = value.trim();
            if (!v) return 'First name is required';
            if (v.length < 2) return 'First name must be at least 2 characters';
            if (v.length > 60) return 'First name must not exceed 60 characters';
            if (!/^[A-Za-z][A-Za-z .'-]*$/.test(v)) return 'First name may only contain letters, spaces, dots and hyphens';
            return '';
        },
        email(value) {
            const v = value.trim();
            if (!v) return 'Email is required';
            if (v.length > 254) return 'Email must not exceed 254 characters';
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) return 'Enter a valid email address';
            return '';
        },
        phone(value) {
            const v = value.replace(/\s/g, '');
            if (!v) return 'Phone number is required';
            if (!/^[6-9]\d{9}$/.test(v)) return 'Enter a valid 10-digit Indian mobile number (starts with 6–9)';
            return '';
        }
    };

    function setFieldError(fieldId, message) {
        const input = document.getElementById(fieldId);
        const error = document.getElementById(fieldId + 'Error');
        const invalid = message !== '';
        input.classList.toggle('input-invalid', invalid);
        input.setAttribute('aria-invalid', invalid ? 'true' : 'false');
        error.textContent = message;
        error.classList.toggle('visible', invalid);
        return !invalid;
    }

    function isCustomerValid() {
        const firstname = document.getElementById('firstname').value;
        const email = document.getElementById('email').value;
        const phone = document.getElementById('phone').value;
        return !customerRules.firstname(firstname)
            && !customerRules.email(email)
            && !customerRules.phone(phone);
    }

    const cardRules = {
        ccnum(value) {
            const digits = value.replace(/\s/g, '');
            if (!digits) return 'Card number is required';
            if (digits.length < 13 || digits.length > 19) return 'Enter a valid card number';
            return '';
        },
        ccexpmon(value) {
            const v = value.replace(/\D/g, '');
            if (!v) return 'Expiry month is required';
            const month = parseInt(v, 10);
            if (month < 1 || month > 12) return 'Enter a valid month (01–12)';
            return '';
        },
        ccexpyr(value) {
            const v = value.replace(/\D/g, '');
            if (v.length !== 4) return 'Enter a 4-digit expiry year';
            return '';
        },
        ccvv(value) {
            const v = value.replace(/\D/g, '');
            if (!/^\d{3,4}$/.test(v)) return 'Enter a valid CVV';
            return '';
        },
        ccname(value) {
            const v = value.trim();
            if (!v) return 'Name on card is required';
            if (v.length < 2) return 'Name on card must be at least 2 characters';
            return '';
        }
    };

    function isCardExpiryValid() {
        const month = parseInt(document.getElementById('ccexpmon').value.replace(/\D/g, ''), 10);
        const year = parseInt(document.getElementById('ccexpyr').value.replace(/\D/g, ''), 10);
        if (!month || month < 1 || month > 12 || String(year).length !== 4) return false;
        const now = new Date();
        const expiry = new Date(year, month, 0);
        return expiry >= new Date(now.getFullYear(), now.getMonth(), 1);
    }

    function isCardReady() {
        if (!isCustomerValid()) return false;
        const fields = ['ccnum', 'ccexpmon', 'ccexpyr', 'ccvv', 'ccname'];
        for (const fieldId of fields) {
            const value = document.getElementById(fieldId).value;
            if (cardRules[fieldId](value)) return false;
        }
        return isCardExpiryValid();
    }

    function isUpiCollectReady() {
        if (!isCustomerValid()) return false;
        const vpa = document.getElementById('vpa').value.trim();
        return isVpaAlreadyVerified(vpa);
    }

    function isUpiIntentReady() {
        return isCustomerValid();
    }

    function isNetBankingReady() {
        return isCustomerValid() && !!document.getElementById('bankcode').value;
    }

    function setPayButtonLocked(btn, locked) {
        btn.classList.toggle('btn-locked', locked);
        btn.setAttribute('aria-disabled', locked ? 'true' : 'false');
    }

    function updatePayButtons() {
        const locked = paying;
        setPayButtonLocked(document.getElementById('payCardBtn'), locked || !isCardReady());
        setPayButtonLocked(document.getElementById('payUpiBtn'), locked || !isUpiCollectReady());
        setPayButtonLocked(document.getElementById('payIntentBtn'), locked || !isUpiIntentReady());
        setPayButtonLocked(document.getElementById('nbPayBtn'), locked || !isNetBankingReady());
    }

    function resetCardForm() {
        document.getElementById('cardForm').reset();
        binData = null;
        clearTimeout(binTimer);
        const box = document.getElementById('binInfo');
        box.style.display = 'none';
        box.innerHTML = '';
        box.className = 'bin-info';
    }

    function resetUpiCollectForm() {
        document.getElementById('upiForm').reset();
        clearTimeout(vpaTimer);
        vpaState = { valid: false, checking: false, vpa: '', verifiedVpa: '' };
        const status = document.getElementById('vpaStatus');
        status.style.display = 'none';
        status.textContent = '';
        status.className = 'vpa-status';
    }

    function resetUpiIntentForm() {
        document.getElementById('upi_app').value = 'generic';
        renderUpiApps();
    }

    function resetNetBankingForm() {
        selectedBank = '';
        document.getElementById('bankcode').value = '';
        document.getElementById('bankSearch').value = '';
        renderBanks();
    }

    function resetAllPaymodeForms() {
        resetCardForm();
        resetUpiCollectForm();
        resetUpiIntentForm();
        resetNetBankingForm();
    }

    function validateCustomerField(fieldId) {
        const input = document.getElementById(fieldId);
        let value = input.value;
        if (fieldId === 'phone') {
            value = value.replace(/\D/g, '');
            input.value = value;
        }
        const ok = setFieldError(fieldId, customerRules[fieldId](value));
        updatePayButtons();
        return ok;
    }

    function validateCustomer() {
        const fields = ['firstname', 'email', 'phone'];
        let valid = true;
        let firstInvalid = null;

        fields.forEach((fieldId) => {
            if (!validateCustomerField(fieldId)) {
                valid = false;
                if (!firstInvalid) firstInvalid = fieldId;
            }
        });

        if (!valid) {
            document.getElementById('customerForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (firstInvalid) document.getElementById(firstInvalid).focus();
            toast('Please fix the highlighted customer details');
            return null;
        }

        return {
            firstname: document.getElementById('firstname').value.trim(),
            email: document.getElementById('email').value.trim(),
            phone: document.getElementById('phone').value.replace(/\D/g, '')
        };
    }

    ['firstname', 'email', 'phone'].forEach((fieldId) => {
        const input = document.getElementById(fieldId);
        input.addEventListener('blur', () => validateCustomerField(fieldId));
        input.addEventListener('input', () => {
            if (input.classList.contains('input-invalid')) {
                validateCustomerField(fieldId);
            } else {
                updatePayButtons();
            }
        });
    });

    document.getElementById('phone').addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\D/g, '').slice(0, 10);
        updatePayButtons();
    });

    ['ccnum', 'ccexpmon', 'ccexpyr', 'ccvv', 'ccname'].forEach((fieldId) => {
        const input = document.getElementById(fieldId);
        input.addEventListener('input', () => {
            if (fieldId === 'ccexpmon') {
                input.value = input.value.replace(/\D/g, '').slice(0, 2);
            } else if (fieldId === 'ccexpyr') {
                input.value = input.value.replace(/\D/g, '').slice(0, 4);
            } else if (fieldId === 'ccvv') {
                input.value = input.value.replace(/\D/g, '').slice(0, 4);
            }
            updatePayButtons();
        });
    });

    document.querySelectorAll('.pay-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const nextTab = tab.dataset.tab;
            if (nextTab === activeTab) return;
            resetAllPaymodeForms();
            activeTab = nextTab;
            document.querySelectorAll('.pay-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === activeTab));
            document.querySelectorAll('.pay-panel').forEach(p => p.classList.toggle('active', p.id === 'panel-' + activeTab));
            updatePayButtons();
        });
    });

    document.getElementById('ccnum').addEventListener('input', (e) => {
        let v = e.target.value.replace(/\D/g, '');
        e.target.value = v.replace(/(\d{4})(?=\d)/g, '$1 ').trim();
        const digits = v;
        clearTimeout(binTimer);
        const box = document.getElementById('binInfo');
        if (digits.length < 6) {
            binData = null;
            box.style.display = 'none';
            updatePayButtons();
            return;
        }
        box.style.display = 'flex';
        box.className = 'bin-info loading';
        box.innerHTML = '<span class="spinner"></span> Looking up card…';
        binTimer = setTimeout(() => lookupBin(digits), 400);
        updatePayButtons();
    });

    async function lookupBin(cardNumber) {
        try {
            const res = await fetch('/api/bin-info.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ card_number: cardNumber })
            });
            const data = await res.json();
            const box = document.getElementById('binInfo');
            if (!data.success || !data.bin) {
                binData = null;
                box.className = 'bin-info';
                box.innerHTML = '⚠️ Could not detect card type — defaulting to credit card';
                return;
            }
            binData = data.bin;
            box.className = 'bin-info';
            box.innerHTML = `
                <span class="scheme-icon">${binData.scheme_label.slice(0,4).toUpperCase()}</span>
                <div>
                    <strong>${binData.card_type_label}</strong> · ${binData.scheme_label}<br>
                    <span style="color:var(--text-muted);font-size:12px">${binData.issuing_bank}</span>
                </div>`;
        } catch (e) {
            binData = null;
        } finally {
            updatePayButtons();
        }
    }

    let vpaState = { valid: false, checking: false, vpa: '', verifiedVpa: '' };
    let vpaTimer = null;

    function isVpaAlreadyVerified(vpa) {
        return !!vpa && vpa === vpaState.verifiedVpa && vpaState.valid && !vpaState.checking;
    }

    async function verifyVpa(vpa) {
        if (isVpaAlreadyVerified(vpa)) {
            return;
        }

        const status = document.getElementById('vpaStatus');
        vpaState.vpa = vpa;

        try {
            if (!vpa) {
                vpaState.valid = false;
                vpaState.verifiedVpa = '';
                vpaState.checking = false;
                status.style.display = 'none';
                return;
            }

            if (!/^[^\s@]+@[^\s@]+$/.test(vpa)) {
                vpaState.valid = false;
                vpaState.verifiedVpa = '';
                vpaState.checking = false;
                status.style.display = 'block';
                status.className = 'vpa-status pending';
                status.textContent = 'Enter a valid UPI ID (name@bank)';
                return;
            }

            vpaState.checking = true;
            vpaState.valid = false;
            vpaState.verifiedVpa = '';
            status.style.display = 'block';
            status.className = 'vpa-status checking';
            status.innerHTML = '<span class="spinner"></span> Verifying UPI ID…';
            updatePayButtons();

            const res = await fetch('/api/validate-vpa.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ vpa })
            });
            const data = await res.json();
            if (vpaState.vpa !== vpa) return;

            vpaState.valid = !!data.success;
            vpaState.checking = false;
            vpaState.verifiedVpa = data.success ? vpa : '';
            status.className = 'vpa-status ' + (data.success ? 'valid' : 'invalid');
            status.textContent = data.message || (data.success ? 'UPI ID verified' : 'Invalid UPI ID');
        } catch (e) {
            if (vpaState.vpa !== vpa) return;
            vpaState.valid = false;
            vpaState.verifiedVpa = '';
            vpaState.checking = false;
            status.className = 'vpa-status invalid';
            status.textContent = 'Could not verify UPI ID';
        } finally {
            updatePayButtons();
        }
    }

    document.getElementById('vpa').addEventListener('input', (e) => {
        const vpa = e.target.value.trim();
        if (vpa !== vpaState.verifiedVpa) {
            vpaState.valid = false;
            vpaState.verifiedVpa = '';
        }
        clearTimeout(vpaTimer);
        updatePayButtons();
        vpaTimer = setTimeout(() => verifyVpa(vpa), 500);
    });

    document.getElementById('vpa').addEventListener('blur', (e) => {
        const vpa = e.target.value.trim();
        clearTimeout(vpaTimer);
        if (!isVpaAlreadyVerified(vpa)) {
            verifyVpa(vpa);
        }
    });

    let banks = [];
    let selectedBank = '';

    function bankIconFallback(img) {
        const wrap = img.parentElement;
        if (!wrap) return;
        wrap.classList.add('bank-logo--fallback');
        wrap.textContent = wrap.dataset.fallback || '?';
    }

    function renderBanks(filter = '') {
        const grid = document.getElementById('bankGrid');
        const q = filter.toLowerCase();
        grid.innerHTML = '';
        banks.filter(b => !q || b.name.toLowerCase().includes(q) || b.code.toLowerCase().includes(q))
            .forEach(bank => {
                const card = document.createElement('button');
                card.type = 'button';
                card.className = 'bank-card' + (bank.code === selectedBank ? ' selected' : '') + (bank.active ? '' : ' disabled');
                const initials = bank.name.split(' ').filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase();
                const logo = bank.icon_url
                    ? `<span class="bank-logo" data-fallback="${initials.slice(0, 3)}"><img src="${bank.icon_url}" alt="" loading="lazy" onerror="bankIconFallback(this)"></span>`
                    : `<span class="bank-logo bank-logo--fallback">${initials.slice(0, 3)}</span>`;
                card.innerHTML = `${logo}<span class="bank-name">${bank.name}</span>`;
                if (bank.active) {
                    card.addEventListener('click', () => {
                        selectedBank = bank.code;
                        document.getElementById('bankcode').value = bank.code;
                        renderBanks(document.getElementById('bankSearch').value);
                        updatePayButtons();
                    });
                }
                grid.appendChild(card);
            });
    }

    async function loadBanks() {
        try {
            const res = await fetch('/api/netbanking-banks.php');
            const data = await res.json();
            banks = data.banks || [];
            renderBanks();
        } catch (e) {
            banks = [{ code: 'TESTPGNB', name: 'Test Net Banking', active: true }];
            renderBanks();
        }
        updatePayButtons();
    }

    document.getElementById('bankSearch').addEventListener('input', (e) => renderBanks(e.target.value));

    // ── UPI Intent app selection ──────────────────────────────────────────────
    const isMobileDevice = /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent);

    const upiApps = [
        { app: 'generic',  label: 'Any UPI App', initials: 'UPI', color: '#10846D', icon: null },
        { app: 'gpay',     label: 'Google Pay',  initials: 'G',   color: '#4285F4', icon: '/assets/img/upi/gpay-brand.png' },
        { app: 'phonepe',  label: 'PhonePe',     initials: 'Ph',  color: '#5F259F', icon: '/assets/img/upi/phonepe-brand.png' },
        { app: 'paytm',    label: 'Paytm',       initials: 'Pt',  color: '#00BAF2', icon: '/assets/img/upi/paytm-brand.png' },
        { app: 'bhim',     label: 'BHIM',        initials: 'B',   color: '#1A4DA2', icon: '/assets/img/upi/bhim-brand.png' },
    ];

    function upiLogoHtml({ app, initials, color, icon }) {
        if (!icon) {
            // Generic "any UPI" — coloured initials badge
            return `<span class="bank-logo bank-logo--fallback" style="background:${color};font-size:11px">${initials}</span>`;
        }
        // Real brand icon with white rounded container; onerror → coloured initials
        return `<span class="bank-logo bank-logo--brand">` +
               `<img src="${icon}" alt="${app}" loading="lazy" ` +
               `style="width:40px;height:40px;object-fit:contain;border-radius:10px;display:block" ` +
               `onerror="var p=this.parentElement;p.className='bank-logo bank-logo--fallback';p.style.background='${color}';p.textContent='${initials}'">` +
               `</span>`;
    }

    function renderUpiApps() {
        const desktopNote   = document.getElementById('upiDesktopNote');
        const mobileSection = document.getElementById('upiMobileSection');
        const grid          = document.getElementById('upiAppGrid');

        if (!isMobileDevice) {
            // Desktop: hide icons, show QR hint, always use generic deeplink.
            if (desktopNote)   desktopNote.style.display   = 'block';
            if (mobileSection) mobileSection.style.display = 'none';
            document.getElementById('upi_app').value = 'generic';
            return;
        }

        // Mobile: show app icon grid, hide desktop note.
        if (desktopNote)   desktopNote.style.display   = 'none';
        if (mobileSection) mobileSection.style.display = 'block';

        if (!grid) return;
        const selectedApp = document.getElementById('upi_app').value;
        grid.innerHTML = '';
        upiApps.forEach((appDef) => {
            const card = document.createElement('button');
            card.type = 'button';
            card.className = 'bank-card' + (appDef.app === selectedApp ? ' selected' : '');
            card.innerHTML = upiLogoHtml(appDef) + `<span class="bank-name">${appDef.label}</span>`;
            card.addEventListener('click', () => {
                document.getElementById('upi_app').value = appDef.app;
                renderUpiApps();
                updatePayButtons();
            });
            grid.appendChild(card);
        });
    }

    async function pay(paymode, extra = {}) {
        if (paying) return;
        const customer = validateCustomer();
        if (!customer) return;

        paying = true;
        updatePayButtons();
        const btn = document.getElementById({
            card: 'payCardBtn',
            upi_collect: 'payUpiBtn',
            upi_intent: 'payIntentBtn',
            netbanking: 'nbPayBtn',
        }[paymode]);
        const orig = btn.innerHTML;
        btn.innerHTML = '<span class="spinner"></span> Processing…';

        const payload = {
            paymode,
            amount: product.price,
            productinfo: product.name,
            ...customer,
            ...extra
        };

        try {
            const res = await fetch('/api/process-payment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (!data.success) {
                if (data.errors) {
                    Object.entries(data.errors).forEach(([field, message]) => {
                        if (document.getElementById(field)) {
                            setFieldError(field, message);
                        }
                    });
                    document.getElementById('customerForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
                    updatePayButtons();
                }
                toast(data.message || 'Payment failed');
                return;
            }

            if (data.redirect_url) {
                window.location.href = data.redirect_url;
                return;
            }

            toast('Payment initiated but no redirect received', 'error');
        } catch (e) {
            toast('Network error — is the server running?');
        } finally {
            paying = false;
            btn.innerHTML = orig;
            updatePayButtons();
        }
    }

    function handlePayClick(readyCheck, paymode, extra = {}) {
        updatePayButtons();
        if (paying || !readyCheck()) return;
        pay(paymode, extra);
    }

    document.getElementById('payCardBtn').addEventListener('click', () => {
        handlePayClick(isCardReady, 'card', {
            ccnum: document.getElementById('ccnum').value.replace(/\s/g, ''),
            ccexpmon: document.getElementById('ccexpmon').value,
            ccexpyr: document.getElementById('ccexpyr').value,
            ccvv: document.getElementById('ccvv').value,
            ccname: document.getElementById('ccname').value,
            card_category: binData?.card_category || 'creditcard',
        });
    });

    document.getElementById('payUpiBtn').addEventListener('click', () => {
        const vpa = document.getElementById('vpa').value.trim();
        handlePayClick(isUpiCollectReady, 'upi_collect', { vpa });
    });

    document.getElementById('payIntentBtn').addEventListener('click', () => {
        handlePayClick(isUpiIntentReady, 'upi_intent', {
            upi_app: document.getElementById('upi_app').value,
        });
    });

    document.getElementById('nbPayBtn').addEventListener('click', () => {
        handlePayClick(isNetBankingReady, 'netbanking', {
            bankcode: document.getElementById('bankcode').value,
        });
    });

    ['payCardBtn', 'payUpiBtn', 'payIntentBtn', 'nbPayBtn'].forEach((id) => {
        document.getElementById(id).addEventListener('mousedown', updatePayButtons);
    });

    loadBanks();
    renderUpiApps();
    updatePayButtons();
    </script>
</body>
</html>
