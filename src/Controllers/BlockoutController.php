<?php
declare(strict_types=1);

namespace TurneroYa\Controllers;

use TurneroYa\Core\Auth;
use TurneroYa\Core\Request;
use TurneroYa\Core\Session;
use TurneroYa\Models\Blockout;
use TurneroYa\Models\Professional;

final class BlockoutController
{
    public function index(): string
    {
        $businessId = Auth::businessId();
        return view('dashboard/blockouts/index', [
            'title' => 'Bloqueos',
            'pageTitle' => 'Bloqueos / Vacaciones',
            'blockouts' => Blockout::forBusiness($businessId),
            'professionals' => Professional::allByBusiness($businessId),
        ]);
    }

    public function store(): void
    {
        if (!Session::verifyCsrf(Request::input('_csrf'))) {
            flash('error', 'Token inválido');
            redirect('/dashboard/blockouts');
        }
        $start = (string) Request::input('start_date', '');
        $end = (string) Request::input('end_date', '');
        if (!$start || !$end) {
            flash('error', 'Fechas requeridas');
            redirect('/dashboard/blockouts');
        }
        Blockout::create([
            'business_id' => Auth::businessId(),
            'professional_id' => Request::input('professional_id') ?: null,
            'title' => (string) Request::input('title', 'Bloqueo'),
            'start_date' => $start,
            'end_date' => $end,
            'all_day' => Request::input('all_day') ? true : false,
        ]);
        flash('success', 'Bloqueo creado');
        redirect('/dashboard/blockouts');
    }

    public function destroy(array $params): void
    {
        if (!Session::verifyCsrf(Request::input('_csrf'))) {
            redirect('/dashboard/blockouts');
        }
        Blockout::delete($params['id']);
        flash('success', 'Bloqueo eliminado');
        redirect('/dashboard/blockouts');
    }
}
