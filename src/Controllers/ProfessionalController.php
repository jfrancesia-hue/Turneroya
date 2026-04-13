<?php
declare(strict_types=1);

namespace TurneroYa\Controllers;

use TurneroYa\Core\Auth;
use TurneroYa\Core\Request;
use TurneroYa\Core\Session;
use TurneroYa\Core\Validator;
use TurneroYa\Models\Professional;
use TurneroYa\Models\Service;

final class ProfessionalController
{
    public function index(): string
    {
        $businessId = Auth::businessId();
        $pros = Professional::allByBusiness($businessId);
        return view('dashboard/professionals/index', [
            'title' => 'Profesionales',
            'pageTitle' => 'Profesionales',
            'professionals' => $pros,
        ]);
    }

    public function create(): string
    {
        $services = Service::allByBusiness(Auth::businessId(), true);
        return view('dashboard/professionals/form', [
            'title' => 'Nuevo profesional',
            'pageTitle' => 'Nuevo profesional',
            'professional' => null,
            'services' => $services,
            'selectedServices' => [],
            'action' => '/dashboard/professionals',
        ]);
    }

    public function store(): void
    {
        $this->verifyCsrf();
        $data = $this->validated();
        $serviceIds = (array) Request::input('service_ids', []);

        $id = Professional::create([
            'business_id' => Auth::businessId(),
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'specialization' => $data['specialization'],
            'bio' => $data['bio'],
            'color' => $data['color'],
            'is_active' => true,
        ]);
        Professional::syncServices($id, $serviceIds);

        flash('success', 'Profesional creado correctamente');
        redirect('/dashboard/professionals');
    }

    public function edit(array $params): string
    {
        $pro = Professional::find($params['id']);
        $this->ensureOwned($pro);
        $services = Service::allByBusiness(Auth::businessId(), true);
        $selected = array_column(Professional::servicesForProfessional($pro['id']), 'id');
        return view('dashboard/professionals/form', [
            'title' => 'Editar profesional',
            'pageTitle' => 'Editar profesional',
            'professional' => $pro,
            'services' => $services,
            'selectedServices' => $selected,
            'action' => '/dashboard/professionals/' . $pro['id'],
        ]);
    }

    public function update(array $params): void
    {
        $this->verifyCsrf();
        $pro = Professional::find($params['id']);
        $this->ensureOwned($pro);
        $data = $this->validated();
        Professional::update($pro['id'], [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'specialization' => $data['specialization'],
            'bio' => $data['bio'],
            'color' => $data['color'],
            'is_active' => Request::input('is_active') ? true : false,
        ]);
        Professional::syncServices($pro['id'], (array) Request::input('service_ids', []));
        flash('success', 'Profesional actualizado');
        redirect('/dashboard/professionals');
    }

    public function destroy(array $params): void
    {
        $this->verifyCsrf();
        $pro = Professional::find($params['id']);
        $this->ensureOwned($pro);
        Professional::delete($pro['id']);
        flash('success', 'Profesional eliminado');
        redirect('/dashboard/professionals');
    }

    private function verifyCsrf(): void
    {
        if (!Session::verifyCsrf(Request::input('_csrf'))) {
            flash('error', 'Token inválido');
            back();
        }
    }

    private function validated(): array
    {
        $data = [
            'name' => trim((string) Request::input('name', '')),
            'email' => trim((string) Request::input('email', '')),
            'phone' => trim((string) Request::input('phone', '')),
            'specialization' => trim((string) Request::input('specialization', '')),
            'bio' => trim((string) Request::input('bio', '')),
            'color' => (string) Request::input('color', '#3B82F6'),
        ];
        $v = Validator::make($data, [
            'name' => 'required|min:2|max:100',
            'email' => 'email',
        ]);
        if ($v->fails()) {
            flash('error', $v->firstError() ?? 'Datos inválidos');
            back();
        }
        return $data;
    }

    private function ensureOwned(?array $pro): void
    {
        if (!$pro || $pro['business_id'] !== Auth::businessId()) {
            http_response_code(404);
            flash('error', 'Profesional no encontrado');
            redirect('/dashboard/professionals');
        }
    }
}
