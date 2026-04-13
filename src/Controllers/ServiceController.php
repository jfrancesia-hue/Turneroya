<?php
declare(strict_types=1);

namespace TurneroYa\Controllers;

use TurneroYa\Core\Auth;
use TurneroYa\Core\Request;
use TurneroYa\Core\Session;
use TurneroYa\Core\Validator;
use TurneroYa\Models\Service;

final class ServiceController
{
    public function index(): string
    {
        $services = Service::allByBusiness(Auth::businessId());
        return view('dashboard/services/index', [
            'title' => 'Servicios',
            'pageTitle' => 'Servicios',
            'services' => $services,
        ]);
    }

    public function create(): string
    {
        return view('dashboard/services/form', [
            'title' => 'Nuevo servicio',
            'pageTitle' => 'Nuevo servicio',
            'service' => null,
            'action' => '/dashboard/services',
        ]);
    }

    public function store(): void
    {
        $this->csrf();
        $data = $this->validated();
        Service::create(array_merge($data, ['business_id' => Auth::businessId()]));
        flash('success', 'Servicio creado');
        redirect('/dashboard/services');
    }

    public function edit(array $params): string
    {
        $s = Service::find($params['id']);
        $this->ensureOwned($s);
        return view('dashboard/services/form', [
            'title' => 'Editar servicio',
            'pageTitle' => 'Editar servicio',
            'service' => $s,
            'action' => '/dashboard/services/' . $s['id'],
        ]);
    }

    public function update(array $params): void
    {
        $this->csrf();
        $s = Service::find($params['id']);
        $this->ensureOwned($s);
        Service::update($s['id'], $this->validated());
        flash('success', 'Servicio actualizado');
        redirect('/dashboard/services');
    }

    public function destroy(array $params): void
    {
        $this->csrf();
        $s = Service::find($params['id']);
        $this->ensureOwned($s);
        Service::delete($s['id']);
        flash('success', 'Servicio eliminado');
        redirect('/dashboard/services');
    }

    private function csrf(): void
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
            'description' => trim((string) Request::input('description', '')),
            'duration' => (int) Request::input('duration', 30),
            'price' => Request::input('price') !== '' && Request::input('price') !== null
                ? (float) Request::input('price') : null,
            'color' => (string) Request::input('color', '#10B981'),
            'is_active' => Request::input('is_active') ? true : false,
            'requires_deposit' => Request::input('requires_deposit') ? true : false,
            'deposit_amount' => Request::input('deposit_amount') !== '' && Request::input('deposit_amount') !== null
                ? (float) Request::input('deposit_amount') : null,
        ];
        $v = Validator::make($data, [
            'name' => 'required|min:2|max:100',
            'duration' => 'required|integer|min:5|max:600',
        ]);
        if ($v->fails()) {
            flash('error', $v->firstError() ?? 'Datos inválidos');
            back();
        }
        return $data;
    }

    private function ensureOwned(?array $s): void
    {
        if (!$s || $s['business_id'] !== Auth::businessId()) {
            flash('error', 'Servicio no encontrado');
            redirect('/dashboard/services');
        }
    }
}
