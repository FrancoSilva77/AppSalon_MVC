<?php

namespace Controllers;

use Classes\Email;
use Model\Usuario;
use MVC\Router;

class LoginController
{
  public static function login(Router $router)
  {
    $alertas = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $auth = new Usuario($_POST);

      $alertas = $auth->validarLogin();

      if (empty($alertas)) {
        // Comprobar que exista el usuario
        $usuario = Usuario::where('email', $auth->email);
        if ($usuario) {
          // Verificar el password
          if ($usuario->comprobarPasswordAndVerificado($auth->password)) {
            // Autenticar el ususario
            session_start();

            $_SESSION['id'] = $usuario->id;
            $_SESSION['nombre'] = $usuario->nombre . " " . $usuario->apellido;
            $_SESSION['email'] = $usuario->email;
            $_SESSION['login'] = true;

            // Redireccionamiento
            if ($usuario->admin === "1") {
              $_SESSION['admin'] = $usuario->admin ?? null;
              header('Location: /admin');
            } else {
              header('Location: /cita');
            }
          }
        } else {
          Usuario::setAlerta('error', 'Usuario no encontrado');
        }
      }
    }

    $alertas = Usuario::getAlertas();

    $router->render('/auth/login', [
      "titulo" => 'Iniciar sesión',
      "alertas" => $alertas
    ]);
  }

  public static function logout()
  {
    session_start();
    
    $_SESSION = [];

    header('Location: /');
  }

  public static function olvide(Router $router)
  {
    $alertas = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $auth = new Usuario($_POST);
      $alertas = $auth->validarEmail();

      if (empty($alertas)) {
        $usuario = Usuario::where('email', $auth->email);

        if ($usuario && $usuario->confirmado === "1") {
          // Generar un token
          $usuario->crearToken();
          $usuario->guardar();

          $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
          $email->enviarInstrucciones();

          // Alerta de exito
          Usuario::setAlerta('exito', 'Revisa tu email');
        } else {
          Usuario::setAlerta('error', 'El usuario no existe o no esta confirmado');
          $alertas = Usuario::getAlertas();
        }
      }
    }

    $alertas = Usuario::getAlertas();

    $router->render('auth/olvide-password', [
      'titulo' => 'Olvide mi Contraseña',
      'alertas' => $alertas
    ]);
  }

  public static function recuperar(Router $router)
  {
    $alertas = [];
    $error = false;

    $token = s($_GET['token']);

    // Buscar usuario por su token
    $usuario = Usuario::where('token', $token);

    if (empty($usuario)) {
      Usuario::setAlerta('error', 'Token No Válido');
      $error = true;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      // Leer el nuevo password y guardarlo

      $password = new Usuario($_POST);
      $alertas = $password->validarPassword();

      if (empty($alertas)) {
        $usuario->password = null;
        $usuario->password = $password->password;
        $usuario->hashPassword();
        $usuario->token = null;

        $resultado =  $usuario->guardar();

        if ($resultado) {
          header('Location: /login');
        }
      }
    }


    // debuguear($usuario);

    $alertas = Usuario::getAlertas();

    $router->render('auth/recuperar-password', [
      'titulo' => "Recuperar Contraseña",
      'alertas' => $alertas,
      'error' => $error
    ]);
  }

  public static function crear(Router $router)
  {
    $usuario = new Usuario;

    // Alertas vacías
    $alertas = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

      $usuario->sincronizar($_POST);
      $alertas = $usuario->validarNuevaCuenta();

      if (empty($alertas)) {
        // Verificar que el usuario no esté registrado
        $resultado = $usuario->existeUsuario();

        if ($resultado->num_rows) {
          $alertas = Usuario::getAlertas();
        } else {
          $usuario->hashPassword();

          // Generar un token ünico
          $usuario->crearToken();

          // Enviar el email
          $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
          $email->enviarConfirmacion();

          // Crear el usuario
          $resultado = $usuario->guardar();

          if ($resultado) {
            header('Location: /mensaje');
          }
        }
      }
    }

    $router->render('/auth/crear-cuenta', [
      'titulo' => 'Crear cuenta',
      'usuario' => $usuario,
      'alertas' => $alertas
    ]);
  }

  public static function mensaje(Router $router)
  {
    $router->render('auth/mensaje');
  }

  public static function confirmar(Router $router)
  {
    $alertas = [];
    $token = s($_GET['token']);
    $usuario = Usuario::where('token', $token);

    if (empty($usuario)) {
      // Mostrar mensaje de error
      Usuario::setAlerta('error', 'Token no válido');
    } else {
      $usuario->confirmado = '1';
      $usuario->token = "";
      $usuario->guardar();
      Usuario::setAlerta('exito', 'Confirmación de cuenta exitosa');
    }

    // Obtener alertas
    $alertas = Usuario::getAlertas();

    // Renderizar las vistas
    $router->render('auth/confirmar-cuenta', [
      'titulo' => 'Confirmar Cuenta',
      'alertas' => $alertas
    ]);
  }
}