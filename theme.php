<?php

declare(strict_types=1);

function asset_url(string $path): string
{
    static $prefix;

    if (null === $prefix) {
        $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        $dir = str_replace('\\', '/', dirname($scriptName));
        if ('.' === $dir || '/' === $dir) {
            $dir = '';
        }
        $prefix = '' === $dir ? '' : rtrim($dir, '/') . '/';
    }

    return $prefix . ltrim($path, '/');
}

function resolve_theme_assets(array $config): array
{
    $default = 'terminal';
    $selected = strtolower((string)($config['theme'] ?? $default));
    $selected = preg_replace('/[^a-z0-9_-]/', '', $selected) ?: $default;

    $themeDir = __DIR__ . '/themes';
    $themePath = $themeDir . '/' . $selected . '.css';

    if (!is_readable($themePath)) {
        $selected = $default;
        $themePath = $themeDir . '/' . $default . '.css';
    }

    return [
        'name' => $selected,
        'theme' => asset_url('themes/' . $selected . '.css'),
        'base' => asset_url('css/base.css'),
        'script' => asset_url('encryption.js'),
        'body_class' => 'theme-' . $selected,
    ];
}

function list_available_themes(): array
{
    $themeDir = __DIR__ . '/themes';
    if (!is_dir($themeDir)) {
        return [];
    }

    $files = glob($themeDir . '/*.css') ?: [];

    $names = array_map(static function (string $file): string {
        return basename($file, '.css');
    }, $files);

    sort($names);

    return $names;
}
