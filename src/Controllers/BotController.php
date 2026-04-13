<?php
declare(strict_types=1);

namespace TurneroYa\Controllers;

use TurneroYa\Core\Auth;
use TurneroYa\Core\Request;
use TurneroYa\Core\Session;
use TurneroYa\Models\Business;

final class BotController
{
    public function config(): string
    {
        $business = Business::find(Auth::businessId());
        return view('dashboard/bot/config', [
            'title' => 'Bot WhatsApp',
            'pageTitle' => 'Configuración del bot',
            'business' => $business,
        ]);
    }

    public function saveConfig(): void
    {
        if (!Session::verifyCsrf(Request::input('_csrf'))) {
            flash('error', 'Token inválido');
            redirect('/dashboard/bot/config');
        }
        Business::update(Auth::businessId(), [
            'bot_enabled' => Request::input('bot_enabled') ? true : false,
            'bot_welcome_message' => (string) Request::input('bot_welcome_message', ''),
            'bot_personality' => (string) Request::input('bot_personality', 'profesional y amigable'),
            'whatsapp' => (string) Request::input('whatsapp', ''),
        ]);
        flash('success', 'Configuración del bot guardada');
        redirect('/dashboard/bot/config');
    }
}
