<?php
class MY_Controller extends CI_Controller {
 
    protected $data = array();
    public $msg;
    protected $usuario = NULL;
 
    public function __construct() {
        parent::__construct();
    }

    public function isUserLoggedIn()
    {
        $usuario = $this->session->userdata('gl_usuario');

        //Si ha iniciado sesión, se procede a mostrar la vista de informacion de venta
        if(isset($usuario))
        {
            $this->usuario = $usuario;
            return true;
        }
        else
        {
            return false;
        }
    }
}
?>