<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';
// use namespace
use Restserver\Libraries\REST_Controller;

class User extends REST_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->model('user_model');
	}

	function user_get()
    {
        if(!$this->get('id'))
        {
            $this->response(NULL, 400);
        }
 
        $user = $this->user_model->getUser( $this->get('id') );
         
        if($user)
        {
            $this->response($user, 200); // 200 being the HTTP response code
        }
 
        else
        {
            $this->response(NULL, 404);
        }
    }
     
    function users_get()
    {
        $users = $this->user_model->listUsers( "status='1'", $this->get('limit') );
         
        if($users)
        {
            $this->response($users, 200);
        }
 
        else
        {
            $this->response(NULL, 404);
        }
    }

    function insert_put()
    {
    	echo "get: ".$this->input->get("email")."<br>";
    	echo "put: ".$this->input->get("email")."<br>";
    	$this->put("email")."<br>";
    	if (filter_var($this->input->get("email"), FILTER_VALIDATE_EMAIL))
		{
			//recivo la data enviada por metodo post;
			if($user_id = $this->user_model->insert($this->input->get("rol"), $this->input->get("district"), $this->input->get("email"), $this->input->get("user_name"), $this->input->get("password", TRUE), $this->input->get("first_name"), $this->input->get("last_name"), $this->input->get("birth_date"), $this->input->get("avatar")))
			{				
				$ret = array("estado" => "1", "msg" => ___("Registro satisfactorio"), "user_id" => $user_id);
			}
			else
			{
				$ret = array("estado" => "0", "msg" => "Error: " . $this->user_model->msg);
			}
		}
		else
		{
				$ret = array("estado" => "0", "msg" => ___("Correo electrónico inválido"));
		}
		echo json_encode($ret);
    }

    function __insert_put()
    {
    	if (filter_var($this->put("email"), FILTER_VALIDATE_EMAIL))
		{
			//recivo la data enviada por metodo post;
			if($user_id = $this->user_model->insert($this->put("rol"), $this->put("district"), $this->put("email"), $this->put("user_name"), $this->put("password", TRUE), $this->put("first_name"), $this->put("last_name"), $this->put("birth_date"), $this->put("avatar")))
			{				
				$ret = array("estado" => "1", "msg" => ___("Registro satisfactorio"), "user_id" => $user_id);
			}
			else
			{
				$ret = array("estado" => "0", "msg" => "Error: " . $this->user_model->msg);
			}
		}
		else
		{
				$ret = array("estado" => "0", "msg" => ___("Correo electrónico inválido"));
		}
		echo json_encode($ret);
    }
      
    function user_post()
    {
        $result = $this->user_model->update( $this->post('id'), array(
            'name' => $this->post('name'),
            'email' => $this->post('email')
        ));
         
        if($result === FALSE)
        {
            $this->response(array('status' => 'failed'));
        }
         
        else
        {
            $this->response(array('status' => 'success'));
        }
         
    }
 
    function user_delete()
    {
        $data = array('returned: '. $this->delete('id'));
        $this->response($data);
    }


	public function index()
	{
		$this->isUserLoggedIn();
	}

	private function heredaCookies()
	{

		if($this->user_logged_in())
		{
			$usuario = $this->usuario;
        	$this->productos_model->tipo_consulta = 'cart_stock'; //definiendo que campos se van a listar

			//aqui validamos la venta del cliente a la cual se le asiganaras los items
			if($idventa = $this->ventas_model->addVenta($usuario->idcliente))
			{

				//Obtengo los items agregados al carrito, envío TRUE indicando que busco productos de la cookie
				if($items = $this->getItemsCarrito(TRUE))
				{
					foreach ($items as $key => $itemP) {
						//Agregando a la BD este item
						$itemP = explode(',', $itemP['items'][0]);
						$cantidad = $itemP[0];
						$precio = $itemP[1];
						$itemId = str_pad($key,12,'0', STR_PAD_LEFT);
						$cantidad_previa = 0;

						if($producto = $this->productos_model->getDetail($itemId))
						{

								//Verifico cuántos productos se tienen ya en el carrito
								if($item = $this->ventas_model->getItems(" PDET.idventa = " . $idventa . " and
																			COD_PRODUCTO='" . $itemId . "'", 1))
				                {
				                    $cantidad_previa = $item[0]->CANTIDAD;
				                }

				                //Validación de stock contra la cantidad total requerida por este producto
				                if($producto[0]->STOCK  >= ($cantidad + $cantidad_previa) )
				                {
				                	if(($cantidad + $cantidad_previa)<=$this->config->item('max_prod_pedido'))
				                	{
										//validando el registro del item al carritos
										if($this->ventas_model->insertItem($idventa,$itemId,
																			$cantidad,$precio, ($cantidad * $precio), $producto[0]->title ))
										{
												//Ahora debo retornar un listado de los productos
												$ret = TRUE;
										}
										else
										{
												$ret = array("estado" => "0", "msg" => $this->ventas_model->msg);
										}
									}
									else
									{
										$ret = array("estado" => "0", "msg" => 'No puede adquirir más de ' .
														$this->config->item('max_prod_pedido') . ' unidades por pedido');
									}
								}
								else
								{
									$ret = array("estado" => "0", "msg" => 'No hay stock suficiente');
								}
						}
						else
						{
							$ret = array("estado" => "0", "msg" => 'No existe producto');
						}

					}
					delete_cookie("bj_prod_cesta");
				}
				//Además obtengo los productos vistos y los asigno al perfil para calcular el perfil de uso
                if($itemsVistos = $this->input->cookie("bj_prod_vistos",TRUE))
                {
                    if(count($itemsVistos) > 0)
                    {
                    	if($profile = $this->clientes_model->getUserProfile($usuario->idcliente))
			            {
			            	$defProfile = '0,0,0,0,0';

			                $profiles["CAR_DESKTOP"] = ($profile[0]->CAR_DESKTOP?$profile[0]->CAR_DESKTOP:$defProfile); 
			                $profiles["CAR_MOVIL"] = ($profile[0]->CAR_MOVIL?$profile[0]->CAR_MOVIL:$defProfile);  
			                $profiles["CAR_DESKTOP_ACU"] = ($profile[0]->CAR_DESKTOP_ACU?$profile[0]->CAR_DESKTOP_ACU:$defProfile);  
			                $profiles["CAR_MOVIL_ACU"] = ($profile[0]->CAR_MOVIL_ACU?$profile[0]->CAR_MOVIL_ACU:$defProfile);
							$dataPerfiles['MOVIL'] = explode(',', $profiles["CAR_MOVIL_ACU"]);
							$dataPerfiles['DESKTOP'] = explode(',', $profiles["CAR_DESKTOP_ACU"]);
							$conteo['MOVIL'] = ($profile[0]->CAR_MOVIL_VISTOS?$profile[0]->CAR_MOVIL_VISTOS:0);
							$conteo['DESKTOP'] = ($profile[0]->CAR_DESKTOP_VISTOS?$profile[0]->CAR_DESKTOP_VISTOS:0);

			                // $this->clientes_model->updatePreferenciasAcu($idUsuario, implode(',', $perfFinal), $tipoPerfilCat,$total);
			            }
                        foreach ($itemsVistos as $key => $itemProducto) {

                            //obtengo la informacion de este producto
                            $idProducto = str_pad($itemProducto, 12, "0", STR_PAD_LEFT);
                            $this->productos_model->tipo_consulta = 'full';
                            if($producto = $this->productos_model->getDetail($idProducto))
                            {
								$this->productos_model->addVista($idProducto, $producto[0]->COD_CATEGORIA, $usuario->idcliente,
																	$this->input->ip_address());   
								//Debo sumar su peso de productos vistos para su perfil
								if($tipoPerfilCat = $this->categorias_model->getTipoPerfil($producto[0]->COD_CATEGORIA))
								{
									//ACtualizo el perfil de preferencias
				                	$conteo[$tipoPerfilCat] =  intval($conteo[$tipoPerfilCat])+1;
				                	$total = $conteo[$tipoPerfilCat];

				                	//estableciendo los nuevos valores
				                	foreach ($dataPerfiles[$tipoPerfilCat] as $key => $value) {
				                		$nc='CAR' . ($key + 1);
				                		$dataPerfiles[$tipoPerfilCat][$key] = intval(($producto[0]->$nc + ($total - 1) * $value) / $total);
				                	}
									
						        }                             
                            }
                        }
			            $this->clientes_model->updatePreferenciasAcu($usuario->idcliente, implode(',', $dataPerfiles['DESKTOP']),
			            												'DESKTOP',$conteo['DESKTOP']);
			            $this->clientes_model->updatePreferenciasAcu($usuario->idcliente, implode(',', $dataPerfiles['MOVIL']),
			            												'MOVIL',$conteo['MOVIL']);

                    }
					delete_cookie("bj_prod_vistos");
                }
	        	//Tambien se carga el perfil guardado anteriormente
	        	$this->setProfileAcumulado();
			}
			else
			{
					$ret = array("estado" => "0", "msg" => $this->ventas_model->msg);
			}
		}
	}

	public function login_ajax()
	{		
			if($login = $this->clientes_model->login($this->input->post("username",TRUE), sha1($this->input->post("_password",TRUE))))
			{
				$this->session->set_userdata('bj_cliente', $login);
				$ret = array("estado"=>"1","msg"=>"Inicio de sesión correcto");
				//El usuario debe heredar los datos que ya tenía cargados en la cookie, luego esta cookie debe ser eliminada
				$this->heredaCookies();
			}
			else
			{
				$ret = array("estado"=>"0","msg"=>"Datos de inicio de sesión incorrectos");
			}
		echo json_encode($ret);
	}

	public function login_fb_ajax()
	{
		$fb = new Facebook\Facebook([
		  'app_id' => $this->config->item('fb_appId'), // Replace {app-id} with your app id
		  'app_secret' => $this->config->item('fb_secret'),
		  'default_graph_version' => 'v2.8',
		  ]);

		$helper = $fb->getJavaScriptHelper();
		//$accessToken = $this->input->post('token');

		try {
		   $accessToken = $helper->getAccessToken();

			//echo ((string) $accessToken);
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
		  // When Graph returns an error
		  $data['estado'] = '0';
		  $data['msg'] = 'Graph returned an error: ' . $e->getMessage();
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
		  // When validation fails or other local issues
		   $data['estado'] = '0';
		  $data['msg'] = 'Facebook SDK returned an error: ' . $e->getMessage();
		}

		if (! isset($accessToken)) {
		   $data['estado'] = '0';
		  $data['msg'] = 'No cookie set or no OAuth data could be obtained from cookie.';
		}else
		{
			
			$fb->setDefaultAccessToken($accessToken->getValue());
			$response = $fb->get('/me?fields=first_name,gender,last_name,name,email&scope=email');
  			$user_profile = $userNode = $response->getGraphUser();

  			if($login = $this->clientes_model->login_fb($user_profile["email"]))
  			{
				$this->session->set_userdata('bj_cliente', $login);
				$data = array("estado"=>"1","msg"=>"Inicio de sesión correcto");
				//El usuario debe heredar los datos que ya tenía cargados en la cookie, luego esta cookie debe ser eliminada
				$this->heredaCookies();
  			}
  			else
  			{
				$data['estado'] = '2';
				$data['msg'] = 'Usuario no existente';  	  				
  			}
		}

		echo json_encode($data);
		
	}

    public function login_fb()
    {
    	$usuario = $this->session->userdata('bj_cliente');
    	if(isset($usuario))
    	{
    		redirect('');
    	}
    	else
    	{
			$user = $this->facebook->getUser();
	        if ($user) {

	            try {
	                $user_profile = $this->facebook->api('/me?fields=email,first_name,gender,last_name,name');

	                //print_r($user_profile);

	                //Ahora verifico si debo registrarlo en la BD o solo leer su informacion
	                if($login = $this->clientes_model->login_fb($user_profile["first_name"],$user_profile["last_name"],$user_profile["email"],
	                								strtoupper(substr($user_profile["gender"],0,1)),$user_profile["id"]))
	                {
	                	$this->session->set_userdata('bj_cliente', $login);
	                }

	                redirect('');

	            } catch (FacebookApiException $e) {
	                $user = null;
	            }

	        } else {
				$this->load->view('header',$this->data);
				$this->load->view('login',$this->data);
				$this->load->view('footer',$this->data);
	        }
	    }
    }

    private function _bk_login_fb_ajax()
    {

    	$fb_id = $this->input->post('fb_id', TRUE);
    	$email = $this->input->post('fb_email', TRUE);

    	if($login = $this->clientes_model->login_fb($fb_id, $fb_email))
		{
			$this->session->set_userdata('bj_cliente', $login);
			$ret = array("estado"=>"1","msg"=>"Inicio de sesión correcto");
		}
		else
		{
			$ret = array("estado"=>"0","msg"=>"Usuario no registrados");
		}
		echo json_encode($ret);
    }

	public function logueado() {
	  $usuario = $this->session->userdata('bj_cliente');

		//Si ha iniciado sesión, se elimina de la BD, caso contrario de la cookie
		if(isset($usuario))
		{
			$ret['estado'] = '1';
		}
		else
		{
			$ret['estado'] = '0';
		}
		echo json_encode($ret);
	}


	// Logout from facebook
	public function logout() {
        /*// Logs off session from website
        $fb = new Facebook\Facebook([
		  'app_id' => $this->config->item('fb_appId'), // Replace {app-id} with your app id
		  'app_secret' => $this->config->item('fb_secret'),
		  'default_graph_version' => 'v2.8',
		  ]);

        $token = $facebook->getAccessToken();
		$url = 'https://www.facebook.com/logout.php?next=' . YOUR_SITE_URL .
		  '&access_token='.$token;
		session_destroy();
		header('Location: '.$url);

        $fb->destroySession();*/
        
		$this->session->unset_userdata('bj_cliente');
        redirect('');
	}

	public function detail($slug="")
	{

	}

	public function search($cadena = "")
	{
	}

	public function history()
	{
		$data["items"] = array();

        $filtroFecha = FALSE;
        $ordenPrecio = FALSE;
		//Ordenando por precio
		//las condiciones de la busqueda se obtienen a través de metodo GET
		$orderBy = $this->input->get("order"); //"price-asc" || "price-desc"
		//rango de precio
		$range_price = $this->input->get("price_range"); //num1,num2

		$data['precios'] = NULL;

		$data["f_order"] = $orderBy;
		$data["f_price_range"] = $this->data["f_price_range"] = $range_price;
		//rango de fechas
		$fecha1 = $this->input->get('date_i');
		$fecha2 = $this->input->get('date_f');

		$pag = $this->input->get("pag");

		$filtrosValidos = array('price'); //son los filtros validos para aplicar condicionales a productos
        $filtrosTrad = array('PROD.PRECIO_VENTA_SOLES'); //cada filtro tendrá un campo en la bd
        $orderValidos = array('asc','desc'); //palabras válidas a ser aplicadas en el filtro order by

        $where = ""; //condiciones adicionales
        $whereC = "";
        $orden = ""; //el ordenamiento adicional

        //Valores de paginacion
        if($pag < 0)
            $pag = 0;
        $pag++;
        $offset = $this->config->item('list_prod_limit') * ($pag - 1);
        $offset_siguiente = $this->config->item('list_prod_limit') * $pag;

        //ordenamiento
        if($orderBy)
        {           
            if(is_array($orderBy))
                $orderBy = $orderBy[0];
            $orderBy = explode('-', $orderBy);
            if(count($orderBy) > 1)
            {
                if(in_array($orderBy[0],$filtrosValidos) and in_array($orderBy[1], $orderValidos))
                {
            		$ordenPrecio = TRUE;
                    $orden .= str_replace($filtrosValidos, $filtrosTrad,$orderBy[0]) . " " . $orderBy[1];
                }
            }        
        }
        else
        {
            $orderBy[0] = 'profile';
        }
        //condicion precio
        if($range_price)
        {
            if(is_array($range_price))
                $range_price = $range_price[0];
            $rango = explode(',', $range_price);
            if(count($rango)>1)
            {
                if($rango[0] == 0) $rango[0] = 0.0001;
                $whereC[] = '('. $filtrosTrad[0] .' between ' .  floatval($rango[0]) .' and ' . floatval($rango[1]) . ')';
                $where[] = '('. $filtrosTrad[0] .' between ' .  floatval($rango[0]) .' and ' . floatval($rango[1]) . ')';
            }
        }
        
        //condicion fecha_registro
        if($fecha1!='' and $fecha2!='')
        {
        	if(validateDate($fecha1) and validateDate($fecha2))
        	{
        		if($fecha2 >= $fecha1)
        		{
        			$filtroFecha = TRUE;
        			$fechat1 = strtotime($fecha1);
					$fechat2 = strtotime($fecha2);

        			//procedo a aplicar filtro de fecha
        			$where[] = "(BP.inserted between '$fecha1' and '$fecha2')";
        		}
        		else
        		{
        			$msg_src = 'La fecha final debe ser mayor o igual a la fecha inicial';
        		}
        	}
        	else
        	{
        		$msg_src = 'Ingrese fechas válidas';
        	}
        }



		//Si ha iniciado sesión, se lee de la BD, caso contrario, se lee de una cookie
		if($this->user_logged_in())
		{
			$usuario = $this->usuario;
			if($dataItems = $this->productos_model->getUserVistas($usuario->idcliente,0,(is_array($where)?implode(' and ',$where):''), $orden))
			{
					$data["items"] = $dataItems;
					//recorro los items obtenidos para obtneer los id de productos y los precios respectivos
					if($totalItems = $this->productos_model->getUserVistasMin($usuario->idcliente,0,(is_array($where)?implode(' and ',$where):'')))
					{
						foreach ($totalItems as $keyIP => $valueIP) {
							$itemsParaPrecio[] = "'".$valueIP->COD_PRODUCTO."'";
						}
						//Evaluando rango de precios
						if($precios = $this->productos_model->getIntervalPrecios("COD_PRODUCTO IN (" . implode(',', $itemsParaPrecio) . ")"))
						{
							//asignando los precios a la variable utilizable desde le view
							$data["precios"] = array( $precios[0]->minimo, $precios[0]->maximo);
						}
					}
			}
			else
			{
				$data["msg"] = $this->msg;
			}
		}
		else
		{
			//se busca de la cookie
			if($dataCookie = $this->input->cookie("bj_prod_vistos",TRUE))
			{
					if($ordenPrecio)
					{
						foreach ($dataCookie as $key => $value) {
								$fechaProd = strtotime(date('Y-m-d',$key));

								if($filtroFecha)
								{
									if($fechaProd >= $fechat1 and $fechaProd<=$fechat2)
									{
										$dataCookies[] = $value;
									}
								}
								else
								{
									$dataCookies[] = $value;
								}
								$idsPrecios[] = "'".$value."'";
						}


						$whereC[] = "(COD_PRODUCTO in (". implode(',', $dataCookies) .") )";
						//En este caso almaceno los id de producto para hacer una consulta con el order by precio desc o asc
						if($prods = $this->productos_model->getProducts(implode(' and ', $whereC), 0, 0, $orden))
						{
							foreach ($prods as $keyP => $valueP) {
								$fechaPC =  date('Y-m-d H:i:s', array_search($valueP->id_producto,$dataCookie));
								$dataItems[] = array("detalle" => $valueP, "inserted" => $fechaPC);
							}
						}
						

						$data['items'] = $dataItems;

					}
					else
					{
						$codigosP = array();
						foreach ($dataCookie as $key => $value) {
								$fechaProd = strtotime(date('Y-m-d',$key));

								if($filtroFecha)
								{
									if($fechaProd >= $fechat1 and $fechaProd<=$fechat2)
									{
										if(!in_array($value, $codigosP))
										{
											$codigosP[] = $value;
											$dataItems[] = array("detalle" => $this->productos_model->getDetail($value)[0], "inserted" => date('Y-m-d H:i:s',$key));
										}
									}
								}
								else
								{
									if(!in_array($value, $codigosP))
									{
										$codigosP[] = $value;
										$dataItems[] = array("detalle" => $this->productos_model->getDetail($value)[0], "inserted" => date('Y-m-d H:i:s',$key));
									}
								}
								$idsPrecios[] = "'".$value."'";
						}
						if(isset($dataItems) and is_array($dataItems))
						{
							$data["items"] = array_reverse($dataItems);
						}
					}
					if($precios = $this->productos_model->getIntervalPrecios("COD_PRODUCTO IN (" . implode(',', $idsPrecios) . ")"))
					{
						//asignando los precios a la variable utilizable desde le view
						$data["precios"] = array( $precios[0]->minimo, $precios[0]->maximo);
					}
				
			}
			else
			{
				$data["msg"] = "no se encontró elementos en la cookie";
			}			
		}

		$this->load->view('header',$this->data);
		
		$this->load->view('cliente/history',$data);

		$this->load->view('footer',$this->data);
	}

	public function recent()
	{

		$data["items"] = array();

        $filtroFecha = TRUE;
        
		//rango de fechas
		$hoy = date('Y-m-d');
		$fecha2 = date( "Y-m-d", strtotime("+1 day", strtotime($hoy)));
		$fecha1 = date( "Y-m-d", strtotime("-". $this->config->item('recent_dias') ." day", strtotime($hoy)));

        $where = ""; //condiciones adicionales
        $whereC = "";
        $orden = ""; //el ordenamiento adicional
        
        //condicion fecha_registro
        if($fecha1!='' and $fecha2!='')
        {
        	if(validateDate($fecha1) and validateDate($fecha2))
        	{
        		if($fecha2 >= $fecha1)
        		{
        			$filtroFecha = TRUE;
        			$fechat1 = strtotime($fecha1);
					$fechat2 = strtotime($fecha2);

        			//procedo a aplicar filtro de fecha
        			$where[] = "(BP.inserted between '$fecha1' and '$fecha2')";
        		}
        		else
        		{
        			$msg_src = 'La fecha final debe ser mayor o igual a la fecha inicial';
        		}
        	}
        	else
        	{
        		$msg_src = 'Ingrese fechas válidas';
        	}
        }



		//Si ha iniciado sesión, se lee de la BD, caso contrario, se lee de una cookie
		if($this->user_logged_in())
		{
			$usuario = $this->usuario;
			if($dataItems = $this->productos_model->getUserVistas($usuario->idcliente,0,(is_array($where)?implode(' and ',$where):''), $orden))
			{
					$data["items"] = $dataItems;
			}
			else
			{
				$data["msg"] = $this->msg;
			}
		}
		else
		{
			//se busca de la cookie
			if($dataCookie = $this->input->cookie("bj_prod_vistos",TRUE))
			{
				$codigosP = array();
				foreach ($dataCookie as $key => $value) {
						$fechaProd = strtotime(date('Y-m-d',$key));

						if($filtroFecha)
						{
							if($fechaProd >= $fechat1 and $fechaProd<=$fechat2)
							{
								if(!in_array($value, $codigosP))
								{
									$codigosP[] = $value;
									$dataItems[] = array("detalle" => $this->productos_model->getDetail($value)[0], "inserted" => date('Y-m-d H:i:s',$key));
								}
							}
						}
						else
						{
							if(!in_array($value, $codigosP))
							{
								$codigosP[] = $value;
								$dataItems[] = array("detalle" => $this->productos_model->getDetail($value)[0], "inserted" => date('Y-m-d H:i:s',$key));
							}
						}
				}
				if(isset($dataItems) and is_array($dataItems))
				{
					$data["items"] = array_reverse($dataItems);
				}
				
			}
			else
			{
				$data["msg"] = "no se encontró elementos en la cookie";
			}			
		}

		$this->load->view('header',$this->data);
		
		$this->load->view('cliente/resent',$data);

		$this->load->view('footer',$this->data);
	}

	public function getDirecciones_ajax()
	{

		$usuario = $this->session->userdata('bj_cliente');

		//Si ha iniciado sesión, se lee de la BD, caso contrario, se lee de una cookie
		if(isset($usuario))
		{
			if($dataItems = $this->clientes_model->getDirecciones($usuario->idcliente))
			{
				$ret = array('estado'=>1, 'direcciones'=> $dataItems);			
			}
			else
			{
				$ret = array('estado'=>0, 'msg'=> $this->msg);
			}
		}
		echo json_encode($ret);
	}

	public function getDireccion_ajax()
	{

		$usuario = $this->session->userdata('bj_cliente');
		$dir_id = $this->input->post('dir_id');

		//Si ha iniciado sesión, se lee de la BD, caso contrario, se lee de una cookie
		if(isset($usuario) and intval($dir_id)>0)
		{
			if($dataItems = $this->clientes_model->getDireccionDetalle($usuario->idcliente,$dir_id))
			{
				$ret = array('estado'=>1, 'direccion'=> $dataItems[0]);		
			}
			else
			{
				$ret = array('estado'=>0, 'msg'=> $this->msg);
			}
		}
		else
		{
			$ret = array('estado'=>0, 'msg'=> 'Usuario o dirección incorrectos');
		}
		echo json_encode($ret);
	}

	public function getRazones_sociales_ajax()
	{
		//Si ha iniciado sesión
		if($this->user_logged_in())
		{
			$usuario = $this->usuario;
			if($dataItems = $this->clientes_model->getFactsEmpresa($usuario->idcliente))
			{
				$ret = array('estado'=>1, 'rs'=> $dataItems);			
			}
			else
			{
				$ret = array('estado'=>0, 'msg'=> $this->msg);
			}
		}
		echo json_encode($ret);
	}

	public function getRazon_social_ajax()
	{
		$rs_id = $this->input->post('rs_id');

		//Si ha iniciado sesión
		if($this->user_logged_in() and intval($rs_id)>0)
		{
			$usuario = $this->usuario;
			if($dataItems = $this->clientes_model->getFactEmpresaDetalle($usuario->idcliente,$rs_id))
			{
				$ret = array('estado'=>1, 'rs'=> $dataItems[0]);		
			}
			else
			{
				$ret = array('estado'=>0, 'msg'=> $this->msg);
			}
		}
		else
		{
			$ret = array('estado'=>0, 'msg'=> 'Usuario o razón social incorrectos');
		}
		echo json_encode($ret);
	}

	public function addDireccion_ajax()
	{
		$usuario = $this->session->userdata('bj_cliente');

		//Si ha iniciado sesión, se lee de la BD, caso contrario, se lee de una cookie
		if(isset($usuario))
		{
			$ALIAS = $this->input->post('alias');
			$COD_DEPARTAMENTO = $this->input->post('COD_DEPARTAMENTO');
			$COD_PROVINCIA = $this->input->post('COD_PROVINCIA');
			$COD_DISTRITO = $this->input->post('COD_DISTRITO');
			$DIRECCION = $this->input->post('DIRECCION');
			$REFERENCIA = $this->input->post('REFERENCIA');

			if($direccion = $this->clientes_model->addDireccion($usuario->idcliente, $ALIAS, $COD_DEPARTAMENTO, $COD_PROVINCIA,
																$COD_DISTRITO, $DIRECCION, $REFERENCIA))
			{
					$ret = array('estado'=>1, 'msg'=> 'Direccion agregada satisfactoriamente', 'id'=>$direccion->idDireccion);
			}
			else
			{
					$ret = array('estado'=>0, 'msg'=> $this->clientes_model->msg);
			}
			echo json_encode($ret);
		}
	}

	//Agregar razon social por ajax
	public function addRs_ajax()
	{
		$usuario = $this->session->userdata('bj_cliente');

		//Si ha iniciado sesión, se lee de la BD, caso contrario, se lee de una cookie
		if(isset($usuario))
		{
			$RAZON_SOCIAL = $this->input->post('RS');
			$COD_DEPARTAMENTO = $this->input->post('COD_DEPARTAMENTO');
			$COD_PROVINCIA = $this->input->post('COD_PROVINCIA');
			$COD_DISTRITO = $this->input->post('COD_DISTRITO');
			$DIRECCION = $this->input->post('DIRECCION');
			$RUC = $this->input->post('RUC');
			$REFERENCIA = $this->input->post('REFERENCIA');

			if($rs = $this->clientes_model->addFactEmpresa($usuario->idcliente, $RUC, $RAZON_SOCIAL, $COD_DEPARTAMENTO, $COD_PROVINCIA,
																$COD_DISTRITO, $DIRECCION, $REFERENCIA))
			{
					$ret = array('estado'=>1, 'msg'=> 'Razón social agregada satisfactoriamente', 'id'=>$rs->idFacturacion);
			}
			else
			{
					$ret = array('estado'=>0, 'msg'=> $this->clientes_model->msg);
			}
			echo json_encode($ret);
		}
	}

	public function addProductSeen_ajax()
	{
		$COD_PRODUCTO = $this->input->post('idProducto');
		//REGISTRO ESTE PRODUCTO EN SU HISTORIAL
		$usuario = $this->session->userdata('bj_cliente');

		if($valueProducto = $this->productos_model->getDetail($COD_PRODUCTO))
		{
			//Si ha iniciado sesión, se lee de la BD, caso contrario, se lee de una cookie
			$idUsuario = 0;
			if(isset($usuario))
			{
					$idUsuario = $usuario->idcliente;
			}
			else
			{						
					$this->input->set_cookie("bj_prod_vistos[". time() ."]", $valueProducto[0]->id_producto, time()+15*24*3600);
					//FIN DE REGISTRO DE VISTA EN HISTORIAL DEL PC
			}
			//Cuando se ha ingresado a este producto, se registra una vista
			$this->productos_model->addVista($valueProducto[0]->id_producto, $valueProducto[0]->COD_CATEGORIA, $idUsuario, $this->input->ip_address());
			$ret = array("estado"=>1, "msg"=>"Vista a producto registrada");
		}
		else
		{
			$ret = array("estado"=>0, "msg"=>"Error registrando vista de producto");
		}
		echo json_encode($ret);
	}

    public function complete_profile()
    {
		$this->user = $usuario = $this->session->userdata('bj_cliente');

		//Si ha iniciado sesión, se elimina de la BD, caso contrario de la cookie
		if(isset($usuario))
		{
            if($data['userProfile'] = $this->clientes_model->getUserProfile($usuario->idcliente))
            {
            	//si se ha posteado, actualizamos y redirigimos a la pagina anterior
            	if($this->input->server('REQUEST_METHOD') == 'POST')
            	{
            		//verificando si se guardó el perfil
            		if($this->guarda_profile())
            		{
            			//se redirecciona a la pagina anterior
            			if(!$url = $this->input->cookie("url_ant",TRUE))
            				$url = 'cart/saleconditions';
                		redirect($url);
            		}
            		else
            		{
            			echo $this->clientes_model->msg;

            		}
            	}
            	else
            	{
		            	$data['userProfile'] = $data['userProfile'][0]; //Asignando la primera fila

		            	//CARGANDO UBICACIONES GEOGRAFICAS
		            	$this->load->model('locaciones_model');
		            	$data['departamentos'] = $this->locaciones_model->getLocations('departamento');
		            	$data['provincias'] = $this->locaciones_model->getLocations('provincia',
		            					($data['userProfile']->COD_DEPARTAMENTO!=''?"COD_DEPARTAMENTO='".$data['userProfile']->COD_DEPARTAMENTO."'":
		            					"COD_DEPARTAMENTO='".$data['departamentos'][0]->COD_LOC."'"));
		            	$data['distritos'] = $this->locaciones_model->getLocations('distrito',
		            					($data['userProfile']->COD_DEPARTAMENTO!=''?"COD_DEPARTAMENTO='".$data['userProfile']->COD_DEPARTAMENTO."' and
		            					COD_PROVINCIA='".$data['userProfile']->COD_PROVINCIA."'":
		            					"COD_DEPARTAMENTO='".$data['departamentos'][0]->COD_LOC."' and COD_PROVINCIA='".$data['provincias'][0]->COD_LOC."'"));

		                $this->load->view('header',$this->data);
		                $data['url_next'] = 'cart/saleconditions';
		                $this->load->view('cliente/complete_profile', $data);
		                $this->load->view('footer',$this->data); 
		        }                  
            }
        }
        else
        {
                $this->load->view('header',$this->data);
                $this->load->view('cliente/no_logueado');
                $this->load->view('footer',$this->data);         	
        }
    }

    private function guarda_profile()
    {
    	$idcliente = $this->user->idcliente;
    	$DNI = $this->input->post('dni');
    	$nombre = $this->input->post('nombre');
    	$apellido = $this->input->post('apellido');
    	$genero = $this->input->post('genero');
    	$CELULAR = $this->input->post('celular');
    	$COD_DEPARTAMENTO = $this->input->post('departamento');
    	$COD_PROVINCIA = $this->input->post('provincia');
    	$COD_DISTRITO = $this->input->post('distrito');
    	$DIRECCION = $this->input->post('direccion');
    	$REFERENCIA = $this->input->post('referencia');

    	// Variables de validacion de datos
    	$val_dni = "/[0-9]{8}/";
    	$val_nom = "/^[A-Za-zá-úÁ-Ú\s\, ]{2,}$/";
    	$val_cel = "/[0-9]/";
		$val_dep = "/[0-9]{2}/";
		$val_prov = "/[0-9]{3}/";
		$val_dist = "/[0-9]{5}/";

        if(preg_match($val_dni, $DNI) and preg_match($val_dep, $COD_DEPARTAMENTO) and preg_match($val_prov, $COD_PROVINCIA) and
        	preg_match($val_dist, $COD_DISTRITO) and preg_match($val_cel, $CELULAR) AND preg_match($val_nom, $nombre))
        {

	    	//Si aun no tiene direcciones, se registra esta primera dirección como 'Mi Casa'
			if(!$this->clientes_model->getDirecciones($idcliente))
			{
	    		$this->clientes_model->addDireccion($idcliente, $this->config->item('vnt_primera_direccion'), $COD_DEPARTAMENTO, $COD_PROVINCIA,
	    											$COD_DISTRITO, $DIRECCION, $REFERENCIA);
			}

	    	return $this->clientes_model->completeProfile($idcliente, $DNI, $nombre, $apellido, $genero, $CELULAR, $COD_DEPARTAMENTO, $COD_PROVINCIA,
    													$COD_DISTRITO, $DIRECCION, $REFERENCIA);
	    }
	    else
	    {
	    	$this->clientes_model->msg = 'Datos incorrectos';
	    }
    }

    public function guarda_user_profile_ajax()
    {
    	if($this->guarda_profile())
    	{
    		$ret = array('estado'=>'1');
    	}
    	else
    	{
    		$ret = array('estado'=>'0', 'msg'=>$this->msg);
    	}
    	echo json_encode($ret);
    }

	//registrando las preferencias de perfil del usuario (en base a 5 categorias: diseñador, estudiante, etc)
	public function setProfile_ajax()
	{
		if($profiles = $this->input->post('prof'))
		{
			$iniprofiles = $profiles;
			$tipo_prof = $this->input->post('type');
			$usuario = $this->session->userdata('bj_cliente');
			$profArr = explode(",", $profiles);
			if(count($profArr) > 0)
			{
				unset($profiles);
				foreach ($profArr as $key => $value) {
					if($value>$this->config->item('max_rombo') or $value < $this->config->item('min_rombo'))
                    	$value = $this->config->item('min_rombo');

                    $profiles[] = intval($value);
				}
			}

			//Si ha iniciado sesión, se registra en la BD, caso contrario, se registra en una cookie
			if(isset($usuario))
			{
				if($this->clientes_model->setProfile($usuario->idcliente, implode(',', $profiles), $tipo_prof))
				{
					$ret = array("estado" => "1", "msg" => "Perfil guardado correctamente");	
				}
				else
				{
					$ret = array("estado" => "0", "msg" => $clientes_model->msg);
				}
			}
			else
			{
				$profArr = $profiles;
				if(count($profArr) > 0)
				{
					unset($profiles);
					foreach ($profArr as $key => $value) {
						if($value>$this->config->item('max_rombo') or $value < $this->config->item('min_rombo'))
                        	$value = $this->config->item('min_rombo');

                        $profiles[] = intval($value);
					}
				}


				$this->input->set_cookie("bj_profile[CAR_" . $tipo_prof . "]", implode(',', $profiles), time()+15*24*3600);
				$ret = array("estado" => "1", "msg" => "Perfil guardado correctamente");	
				// $ret = array("estado" => "2", "msg" => "Perfil guardado correctamente CAR_" . $tipo_prof . " " . $iniprofiles);		
			}
				$this->session->set_userdata("bj_profile_CAR_" . $tipo_prof, implode(',', $profiles));
		}

		echo json_encode($ret);
	}

	public function getDireccionesEnvio()
	{
		//
	}

	public function getConditionProfile_ajax()
	{
		echo json_encode($this->getConditionProfile());
	}

}