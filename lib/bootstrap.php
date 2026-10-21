<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

$configPath = dirname(__DIR__) . '/config.php';
if (!file_exists($configPath)) {
  http_response_code(500);
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'message' => 'config.php not found. Copy config.example.php to config.php']);
  exit;
}

$config = require $configPath;
require_once __DIR__ . '/PayUHash.php';
require_once __DIR__ . '/PayUClient.php';

function jsonResponse(array $payload, int $code = 200): void
{
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode($payload, JSON_UNESCAPED_SLASHES);
  exit;
}

function readJsonBody(): array
{
  $raw = file_get_contents('php://input');
  if (!$raw) {
    return [];
  }
  $decoded = json_decode($raw, true);
  return is_array($decoded) ? $decoded : [];
}
