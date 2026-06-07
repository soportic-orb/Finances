<?php

declare(strict_types=1);

use App\Controllers\AccountsController;
use App\Controllers\AuthController;
use App\Controllers\BankingController;
use App\Controllers\CategoriesController;
use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use App\Controllers\MembersController;
use App\Controllers\RulesController;
use App\Controllers\SettingsController;
use App\Controllers\TransactionsController;
use App\Support\Router;

/**
 * Definició de rutes. Es retorna un callable que rep el Router.
 */
return static function (Router $router): void {
    $router->get('/', [HomeController::class, 'index']);
    $router->get('/health', [HomeController::class, 'health']);

    // Autenticació
    $router->get('/login', [AuthController::class, 'showLogin']);
    $router->post('/login', [AuthController::class, 'login']);
    $router->get('/2fa', [AuthController::class, 'show2fa']);
    $router->post('/2fa', [AuthController::class, 'verify2fa']);
    $router->post('/logout', [AuthController::class, 'logout']);

    // Panell
    $router->get('/dashboard', [DashboardController::class, 'index']);

    // Comptes
    $router->get('/accounts', [AccountsController::class, 'index']);
    $router->post('/accounts', [AccountsController::class, 'store']);
    $router->get('/accounts/{id}/edit', [AccountsController::class, 'edit']);
    $router->post('/accounts/{id}/edit', [AccountsController::class, 'update']);
    $router->post('/accounts/{id}/archive', [AccountsController::class, 'archive']);
    $router->post('/accounts/{id}/delete', [AccountsController::class, 'delete']);

    // Moviments
    $router->get('/transactions', [TransactionsController::class, 'index']);
    $router->post('/transactions', [TransactionsController::class, 'store']);
    $router->post('/transfers', [TransactionsController::class, 'transfer']);
    $router->get('/transactions/{id}/edit', [TransactionsController::class, 'edit']);
    $router->post('/transactions/{id}/edit', [TransactionsController::class, 'update']);
    $router->post('/transactions/{id}/delete', [TransactionsController::class, 'delete']);

    // Membres (owner)
    $router->get('/members', [MembersController::class, 'index']);
    $router->post('/members/create', [MembersController::class, 'create']);
    $router->post('/members/{id}/delete', [MembersController::class, 'delete']);

    // Categories
    $router->get('/categories', [CategoriesController::class, 'index']);
    $router->post('/categories', [CategoriesController::class, 'store']);
    $router->get('/categories/{id}/edit', [CategoriesController::class, 'edit']);
    $router->post('/categories/{id}/edit', [CategoriesController::class, 'update']);
    $router->post('/categories/{id}/delete', [CategoriesController::class, 'delete']);

    // Regles de categorització
    $router->get('/rules', [RulesController::class, 'index']);
    $router->post('/rules', [RulesController::class, 'store']);
    $router->post('/rules/apply', [RulesController::class, 'apply']);
    $router->get('/rules/{id}/edit', [RulesController::class, 'edit']);
    $router->post('/rules/{id}/edit', [RulesController::class, 'update']);
    $router->post('/rules/{id}/delete', [RulesController::class, 'delete']);

    // Enable Banking
    $router->get('/banking', [BankingController::class, 'index']);
    $router->get('/banking/settings', [BankingController::class, 'settings']);
    $router->post('/banking/settings', [BankingController::class, 'saveSettings']);
    $router->post('/banking/link', [BankingController::class, 'startLink']);
    $router->get('/banking/callback', [BankingController::class, 'callback']);
    $router->post('/banking/links/{id}/sync', [BankingController::class, 'sync']);

    // Configuració
    $router->get('/settings', [SettingsController::class, 'index']);
    $router->post('/settings/household', [SettingsController::class, 'updateHousehold']);
    $router->get('/settings/2fa', [SettingsController::class, 'setup2fa']);
    $router->post('/settings/2fa/enable', [SettingsController::class, 'enable2fa']);
    $router->post('/settings/2fa/disable', [SettingsController::class, 'disable2fa']);
};
