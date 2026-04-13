<?php
declare(strict_types=1);

namespace TurneroYa\Controllers;

final class HomeController
{
    public function index(): string
    {
        return view('home');
    }
}
