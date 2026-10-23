<?php

declare(strict_types=1);

// ── Admin auth ───────────────────────────────────────────────────────────────
const ADMIN_USER = 'Sunit.Kumar';
const ADMIN_HASH = '$2b$12$4qtH7Ia71VPvVZuxD8gcmepuqXErqN7Y4o7.nkuTYYDUNGnOL7Xge';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$authError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__login'])) {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    if ($u === ADMIN_USER && password_verify($p, ADMIN_HASH)) {
        $_SESSION['config_admin_authed'] = true;
        header('Location: /config-admin.php');
        exit;
    }
    $authError = 'Invalid username or password.';
}

if (isset($_POST['__logout'])) {
    unset($_SESSION['config_admin_authed']);
    header('Location: /config-admin.php');
    exit;
}

if (empty($_SESSION['config_admin_authed'])) {
    ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Login — PayU Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="waiting-page">
    <div class="waiting-card" style="max-width:360px;padding:40px 32px">
        <div class="waiting-icon">🔐</div>
        <h1 style="font-size:20px;margin:0 0 20px">Admin Login</h1>
        <?php if ($authError): ?>
            <div style="padding:10px 14px;border-radius:8px;background:rgba(215,0,127,.08);border:1px solid rgba(215,0,127,.25);color:var(--danger);font-size:13px;margin-bottom:16px"><?= htmlspecialchars($authError) ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <input type="hidden" name="__login" value="1">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" autofocus autocomplete="off">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" autocomplete="current-password">
            </div>
            <button class="btn btn-primary" type="submit">Login</button>
        </form>
    </div>
</div>
</body>
</html><?php
    exit;
}
// ────────────────────────────────────────────────────────────────────────────

$config = require __DIR__ . '/lib/bootstrap.php';

$currentEnv    = $config['payu']['env'] ?? 'test';
$currentUrl    = $config['app']['base_url'] ?? '';

// Read both sets directly from env (already loaded by bootstrap)
$testKey  = getenv('PAYU_TEST_KEY')  ?: '';
$testSalt = getenv('PAYU_TEST_SALT') ?: '';
$prodKey  = getenv('PAYU_PROD_KEY')  ?: '';
$prodSalt = getenv('PAYU_PROD_SALT') ?: '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Config — PayU Store</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?php $headerMode = 'checkout'; require __DIR__ . '/includes/header.php'; ?>

<div class="waiting-page" style="min-height:calc(100vh - 64px);align-items:flex-start;padding-top:40px">
    <div style="width:100%;max-width:520px">

        <div class="panel" style="border-top:4px solid var(--payu-primary)">
            <h2 style="margin:0 0 4px;font-size:1.15rem">PayU Configuration</h2>
            <p style="margin:0 0 24px;font-size:13px;color:var(--text-muted)">Changes are saved to <code>.env</code> and take effect immediately.</p>

            <!-- Env toggle -->
            <div style="display:flex;gap:8px;margin-bottom:24px">
                <button type="button" id="btnTest"
                    style="flex:1;padding:12px;border-radius:10px;font-family:inherit;font-size:14px;font-weight:600;cursor:pointer;border:2px solid;transition:all .15s">
                    🧪 Test
                </button>
                <button type="button" id="btnProd"
                    style="flex:1;padding:12px;border-radius:10px;font-family:inherit;font-size:14px;font-weight:600;cursor:pointer;border:2px solid;transition:all .15s">
                    🚀 Production
                </button>
            </div>

            <div id="envBanner" style="padding:10px 14px;border-radius:8px;font-size:13px;font-weight:600;margin-bottom:20px;text-align:center"></div>

            <!-- Test credentials -->
            <div id="testCredsSection" style="margin-bottom:20px;padding:14px 16px;border-radius:10px;background:rgba(245,158,11,.06);border:1px solid rgba(245,158,11,.25)">
                <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--warning);margin-bottom:12px">🧪 Test Credentials</div>
                <div class="form-group" style="margin-bottom:12px">
                    <label>Test Key</label>
                    <input type="text" id="fTestKey" placeholder="Enter test key" value="<?= htmlspecialchars($testKey, ENT_QUOTES) ?>">
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label style="display:flex;justify-content:space-between;align-items:center">
                        Test Salt
                        <?php if ($testSalt !== ''): ?>
                            <span class="saltToggle" data-target="fTestSalt" style="font-size:11px;font-weight:600;color:var(--payu-primary);cursor:pointer;user-select:none">Show</span>
                        <?php endif; ?>
                    </label>
                    <input type="password" id="fTestSalt" placeholder="Salt..."
                           value="<?= htmlspecialchars($testSalt, ENT_QUOTES) ?>">
                    <?php if ($testSalt !== ''): ?><span style="font-size:11px;color:var(--text-muted);margin-top:4px;display:block">Configured. Type to replace.</span><?php endif; ?>
                </div>
            </div>

            <!-- Production credentials -->
            <div id="prodCredsSection" style="margin-bottom:20px;padding:14px 16px;border-radius:10px;background:rgba(215,0,127,.05);border:1px solid rgba(215,0,127,.2)">
                <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--danger);margin-bottom:12px">🚀 Production Credentials</div>
                <div class="form-group" style="margin-bottom:12px">
                    <label>Production Key</label>
                    <input type="text" id="fProdKey" placeholder="Enter production key" value="<?= htmlspecialchars($prodKey, ENT_QUOTES) ?>">
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label style="display:flex;justify-content:space-between;align-items:center">
                        Production Salt
                        <?php if ($prodSalt !== ''): ?>
                            <span class="saltToggle" data-target="fProdSalt" style="font-size:11px;font-weight:600;color:var(--payu-primary);cursor:pointer;user-select:none">Show</span>
                        <?php endif; ?>
                    </label>
                    <input type="password" id="fProdSalt" placeholder="Salt..."
                           value="<?= htmlspecialchars($prodSalt, ENT_QUOTES) ?>">
                    <?php if ($prodSalt !== ''): ?><span style="font-size:11px;color:var(--text-muted);margin-top:4px;display:block">Configured. Type to replace.</span><?php endif; ?>
                </div>
            </div>

            <div id="errMsg" style="display:none;padding:10px 14px;border-radius:8px;background:rgba(215,0,127,.08);border:1px solid rgba(215,0,127,.25);color:var(--danger);font-size:13px;margin-bottom:14px"></div>

            <button class="btn btn-primary" id="saveBtn" type="button" style="margin-top:4px">Save configuration</button>

            <a href="/" class="btn btn-ghost" style="margin-top:8px;display:block;text-align:center">← Back to store</a>

            <!-- Danger zone -->
            <div style="margin-top:24px;padding:14px 16px;border-radius:10px;border:1px solid rgba(215,0,127,.25);background:rgba(215,0,127,.04)">
                <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--danger);margin-bottom:8px">⚠️ Danger Zone</div>
                <p style="font-size:12px;color:var(--text-muted);margin:0 0 10px">Clears all keys, salts and resets env to test. Use before sharing or handing over device.</p>
                <button class="btn" id="clearBtn" type="button"
                    style="width:100%;background:transparent;border:1.5px solid var(--danger);color:var(--danger);font-size:13px">
                    🗑 Clear all credentials
                </button>
            </div>

            <!-- Confirm modal -->
            <div id="clearModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center">
                <div style="background:var(--surface);border-radius:var(--radius);padding:32px;max-width:340px;width:90%;text-align:center;box-shadow:var(--shadow)">
                    <div style="font-size:32px;margin-bottom:12px">⚠️</div>
                    <h3 style="margin:0 0 8px;font-size:1rem">Clear all credentials?</h3>
                    <p style="font-size:13px;color:var(--text-muted);margin:0 0 20px">This will wipe all keys and salts from <code>.env</code>. You will need to re-enter them to make payments.</p>
                    <div style="display:flex;gap:10px">
                        <button id="clearCancelBtn" class="btn btn-ghost" style="flex:1">Cancel</button>
                        <button id="clearConfirmBtn" class="btn" style="flex:1;background:var(--danger);color:#fff">Yes, clear</button>
                    </div>
                </div>
            </div>

            <form method="POST" style="margin-top:8px">
                <input type="hidden" name="__logout" value="1">
                <button type="submit" class="btn btn-ghost" style="width:100%;color:var(--text-muted);font-size:12px">Logout</button>
            </form>
        </div>

        <!-- Current resolved URLs (read-only info) -->
        <div style="margin-top:16px;padding:14px 16px;border-radius:10px;background:rgba(16,132,109,.06);border:1px solid rgba(16,132,109,.18);font-size:12px;color:var(--text-muted)">
            <strong style="color:var(--payu-primary);display:block;margin-bottom:6px">Active endpoints</strong>
            <div>Payment URL: <code><?= htmlspecialchars($config['payu']['payment_url'], ENT_QUOTES) ?></code></div>
            <div style="margin-top:4px">Postservice: <code><?= htmlspecialchars($config['payu']['postservice_url'], ENT_QUOTES) ?></code></div>
        </div>
    </div>
</div>

<script>
const CURRENT_ENV = <?= json_encode($currentEnv) ?>;
let selectedEnv = CURRENT_ENV;

const btnTest = document.getElementById('btnTest');
const btnProd = document.getElementById('btnProd');
const banner  = document.getElementById('envBanner');
const saveBtn = document.getElementById('saveBtn');
const errMsg  = document.getElementById('errMsg');

const testCredsSection = document.getElementById('testCredsSection');
const prodCredsSection = document.getElementById('prodCredsSection');

function setEnv(env) {
    selectedEnv = env;
    const isTest = env === 'test';

    btnTest.style.background = isTest ? 'rgba(16,132,109,.14)' : 'transparent';
    btnTest.style.borderColor = isTest ? 'var(--payu-primary)' : 'var(--border)';
    btnTest.style.color = isTest ? 'var(--payu-primary-dark)' : 'var(--text-muted)';

    btnProd.style.background = !isTest ? 'rgba(215,0,127,.1)' : 'transparent';
    btnProd.style.borderColor = !isTest ? 'var(--danger)' : 'var(--border)';
    btnProd.style.color = !isTest ? 'var(--danger)' : 'var(--text-muted)';

    if (isTest) {
        banner.textContent = '🧪 Test environment — payments go to test.payu.in';
        banner.style.background = 'rgba(245,158,11,.12)';
        banner.style.color = 'var(--warning)';
        banner.style.border = '1px solid rgba(245,158,11,.3)';
    } else {
        banner.textContent = '🚀 Production environment — real payments on secure.payu.in';
        banner.style.background = 'rgba(215,0,127,.08)';
        banner.style.color = 'var(--danger)';
        banner.style.border = '1px solid rgba(215,0,127,.25)';
    }

    // Show only the relevant credentials section
    if (testCredsSection) testCredsSection.style.display = isTest  ? 'block' : 'none';
    if (prodCredsSection) prodCredsSection.style.display = !isTest ? 'block' : 'none';
}

btnTest.addEventListener('click', () => setEnv('test'));
btnProd.addEventListener('click', () => setEnv('production'));
setEnv(CURRENT_ENV);

// Clear credentials modal
const clearModal       = document.getElementById('clearModal');
const clearBtn         = document.getElementById('clearBtn');
const clearCancelBtn   = document.getElementById('clearCancelBtn');
const clearConfirmBtn  = document.getElementById('clearConfirmBtn');

if (clearBtn) clearBtn.addEventListener('click', function() {
    clearModal.style.display = 'flex';
});
if (clearCancelBtn) clearCancelBtn.addEventListener('click', function() {
    clearModal.style.display = 'none';
});
if (clearConfirmBtn) clearConfirmBtn.addEventListener('click', async function() {
    clearConfirmBtn.disabled = true;
    clearConfirmBtn.textContent = 'Clearing…';
    try {
        const res  = await fetch('/api/clear-config.php', { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            clearModal.style.display = 'none';
            window.location.reload();
        } else {
            alert(data.message || 'Clear failed.');
            clearConfirmBtn.disabled = false;
            clearConfirmBtn.textContent = 'Yes, clear';
        }
    } catch(e) {
        alert('Network error.');
        clearConfirmBtn.disabled = false;
        clearConfirmBtn.textContent = 'Yes, clear';
    }
});
// Close modal on backdrop click
if (clearModal) clearModal.addEventListener('click', function(e) {
    if (e.target === clearModal) clearModal.style.display = 'none';
});

// Salt show/hide toggles (multiple)
document.querySelectorAll('.saltToggle').forEach(function(toggle) {
    toggle.addEventListener('click', function() {
        var input = document.getElementById(toggle.dataset.target);
        if (!input) return;
        var isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        toggle.textContent = isHidden ? 'Hide' : 'Show';
    });
});

saveBtn.addEventListener('click', async function() {
    errMsg.style.display = 'none';
    const testKey  = document.getElementById('fTestKey').value.trim();
    const testSalt = document.getElementById('fTestSalt').value.trim();
    const prodKey  = document.getElementById('fProdKey').value.trim();
    const prodSalt = document.getElementById('fProdSalt').value.trim();

    if (selectedEnv === 'test' && (!testKey || !testSalt)) {
        errMsg.textContent = 'Test key and salt are required when Test env is active.';
        errMsg.style.display = 'block';
        return;
    }
    if (selectedEnv === 'production' && (!prodKey || !prodSalt)) {
        errMsg.textContent = 'Production key and salt are required when Production env is active.';
        errMsg.style.display = 'block';
        return;
    }

    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving…';

    try {
        const res  = await fetch('/api/save-config.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ env: selectedEnv, test_key: testKey, test_salt: testSalt, prod_key: prodKey, prod_salt: prodSalt })
        });
        const data = await res.json();
        if (data.success) {
            saveBtn.textContent = '✓ Saved — reloading…';
            saveBtn.style.background = 'linear-gradient(135deg,#008558,#024538)';
            setTimeout(() => window.location.reload(), 800);
        } else {
            errMsg.textContent = data.message || 'Save failed.';
            errMsg.style.display = 'block';
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save configuration';
        }
    } catch(e) {
        errMsg.textContent = 'Network error — could not save.';
        errMsg.style.display = 'block';
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save configuration';
    }
});
</script>
</body>
</html>
