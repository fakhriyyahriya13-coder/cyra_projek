<?php

function cyraStartSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function cyraIsLoggedIn(): bool
{
    cyraStartSession();

    return isset($_SESSION['login']) && $_SESSION['login'] === true;
}

function cyraRequireAdmin(string $redirectPath = '../auth/login.php'): void
{
    if (!cyraIsLoggedIn()) {
        header('Location: ' . $redirectPath);
        exit;
    }
}

function cyraCurrentUsername(string $default = 'Admin'): string
{
    cyraStartSession();

    return $_SESSION['username'] ?? $default;
}
