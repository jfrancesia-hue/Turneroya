<?php
declare(strict_types=1);

namespace TurneroYa\Controllers;

use TurneroYa\Core\Auth;
use TurneroYa\Services\GrowthEngine;

final class GrowthController
{
    public function index(): string
    {
        $businessId = Auth::businessId();
        if (!$businessId) redirect('/dashboard/onboarding');

        $engine = new GrowthEngine($businessId);
        return view('dashboard/growth/index', [
            'title' => 'Crecimiento',
            'pageTitle' => 'Crecimiento',
            'pageSubtitle' => 'CRM, agenda inteligente, dinero y automatizaciones',
            'growth' => $engine->commandCenter(),
        ]);
    }
}
