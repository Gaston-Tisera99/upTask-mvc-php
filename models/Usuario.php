<?php

namespace Model;

class Usuario extends ActiveRecord{
    protected static $tabla = 'usuarios';
    protected static $columnasDB = ['id', 'nombre', 'email', 'password', 'token', 'confirmado'];

    public function __construct($args = []){
        $this->id = $args['id'] ?? null;
        $this->nombre = $args['nombre'] ?? '';    
        $this->email = $args['email'] ?? '';    
        $this->password = $args['password'] ?? '';    
        $this->password2 = $args['password2'] ?? null;  
        $this->token = $args['token'] ?? '';    
        $this->confirmado = $args['confirmado'] ?? 0;    
    }

    public function validarCuentasNuevas(){
        if(!$this->nombre){
            self::$alertas['error'][] = 'El nombre del Usuario es obligatorio';
        }

        if(!$this->email){
            self::$alertas['error'][] = 'El email del Usuario es obligatorio';
        }

        if(!$this->password){
            self::$alertas['error'][] = 'El password del Usuario no puede ser vacio';
        }

        if(strlen($this->password) < 6){
            self::$alertas['error'][] = 'El password del Usuario no contener menos de 6 caracteres';
        }

        if($this->password !== $this->password2){
            self::$alertas['error'][] = 'Los password son diferentes';
        }

        return self::$alertas;
    }

    public function hashPassword(){
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
    }

    //generar un token

    public function crearToken(){
        $this->token = uniqid();
    }
}