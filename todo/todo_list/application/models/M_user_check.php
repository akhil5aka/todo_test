<?php
defined('BASEPATH') or exit('No direct script access allowed');
//Implimented by Akhil

//Akhil's code starts here 

class M_user_check extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function log_check($usr,$pass)

    {

        $query= $this->db->query("select id from tb_users where usr_name='$usr' and password='$pass'");
        $re=$query->result();;
        if($re[0]->id)
        {
            return 1;
        }
        else
        {
            return 0;
        }
         
}
 
}
