<?php
declare(strict_types=1);

namespace TurneroYa\Controllers;

use TurneroYa\Core\Auth;
use TurneroYa\Core\Request;
use TurneroYa\Core\Session;
use TurneroYa\Models\Schedule;
use TurneroYa\Models\Professional;

final class ScheduleController
{
    public function index(): string
    {
        $businessId = Auth::businessId();
        $businessSchedules = Schedule::forBusiness($businessId);
        $professionals = Professional::allByBusiness($businessId, true);
        $proSchedules = [];
        foreach ($professionals as $p) {
            $proSchedules[$p['id']] = Schedule::forProfessional($p['id']);
        }
        return view('dashboard/schedules/index', [
            'title' => 'Horarios',
            'pageTitle' => 'Horarios',
            'businessSchedules' => $businessSchedules,
            'professionals' => $professionals,
            'proSchedules' => $proSchedules,
        ]);
    }

    public function save(): void
    {
        if (!Session::verifyCsrf(Request::input('_csrf'))) {
            flash('error', 'Token inválido');
            redirect('/dashboard/schedules');
        }

        $professionalId = Request::input('professional_id') ?: null;
        $schedules = [];
        for ($dow = 0; $dow <= 6; $dow++) {
            $schedules[] = [
                'day_of_week' => $dow,
                'is_active' => Request::input("day_{$dow}_active") ? true : false,
                'start_time' => (string) Request::input("day_{$dow}_start", '09:00'),
                'end_time' => (string) Request::input("day_{$dow}_end", '18:00'),
                'break_start' => (string) Request::input("day_{$dow}_break_start", '') ?: null,
                'break_end' => (string) Request::input("day_{$dow}_break_end", '') ?: null,
            ];
        }

        Schedule::replaceForProfessional(Auth::businessId(), $professionalId, $schedules);
        flash('success', 'Horarios guardados');
        redirect('/dashboard/schedules');
    }
}
