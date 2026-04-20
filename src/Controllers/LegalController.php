<?php
declare(strict_types=1);

namespace TurneroYa\Controllers;

final class LegalController
{
    public function terms(): string
    {
        return view('legal/terms', ['title' => 'Términos y Condiciones']);
    }

    public function privacy(): string
    {
        return view('legal/privacy', ['title' => 'Política de Privacidad']);
    }
}
