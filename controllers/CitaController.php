<?php

namespace Controllers;

Use Model\Hora;
use MVC\Router;

class CitaController
{
    public static function index(Router $router)
    {

        if (!isset($_SESSION)) {
            session_start();
        }

        isAuth();
        $horas  = Hora::all();

        $router->render('cita/index', [
            'nombre' => $_SESSION['nombre'],
            'id' => $_SESSION['id'],
            'horas' => $horas
        ]);
    }
}
