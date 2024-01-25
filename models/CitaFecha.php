<?php

namespace Model;

class CitaFecha extends ActiveRecord
{
  protected static $tabla = 'citas';
  protected static $columnasBD = ['id', 'fecha', 'horaId'];

  public $id;
  public $fecha;
  public $horaId;
}
