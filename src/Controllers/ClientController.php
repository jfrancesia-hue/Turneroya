<?php
declare(strict_types=1);

namespace TurneroYa\Controllers;

use TurneroYa\Core\Auth;
use TurneroYa\Core\Request;
use TurneroYa\Core\Session;
use TurneroYa\Core\Validator;
use TurneroYa\Models\Client;
use TurneroYa\Models\Booking;

final class ClientController
{
    public function index(): string
    {
        $q = (string) Request::query('q', '');
        $clients = Client::allByBusiness(Auth::businessId(), $q);
        return view('dashboard/clients/index', [
            'title' => 'Clientes',
            'pageTitle' => 'Clientes',
            'clients' => $clients,
            'q' => $q,
        ]);
    }

    public function create(): string
    {
        return view('dashboard/clients/form', [
            'title' => 'Nuevo cliente',
            'pageTitle' => 'Nuevo cliente',
            'client' => null,
            'action' => '/dashboard/clients',
        ]);
    }

    public function store(): void
    {
        $this->csrf();
        $data = $this->validated();
        Client::create(array_merge($data, ['business_id' => Auth::businessId()]));
        flash('success', 'Cliente creado');
        redirect('/dashboard/clients');
    }

    public function show(array $params): string
    {
        $client = Client::find($params['id']);
        $this->ensureOwned($client);
        $history = Booking::forClient(Auth::businessId(), $client['id']);
        return view('dashboard/clients/show', [
            'title' => $client['name'],
            'pageTitle' => $client['name'],
            'client' => $client,
            'history' => $history,
        ]);
    }

    public function update(array $params): void
    {
        $this->csrf();
        $c = Client::find($params['id']);
        $this->ensureOwned($c);
        Client::update($c['id'], $this->validated());
        flash('success', 'Cliente actualizado');
        redirect('/dashboard/clients/' . $c['id']);
    }

    public function destroy(array $params): void
    {
        $this->csrf();
        $c = Client::find($params['id']);
        $this->ensureOwned($c);
        Client::delete($c['id']);
        flash('success', 'Cliente eliminado');
        redirect('/dashboard/clients');
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
            'phone' => Client::normalizePhone((string) Request::input('phone', '')),
            'email' => trim((string) Request::input('email', '')),
            'whatsapp_number' => Client::normalizePhone((string) Request::input('whatsapp_number', '')),
            'notes' => trim((string) Request::input('notes', '')),
        ];
        $v = Validator::make($data, [
            'name' => 'required|min:2|max:100',
            'email' => 'email',
        ]);
        if ($v->fails()) {
            flash('error', $v->firstError() ?? 'Datos inválidos');
            back();
        }
        // Defaults para campos nullable
        foreach (['phone','email','whatsapp_number','notes'] as $k) {
            if ($data[$k] === '') $data[$k] = null;
        }
        return $data;
    }

    private function ensureOwned(?array $c): void
    {
        if (!$c || $c['business_id'] !== Auth::businessId()) {
            flash('error', 'Cliente no encontrado');
            redirect('/dashboard/clients');
        }
    }
}
