<?php

require_once dirname(__DIR__) . '/lib/bootstrap.php';

$client = new PayUClient($config);

$banks = [
  ['code' => 'TESTPGNB', 'name' => 'Test Net Banking (Sandbox)', 'test' => true],
  ['code' => 'HDFB', 'name' => 'HDFC Bank'],
  ['code' => 'ICIB', 'name' => 'ICICI Bank'],
  ['code' => 'AXIB', 'name' => 'Axis Bank'],
  ['code' => 'SBIB', 'name' => 'State Bank of India'],
  ['code' => 'PNBB', 'name' => 'Punjab National Bank'],
  ['code' => 'YESB', 'name' => 'Yes Bank'],
  ['code' => 'KOTB', 'name' => 'Kotak Mahindra Bank'],
  ['code' => 'INDB', 'name' => 'Indian Bank'],
  ['code' => 'CABB', 'name' => 'Canara Bank'],
];

jsonResponse([
  'success' => true,
  'banks' => $banks,
  'test_vpa' => 'anything@payu',
  'test_card' => [
    'number' => '5123456789012346',
    'cvv' => '123',
    'exp_month' => '05',
    'exp_year' => '2030',
    'name' => 'Test User',
  ],
]);
