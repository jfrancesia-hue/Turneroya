<?php
declare(strict_types=1);

namespace TurneroYa\Controllers;

use TurneroYa\Core\Auth;
use TurneroYa\Core\Request;
use TurneroYa\Core\Session;
use TurneroYa\Core\Validator;
use TurneroYa\Models\User;

final class AuthController
{
    public function showLogin(): string
    {
        return view('auth/login', ['title' => 'Iniciar sesión']);
    }

    public function login(): void
    {
        if (!Session::verifyCsrf(Request::input('_csrf'))) {
            flash('error', 'Token de seguridad inválido');
            redirect('/login');
        }

        $data = [
            'email' => trim((string) Request::input('email', '')),
            'password' => (string) Request::input('password', ''),
        ];
        $v = Validator::make($data, [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);
        if ($v->fails()) {
            flash('error', $v->firstError() ?? 'Datos inválidos');
            Session::flashOldInput(['email' => $data['email']]);
            redirect('/login');
        }

        $user = Auth::attempt($data['email'], $data['password']);
        if (!$user) {
            flash('error', 'Email o contraseña incorrectos');
            Session::flashOldInput(['email' => $data['email']]);
            redirect('/login');
        }

        User::update($user['id'], ['last_login_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')]);
        redirect($user['business_id'] ? '/dashboard' : '/dashboard/onboarding');
    }

    public function showRegister(): string
    {
        return view('auth/register', ['title' => 'Crear cuenta']);
    }

    public function register(): void
    {
        if (!Session::verifyCsrf(Request::input('_csrf'))) {
            flash('error', 'Token de seguridad inválido');
            redirect('/register');
        }

        $data = [
            'name' => trim((string) Request::input('name', '')),
            'email' => strtolower(trim((string) Request::input('email', ''))),
            'password' => (string) Request::input('password', ''),
            'password_confirmation' => (string) Request::input('password_confirmation', ''),
        ];
        $v = Validator::make($data, [
            'name' => 'required|min:2|max:100',
            'email' => 'required|email|max:150',
            'password' => 'required|min:6|confirmed',
        ]);
        if ($v->fails()) {
            flash('error', $v->firstError() ?? 'Datos inválidos');
            Session::flashOldInput(['name' => $data['name'], 'email' => $data['email']]);
            redirect('/register');
        }

        if (User::findByEmail($data['email'])) {
            flash('error', 'Ya existe una cuenta con ese email');
            Session::flashOldInput(['name' => $data['name'], 'email' => $data['email']]);
            redirect('/register');
        }

        $userId = User::create([
            'email' => $data['email'],
            'password_hash' => Auth::hash($data['password']),
            'name' => $data['name'],
            'role' => 'OWNER',
        ]);

        $user = User::find($userId);
        if ($user) Auth::login($user);
        redirect('/dashboard/onboarding');
    }

    public function logout(): void
    {
        Auth::logout();
        flash('success', 'Sesión cerrada');
        redirect('/');
    }
}
