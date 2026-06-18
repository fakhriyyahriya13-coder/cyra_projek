<?php

function cyraBasePath(string $path = ''): string
{
    $basePath = dirname(__DIR__, 2);
    $path = ltrim($path, "\\/");

    return $path === '' ? $basePath : $basePath . DIRECTORY_SEPARATOR . $path;
}

function cyraAppPath(string $path = ''): string
{
    return cyraBasePath('app' . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, "\\/") : ''));
}

function cyraAssetPath(string $path = ''): string
{
    return cyraBasePath('assets' . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, "\\/") : ''));
}
