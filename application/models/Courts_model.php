<?php
class Courts_Model extends CI_Model {

        public function __construct()
        {
                // Call the CI_Model constructor
                parent::__construct();
        }

        public function listCourts($conditions = '', $limit = 0, $order_by='')
        {
                $ret= false;  //variable que almacena el valor a retornar
                
                $this->db->select('*');   

                $this->db->from('court  as crt');
                
                if($order_by != '')
                {
                        //adicionando las condiciones establecidas al objeto db
                        $this->db->order_by($order_by);
                }
                else
                {
                    $this->db->order_by("inserted", "desc");
                }

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

        public function insert($establishment_id, $name, $capacity, $price_hour='', $cover='')
        {
                
                    //Si el email no esta registrado, se procede a registrarlo
                    $data = array(
                       'establishment_id' => $establishment_id,
                       'name' => $name,
                       'capacity' => $capacity,
                       'price_hour' => $price_hour,
                       'status' => '1'
                    );

                    $this->db->set('inserted', 'NOW()', FALSE);

                    $this->db->insert('establishment', $data); 

                    //check if client inserted
                    if($insert_id = $this->db->insert_id())
                    {
                        return $insert_id;
                    }
                    else
                    {
                        $this->msg = ___("No se pudo registrar establecimiento");
                        return false;
                    }

        }

        public function listServices($conditions = "estado = 1", $limit = 0)
        {
                $ret= false;  //variable que almacena el valor a retornar
                //REalizando el inner join para listar las categorias del sitio actual solamente
                $this->db->select('*');   

                $this->db->from('service as srv');
                $this->db->order_by("name", "asc");

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

        function getPictures($conditions='', $limit=0)
        {
                $ret= false;  //variable que almacena el valor a retornar
                //REalizando el inner join para listar las categorias del sitio actual solamente
                $this->db->select('*');   

                $this->db->from('picture as pict');
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

        public function getCompletes($conditions='', $limit=0)
        {
                $ret= false;  //variable que almacena el valor a retornar
                //REalizando el inner join para listar las categorias del sitio actual solamente
                $this->db->select('est.*, d.name as distric, p.name as province, r.name as region, (select group_concat(services_id) from gl_establishment_service as esrv2 where esrv2.establishment_id=est.establishment_id ) as services');  
                $this->db->from('establishment  as est');
                
                $this->db->join('distric as d', 'est.district_id=d.district_id');
                $this->db->join('province as p', 'd.province_id=p.province_id');
                $this->db->join('region as r', 'p.region_id=r.region_id'); 

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

}