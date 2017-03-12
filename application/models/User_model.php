<?php
class User_Model extends CI_Model {

        public function __construct()
        {
                // Call the CI_Model constructor
                parent::__construct();
                $this->load->database();
        }

        public function getUser($id)
        {
            return $this->listUsers(" user_id='$id'",1);
        }

        public function listUsers($conditions = '',$limit = 0)
        {
                $ret= false;  //variable que almacena el valor a retornar
                //REalizando el inner join para listar las categorias del sitio actual solamente
                $this->db->select('user_id, rol_id,district_id,email,user_name,first_name,last_name,birth_date,avatar, status');   

                $this->db->from('user  as u');
                $this->db->order_by("inserted", "desc");

                if($conditions != '')
                {
                        //adicionando las condiciones establecidas al objeto db
                        $this->db->where($conditions);
                }

                if($limit>0)
                        $this->db->limit($limit);

                $query = $this->db->get();

                if($query->num_rows() > 0)
                        $ret = $query->result();

                //liberando los datos de la consulta SQL
                $query->free_result();
                return $ret;
        }

        public function insert($rol_id, $district_id, $email='', $user_name='', $password='', $first_name='', $last_name='', $birth_date='', $avatar='', $oauth_id='')
        {
                if($cliente = $this->listUsers(" email = '" . $email . "'"))
                {
                    $this->msg = ___('Email ya està en uso');
                    return false;
                }
                else
                {
                    //Si el email no esta registrado, se procede a registrarlo
                    $data = array(
                       'rol_id' => $rol_id,
                       'district_id' => $district_id,
                       'email' => $email,
                       'user_name' => $user_name,
                       'password' => $password,
                       'first_name' => $first_name,
                       'last_name' => $last_name,
                       'birth_date' => $birth_date,
                       'avatar' => $avatar,
                       'status' => '1'
                    );


                    if($oauth_id == "")
                        $this->db->set('oauth_id', 'NULL', FALSE);
                    else
                        $data["oauth_id"] = $oauth_id;

                    $this->db->set('inserted', 'NOW()', FALSE);

                    $this->db->insert('user', $data); 

                    //check if client inserted
                    if($insert_id = $this->db->insert_id())
                    {
                        return $insert_id;
                    }
                    else
                    {
                        $this->msg = ___("No se pudo registrar usuario");
                        return false;
                    }
                }
        }

        public function getRoles($conditions = "estado = 1", $limit = 0)
        {
                $ret= false;  //variable que almacena el valor a retornar
                //REalizando el inner join para listar las categorias del sitio actual solamente
                $this->db->select('*');   

                $this->db->from('role as r');
                $this->db->order_by("title", "asc");

                if($conditions != '')
                {
                        //adicionando las condiciones establecidas al objeto db
                        $this->db->where($conditions);
                }

                if($limit>0)
                        $this->db->limit($limit);

                $query = $this->db->get();

                if($query->num_rows() > 0)
                        $ret = $query->result();

                //liberando los datos de la consulta SQL
                $query->free_result();
                return $ret;
        }

        function login($username, $password)
        {
            //el password ya viene cifrado
            if($user = $this->listUsers(" SHA1(correo) = '" . sha1($username) . "' AND SHA1(password)='" . sha1($password) . "'"))
            {
                return $user[0];
            }
            else
            {
                return false;
            }
        }

       //la sesion ya ha sido validada por facebook, solo resta leer su info o registrarlo en caso no exista en la BD 
        function login_fb($email)
        {
                //Este cliente ya existe en la BD, se procede a retornar su id
                if($user = $this->listUsers(" email = '" . $email . "'"))
                {
                    return $user[0];
                }
                else
                {
                    return FALSE;
                }
        }

        public function getUserProfile($user_id)
        {
                $ret= false;  //variable que almacena el valor a retornar
                //REalizando el inner join para listar las categorias del sitio actual solamente
                $this->db->select("*");   

                $this->db->from('user  as u');
                $this->db->where("user_id='". $user_id ."'");

                $this->db->limit(1);

                $query = $this->db->get();

                if($query->num_rows() > 0)
                        $ret = $query->result();

                //liberando los datos de la consulta SQL
                $query->free_result();
                return $ret;
        }

        public function getUserProfileDetail($idcliente)
        {
                $ret= false;  //variable que almacena el valor a retornar
                //REalizando el inner join para listar las categorias del sitio actual solamente
                $this->db->select('u.*, d.name as distric, p.name as province, r.name as region');  
                $this->db->from('user  as u');
                
                $this->db->join('distric as d', 'u.district_id=d.district_id');
                $this->db->join('province as p', 'd.province_id=p.province_id');
                $this->db->join('region as r', 'p.region_id=r.region_id');  

                $this->db->where("user_id='". $idcliente ."'");

                $this->db->limit(1);

                $query = $this->db->get();

                if($query->num_rows() > 0)
                        $ret = $query->result();

                //liberando los datos de la consulta SQL
                $query->free_result();
                return $ret;
        }

        public function completeProfile($user_id, $rol_id, $district_id, $email='', $user_name='', $password='', $first_name='', $last_name='', $birth_date='', $avatar='')
        {
            //En este caso es un update
            $data = array(
                       'rol_id' => $rol_id,
                       'district_id' => $district_id,
                       'email' => $email,
                       'user_name' => $username,
                       'password' => $password,
                       'first_name' => $first_name,
                       'last_name' => $last_name,
                       'birth_date' => $birth_date,
                       'avatar' => $avatar
                    );

            $this->db->set('updated','NOW()',FALSE);

            $this->db->where('user_id', $user_id);
            $this->db->update('user', $data); 

            //check if upodate for add updated column in database
            if($this->db->affected_rows() > 0)
            {                    
                    return true;
            }
            else
            {
                $this->msg = ___('No se pudo actualizar la información de usuario');
                return false;
            }


        }

}