<?php

declare(strict_types=1);

use App\Controllers\AccountsController;
use App\Controllers\AiController;
use App\Controllers\AuthController;
use App\Controllers\BankingController;
use App\Controllers\BudgetsController;
use App\Controllers\CategoriesController;
use App\Controllers\DashboardController;
use App\Controllers\ExportController;
use App\Controllers\GoalsController;
use App\Controllers\HomeController;
use App\Controllers\ImportController;
use App\Controllers\MembersController;
use App\Controllers\RecurringController;
use App\Controllers\ReportsController;
use App\Controllers\RulesController;
use App\Controllers\SettingsController;
use App\Controllers\TransactionsController;
use App\Controllers\UpdateController;
use App\Support\Router;

/**
 * Definició de rutes. Es retorna un callable que rep el Router.
 */
return static function (Router $router): void {
    $router->get('/', [HomeController::class, 'index']);
    $router->get('/health', [HomeController::class, 'health']);
    $router->get('/locale/{lang}', [HomeController::class, 'locale']);

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

    // Pressupostos
    $router->get('/budgets', [BudgetsController::class, 'index']);
    $router->post('/budgets', [BudgetsController::class, 'store']);
    $router->post('/budgets/{id}/edit', [BudgetsController::class, 'update']);
    $router->post('/budgets/{id}/delete', [BudgetsController::class, 'delete']);

    // Objectius d'estalvi
    $router->get('/goals', [GoalsController::class, 'index']);
    $router->post('/goals', [GoalsController::class, 'store']);
    $router->post('/goals/{id}/edit', [GoalsController::class, 'update']);
    $router->post('/goals/{id}/contribute', [GoalsController::class, 'contribute']);
    $router->post('/goals/{id}/delete', [GoalsController::class, 'delete']);

    // Recurrents / subscripcions
    $router->get('/recurring', [RecurringController::class, 'index']);
    $router->post('/recurring/detect', [RecurringController::class, 'detect']);
    $router->post('/recurring/{id}/delete', [RecurringController::class, 'delete']);

    // Capa d'IA
    $router->get('/ai/categorize', [AiController::class, 'categorize']);
    $router->post('/ai/categorize/suggest', [AiController::class, 'suggest']);
    $router->post('/ai/categorize/apply', [AiController::class, 'applyCategories']);
    $router->get('/ai/analysis', [AiController::class, 'analysis']);
    $router->post('/ai/analysis/generate', [AiController::class, 'generateAnalysis']);
    $router->get('/ai/chat', [AiController::class, 'chat']);
    $router->post('/ai/chat', [AiController::class, 'ask']);
    $router->post('/ai/chat/ask', [AiController::class, 'ask_json']);
    $router->get('/ai/chat/history', [AiController::class, 'history_json']);
    $router->post('/ai/chat/clear', [AiController::class, 'clearChat']);
    $router->get('/ai/settings', [AiController::class, 'settings']);
    $router->post('/ai/settings', [AiController::class, 'saveSettings']);

    // Informes i exportació
    $router->get('/reports/monthly', [ReportsController::class, 'monthly']);
    $router->get('/reports/monthly.pdf', [ReportsController::class, 'pdf']);
    $router->get('/export/transactions.csv', [ExportController::class, 'csv']);
    $router->get('/export/transactions.xls', [ExportController::class, 'xls']);

    // Importació de fitxers
    $router->get('/import', [ImportController::class, 'index']);
    $router->post('/import/norma43', [ImportController::class, 'previewNorma43']);
    $router->post('/import/csv', [ImportController::class, 'previewCsv']);
    $router->post('/import/confirm', [ImportController::class, 'confirm']);
    $router->post('/import/templates/{id}/delete', [ImportController::class, 'deleteTemplate']);

    // Enable Banking
    $router->get('/banking', [BankingController::class, 'index']);
    $router->get('/banking/settings', [BankingController::class, 'settings']);
    $router->post('/banking/settings', [BankingController::class, 'saveSettings']);
    $router->post('/banking/link', [BankingController::class, 'startLink']);
    $router->get('/banking/callback', [BankingController::class, 'callback']);
    $router->post('/banking/links/{id}/sync', [BankingController::class, 'sync']);

    // Sistema (actualitzacions OTA + backups)
    $router->get('/update', [UpdateController::class, 'index']);
    $router->post('/update/check', [UpdateController::class, 'check']);
    $router->post('/update/run', [UpdateController::class, 'run']);
    $router->post('/update/backup', [UpdateController::class, 'backup']);
    $router->post('/update/export', [UpdateController::class, 'export']);
    $router->post('/update/import', [UpdateController::class, 'import']);

    // Configuració
    $router->get('/settings', [SettingsController::class, 'index']);
    $router->post('/settings/household', [SettingsController::class, 'updateHousehold']);
    $router->get('/settings/2fa', [SettingsController::class, 'setup2fa']);
    $router->post('/settings/2fa/enable', [SettingsController::class, 'enable2fa']);
    $router->post('/settings/2fa/disable', [SettingsController::class, 'disable2fa']);
};
