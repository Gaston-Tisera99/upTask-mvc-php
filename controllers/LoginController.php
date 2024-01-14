<?php

namespace Controllers;

use Classes\Email;
use MVC\Router;
use Model\Usuario;

class LoginController {
    public static function login(Router $router){

        $alertas = [];
        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            $usuario = new Usuario($_POST);

            $alertas = $usuario->validarLogin();

            if(empty($alertas)){
                //verificar que el usuario exista
                $usuario = Usuario::where('email', $usuario->email);
                if(!$usuario || !$usuario->confirmado){
                    Usuario::setAlerta('error', 'El usuario no existe o no esta confirmado');
                }else{
                    //El usuario existe
                    if(password_verify($_POST['password'], $usuario->password)){
                        //iniciar sesion
                        session_start();
                        $_SESSION['id'] = $usuario->id;
                        $_SESSION['nombre'] = $usuario->nombre;
                        $_SESSION['email'] = $usuario->email;
                        $_SESSION['login'] = true;

                        //redireccionar
                        header('Location: /dashboard');

                    }else{
                        Usuario::setAlerta('error', 'El Password es incorrecto');
                    }
                }
                
            }
            //debuguear($auth);
        }
        $alertas = Usuario::getAlertas();
        //render a la vista
        $router->render('auth/login', [
            'titulo' => 'Iniciar Sesion',
            'alertas' => $alertas
        ]);
    }

    public static function logout(){
        echo "desde logout";

        
    }

    public static function crear(Router $router){

        $usuario = new Usuario;

        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            $usuario->sincronizar($_POST);
            $alertas = $usuario->validarCuentasNuevas();

            if(empty($alertas)){
                $existeUsuario = Usuario::where('email', $usuario->email);

                if($existeUsuario){
                    Usuario::setAlerta('error', 'El usuario ya esta registrado');
                    $alertas = Usuario::getAlertas();
                }else{
                    //hashear password
                    $usuario->hashPassword();
                    
                    //generar el token
                    $usuario->crearToken();

                    //Eliminar password2
                    unset($usuario->password2);

                    $resultado = $usuario->guardar();

                    //enviar email
                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                    $email->enviarConfirmacion();

                    if($resultado){
                        header('Location: /mensaje');
                    }
                }
            }
        }

        $router->render('auth/crear', [
            'titulo' => 'Crea tu cuenta en UpTask',
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);
    }

    public static function olvide(Router $router){
       
        $alertas = [];
        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            $usuario = new Usuario($_POST);
            $alertas = $usuario->validarEmail();
            if(empty($alertas)){
                //buscar el usuario

                $usuario = Usuario::where('email', $usuario->email);

                if($usuario &&  $usuario->confirmado === "1")
                {
                    unset($usuario->password2);
                    //Generar un nuevo token
                    $usuario->crearToken($usuario->token);
                    //actualizar el usuario
                    $usuario->guardar();
                    //enviar el email
                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                    $email->enviarInstrucciones();
                    //imprimir la alerta
                    Usuario::setAlerta('exito', 'Hemos enviado las instrucciones a tu emial');
                    //debuguear($usuario);
                }else{
                    Usuario::setAlerta('error', 'El usuario no existe o no esta confirmado');
                }
            }
        }
        $alertas = Usuario::getAlertas();

        $router->render('auth/olvide', [
            'titulo' => 'Olvide mi Password',
            'alertas' => $alertas
        ]);
    }

    public static function reestablecer(Router $router){
        $token = $_GET['token'];
        $mostrar = true;
        //si intentan reestablecer sin un token lo regresamos a la pagina principal
        if(!$token) header('Location: /');

        //identificar el usuario con este token
        $usuario = Usuario::where('token', $token);
        
        if(empty($usuario)){
            Usuario::setAlerta('error', 'Token no valido');
            $mostrar = false;
        }

        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            //Añadir el nuevo password
            $usuario->sincronizar($_POST);

            //VALIDAR PASSWORD
             $alertas = $usuario->validarPassword();
            
             if(empty($alertas)){
                //hashear el nuevo password
                $usuario->hashPassword();
                 
                //Eliminar el token
                $usuario->token = null;
                //guardar el usuario en la bd
                $resultado = $usuario->guardar();

                //redireccionar
                if($resultado){
                    header('Location: /');
                }

                debuguear($usuario);   
             }
        }

        $alertas = Usuario::getAlertas();

        $router->render('auth/reestablecer', [
            'titulo' => 'Reestablecer Password',
            'alertas' => $alertas,
            'mostrar' => $mostrar
        ]);
    }

    public static function confirmar(Router $router){
    
        $token = s($_GET['token']);

        if(!$token) header('Location: /');

        //encontrar al usuario con este token
        $usuario = Usuario::where('token', $token);

        if(empty($usuario)){
            //no se encontro usuario con ese token
            Usuario::setAlerta('error', 'Token no valido');
        }else{
            //confirmar la cuenta
            $usuario->confirmado = 1;
            $usuario->token = null;
            unset($usuario->password2);

            //guardar en la base de datos
            $usuario->guardar();
            
            Usuario::setAlerta('exito', 'Cuenta comprobada exitosamente');
            
        }

        $alertas = Usuario::getAlertas();

        $router->render('auth/confirmar', [
            'titulo' => 'Confirma tu cuenta UpTask',
            'alertas' => $alertas
        ]);

    }   

    public static function mensaje(Router $router){

        $router->render('auth/mensaje', [
            'titulo' => 'Cuenta creada exitosamente'
        ]);

    }
}