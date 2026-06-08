<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Auth;
use App\Support\Guard;
use App\Support\View;

/**
 * Gestió de membres de la llar. Només el propietari (owner) hi té accés.
 */
final class MembersController
{
    public function index(): void
    {
        Guard::requireOwner();
        View::render('members/index', [
            'members' => User::allByHousehold((int) Auth::householdId()),
            'error'   => flash('member_error'),
            'ok'      => flash('member_ok'),
        ], 'layouts/app');
    }

    public function create(): void
    {
        Guard::requireOwner();
        $householdId = (int) Auth::householdId();

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($password) < 10) {
            flash('member_error', 'Revisa les dades: nom, correu vàlid i contrasenya de 10+ caràcters.');
            redirect('/members');
        }
        if (User::emailExists($email)) {
            flash('member_error', 'Ja existeix un usuari amb aquest correu.');
            redirect('/members');
        }

        $id = User::create($householdId, $name, $email, Auth::hash($password), 'member');
        AuditLog::record('member_created', 'user', $id, $email);
        flash('member_ok', 'Membre creat correctament.');
        redirect('/members');
    }

    /** @param array<string,string> $params */
    public function delete(array $params): void
    {
        Guard::requireOwner();
        $householdId = (int) Auth::householdId();
        $id = (int) ($params['id'] ?? 0);

        if ($id === Auth::id()) {
            flash('member_error', 'No et pots eliminar a tu mateix.');
            redirect('/members');
        }

        $target = User::find($id);
        if ($target === null || (int) $target['household_id'] !== $householdId || $target['role'] === 'owner') {
            flash('member_error', 'No es pot eliminar aquest usuari.');
            redirect('/members');
        }

        User::delete($id, $householdId);
        AuditLog::record('member_deleted', 'user', $id, $target['email']);
        flash('member_ok', 'Membre eliminat.');
        redirect('/members');
    }
}
