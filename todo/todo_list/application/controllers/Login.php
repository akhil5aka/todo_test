<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller {

	
	public function login()
	{
        $this->load->model("M_user_check");
       
        $pass=$_POST['password'];
        $usr=$_POST['username'];
        $hashed_password = sha1($pass);
       $usr_id= $this->M_user_check->log_check($usr,$hashed_password);

      if($usr_id==1)
      {
		// $this->load->view('login');
        $this->load->view('todo');
      }
		
	}
}
