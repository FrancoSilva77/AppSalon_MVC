<?php

namespace Controllers;

use Model\CitaFecha;

class APICitas
{

  public static function index()
  {
    $fecha = $_GET['fecha'] ?? '';

    if (!$fecha) {
      echo json_encode([]);
      return;
    }

    // Consultar la base de datos
    $citas = CitaFecha::whereArray(['fecha' => $fecha]) ?? [];
    echo json_encode($citas);
  }
}
