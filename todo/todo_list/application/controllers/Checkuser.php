<?php

defined('BASEPATH') or exit('No direct script access allowed');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, OPTIONS");

//Implimented by Akhil

//Akhil's code starts here 

class Checkuser extends CI_Controller
{

    public function check_user()
    {

        $json = file_get_contents('php://input');
      
        $obj = json_decode($json, TRUE);
        $entry =$this->security->xss_clean($obj['entry']);
        


        if (preg_match('/^[0-9]{10}+$/',$entry)) {


            $this->load->model("M_user_check");


            $mob_check = $this->M_user_check->mobile_check($entry);



            if ($mob_check) {

                $response = array(
                    "msg" => "user found",
                    "data" => $mob_check
                );
                // var_dump($response['data']);
                // exit(0);
                echo json_encode($response);
            } else {
                $response = array("msg" => "no user found",);
                echo json_encode($response);
            }
        } else {


            $this->load->model("M_user_check");
            $sub_check = $this->M_user_check->subscription_check($entry);

            if ($sub_check) {
                $response = array(
                    'msg' => "user found",
                    "data" => $sub_check
                );
                
                echo json_encode($response);
            } else {
                $response = array("msg" => "no user found",);
                echo json_encode($response);
            }
        }
    }
}
