<?php

require_once __DIR__ . '/lib/bootstrap.php';

$payload = array_merge($_GET, $_POST);
$status = $payload['status'] ?? 'notify';

if ($status === 'notify') {
  $logFile = __DIR__ . '/storage/webhook.log';
  if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
  }
  file_put_contents($logFile, date('c') . ' ' . json_encode($payload) . PHP_EOL, FILE_APPEND);
  echo 'OK';
  exit;
}

$verified = false;
$hashValid = false;
if (!empty($payload['hash']) && !empty($payload['key'])) {
  $calculated = PayUHash::reverseHash($payload, $config['merchant_salt']);
  $hashValid = hash_equals($calculated, strtolower($payload['hash']));
  $verified = $hashValid;
}

$txnStatus = strtolower($payload['status'] ?? '');
$isSuccess = in_array($txnStatus, ['success', 'captured'], true);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Payment <?= $isSuccess ? 'Success' : 'Result' ?> | PayU S2S Store</title>
  <link rel="stylesheet" href="https://unpkg.com/element-plus/dist/index.css" />
  <style>
    body { margin: 0; background: #f5f7fa; font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; }
    .wrap { max-width: 640px; margin: 48px auto; padding: 0 16px; }
  </style>
</head>
<body>
  <div id="app" class="wrap">
    <el-result
      :icon="icon"
      :title="title"
      :sub-title="subTitle"
    >
      <template #extra>
        <el-descriptions :column="1" border style="margin-bottom: 20px; text-align: left;">
          <el-descriptions-item label="Transaction ID">{{ txnid }}</el-descriptions-item>
          <el-descriptions-item label="Amount">₹{{ amount }}</el-descriptions-item>
          <el-descriptions-item label="Status">{{ status }}</el-descriptions-item>
          <el-descriptions-item label="Hash verified">{{ hashValid ? 'Yes' : 'No' }}</el-descriptions-item>
        </el-descriptions>
        <el-button type="primary" @click="goHome">Back to Store</el-button>
      </template>
    </el-result>
  </div>

  <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
  <script src="https://unpkg.com/element-plus"></script>
  <script>
    const { createApp, ref, computed } = Vue;
    createApp({
      setup() {
        const txnid = <?= json_encode($payload['txnid'] ?? '-') ?>;
        const amount = <?= json_encode($payload['amount'] ?? '-') ?>;
        const status = <?= json_encode($payload['status'] ?? 'unknown') ?>;
        const hashValid = <?= $hashValid ? 'true' : 'false' ?>;
        const isSuccess = <?= $isSuccess ? 'true' : 'false' ?>;

        const icon = computed(() => isSuccess ? 'success' : 'error');
        const title = computed(() => isSuccess ? 'Payment Successful' : 'Payment <?= $status === 'failure' ? 'Failed' : 'Processed' ?>');
        const subTitle = computed(() => hashValid
          ? 'PayU callback hash verified successfully.'
          : 'Callback received. Verify payment server-side before fulfilling order.');

        const goHome = () => { window.location.href = '/'; };

        return { txnid, amount, status, hashValid, icon, title, subTitle, goHome };
      }
    }).use(ElementPlus).mount('#app');
  </script>
</body>
</html>
