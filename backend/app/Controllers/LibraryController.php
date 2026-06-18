<?php
namespace App\Controllers;

use App\Controllers\AuthController;

class LibraryController
{
    public function __construct()
    {
        AuthController::requireLogin();
    }

    public function index()
    {
        $activeMenu = 'biblioteca';
        $viewPath = __DIR__ . '/../Views/library/index.php';
        require_once __DIR__ . '/../Views/layout.php';
    }
}
