<?php
declare(strict_types=1);

namespace TurneroYa\Controllers;

use TurneroYa\Core\Auth;
use TurneroYa\Core\Request;
use TurneroYa\Core\Session;
use TurneroYa\Core\Validator;
use TurneroYa\Core\Database;
use TurneroYa\Models\Business;
use TurneroYa\Models\User;
use TurneroYa\Models\Professional;
use TurneroYa\Models\Service;
use TurneroYa\Models\Schedule;

final class OnboardingController
{
    public function show(): string
    {
        if (Auth::businessId()) redirect('/dashboard');
        return view('onboarding/index', ['title' => 'Configuración inicial']);
    }

    public function save(): void
    {
        if (!Session::verifyCsrf(Request::input('_csrf'))) {
            flash('error', 'Token inválido');
            redirect('/dashboard/onboarding');
        }

        $data = [
            'business_name' => trim((string) Request::input('business_name', '')),
            'business_type' => (string) Request::input('business_type', 'OTHER'),
            'phone' => trim((string) Request::input('phone', '')),
            'whatsapp' => trim((string) Request::input('whatsapp', '')),
            'city' => trim((string) Request::input('city', '')),
            'service_name' => trim((string) Request::input('service_name', '')),
            'service_duration' => (int) Request::input('service_duration', 30),
            'service_price' => (float) Request::input('service_price', 0),
            'professional_name' => trim((string) Request::input('professional_name', '')),
            'start_hour' => (string) Request::input('start_hour', '09:00'),
            'end_hour' => (string) Request::input('end_hour', '18:00'),
        ];

        $v = Validator::make($data, [
            'business_name' => 'required|min:2|max:100',
            'business_type' => 'required|in:SALON,CLINIC,WORKSHOP,STUDIO,GYM,VET,DENTIST,LAWYER,ACCOUNTANT,OTHER',
            'service_name' => 'required|min:2',
            'service_duration' => 'required|integer|min:5',
            'professional_name' => 'required|min:2',
        ]);

        if ($v->fails()) {
            flash('error', $v->firstError() ?? 'Datos inválidos');
            redirect('/dashboard/onboarding');
        }

        $days = Request::input('active_days', ['1','2','3','4','5']);
        if (!is_array($days)) $days = ['1','2','3','4','5'];

        try {
            Database::transaction(function () use ($data, $days) {
                // 1) Crear negocio
                $businessId = Business::create([
                    'name' => $data['business_name'],
                    'slug' => Business::uniqueSlug($data['business_name']),
                    'type' => $data['business_type'],
                    'phone' => $data['phone'],
                    'whatsapp' => $data['whatsapp'],
                    'city' => $data['city'],
                ]);

                // 2) Vincular usuario
                User::attachBusiness(Auth::id(), $businessId);

                // 3) Crear primer servicio
                $serviceId = Service::create([
                    'business_id' => $businessId,
                    'name' => $data['service_name'],
                    'duration' => $data['service_duration'],
                    'price' => $data['service_price'] > 0 ? $data['service_price'] : null,
                ]);

                // 4) Crear primer profesional
                $professionalId = Professional::create([
                    'business_id' => $businessId,
                    'name' => $data['professional_name'],
                ]);

                // 5) Asociar profesional con servicio
                Database::insert('professional_services', [
                    'professional_id' => $professionalId,
                    'service_id' => $serviceId,
                ]);

                // 6) Crear horarios para los días activos (a nivel negocio)
                foreach ($days as $dow) {
                    $dow = (int) $dow;
                    if ($dow < 0 || $dow > 6) continue;
                    Database::insert('schedules', [
                        'day_of_week' => $dow,
                        'start_time' => $data['start_hour'],
                        'end_time' => $data['end_hour'],
                        'business_id' => $businessId,
                    ]);
                }
            });

            Auth::refresh();
            flash('success', '¡Listo! Tu negocio está configurado. Ya podés recibir turnos.');
            redirect('/dashboard');
        } catch (\Throwable $e) {
            flash('error', 'Error al guardar: ' . $e->getMessage());
            redirect('/dashboard/onboarding');
        }
    }
}
