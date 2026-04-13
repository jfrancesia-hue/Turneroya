<?php
declare(strict_types=1);

namespace TurneroYa\Controllers;

use TurneroYa\Core\Auth;
use TurneroYa\Core\Request;
use TurneroYa\Core\Session;
use TurneroYa\Models\Business;

final class SettingsController
{
    public function index(): string
    {
        return view('dashboard/settings/index', [
            'title' => 'Ajustes',
            'pageTitle' => 'Ajustes del negocio',
            'business' => Business::find(Auth::businessId()),
        ]);
    }

    public function save(): void
    {
        if (!Session::verifyCsrf(Request::input('_csrf'))) {
            flash('error', 'Token inválido');
            redirect('/dashboard/settings');
        }
        Business::update(Auth::businessId(), [
            'name' => (string) Request::input('name', ''),
            'phone' => (string) Request::input('phone', ''),
            'whatsapp' => (string) Request::input('whatsapp', ''),
            'email' => (string) Request::input('email', ''),
            'address' => (string) Request::input('address', ''),
            'city' => (string) Request::input('city', ''),
            'province' => (string) Request::input('province', ''),
            'description' => (string) Request::input('description', ''),
            'slot_duration' => (int) Request::input('slot_duration', 30),
            'max_advance_days' => (int) Request::input('max_advance_days', 30),
            'min_advance_hours' => (int) Request::input('min_advance_hours', 2),
            'reminder_hours_before' => (int) Request::input('reminder_hours_before', 24),
            'auto_reminder' => Request::input('auto_reminder') ? true : false,
            'require_confirmation' => Request::input('require_confirmation') ? true : false,
            'allow_cancellation' => Request::input('allow_cancellation') ? true : false,
            'cancellation_hours_limit' => (int) Request::input('cancellation_hours_limit', 4),
        ]);
        flash('success', 'Ajustes guardados');
        redirect('/dashboard/settings');
    }
}
