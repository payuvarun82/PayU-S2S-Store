<?php

declare(strict_types=1);

final class RedirectStore
{
    private string $dir;

    public function __construct(?string $dir = null)
    {
        $this->dir = $dir ?? dirname(__DIR__) . '/storage/redirects';
        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0755, true);
        }
    }

    public function putHtml(string $html, int $ttlSeconds = 600): string
    {
        return $this->write([
            'type' => 'html',
            'html' => $html,
            'expires' => time() + $ttlSeconds,
        ]);
    }

    public function putMeta(array $meta, int $ttlSeconds = 600): string
    {
        return $this->write(array_merge($meta, [
            'expires' => time() + $ttlSeconds,
        ]));
    }

    public function consume(string $token): ?array
    {
        $path = $this->path($token);
        if (!is_file($path)) {
            return null;
        }

        $raw = file_get_contents($path);
        @unlink($path);

        if ($raw === false) {
            return null;
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || ($data['expires'] ?? 0) < time()) {
            return null;
        }

        return $data;
    }

    public function peek(string $token): ?array
    {
        $path = $this->path($token);
        if (!is_file($path)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data) || ($data['expires'] ?? 0) < time()) {
            @unlink($path);
            return null;
        }

        return $data;
    }

    private function write(array $payload): string
    {
        $token = bin2hex(random_bytes(16));
        file_put_contents($this->path($token), json_encode($payload), LOCK_EX);
        return $token;
    }

    private function path(string $token): string
    {
        $token = preg_replace('/[^a-f0-9]/', '', strtolower($token));
        return $this->dir . '/' . $token . '.json';
    }
}
