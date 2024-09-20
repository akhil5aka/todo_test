<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_Returned extends CI_Model
{
	public $limit;
	public $offset;
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	public function getSmsData()
	{
		$query = $this->db->query("SELECT 
			r.id,
			r.month_of_issue,
			r.year_of_issue,
			r.reason,
			c.mobile,
			c.name,
			r.sub_no
			from tb_return r 
			inner join tb_main_subscriptions s on r.sub_no = s.sub_no 
			inner join tb_customer c on c.account_no = s.account_no 
			where c.mobile != ''
			and r.sms_status='Pending' ");
		return $query->result();
	} 

	public function resetReturnCount($sub_no)
	{
		$query = $this->db->query("DELETE from tb_return where sub_no='$sub_no'");
		return $this->db->affected_rows();
	}

	public function resetResendCount($sub_no)
	{
		$query = $this->db->query("DELETE from tb_resend where sub_no='$sub_no'");
		return $this->db->affected_rows();
	}

	public function resetSuspended($sub_no)
	{
		$query = $this->db->query("DELETE from tb_flagged_sub where sub_no='$sub_no'");
		return $this->db->affected_rows();
	}

	public function resetBulkReturnCount($sub_no)
	{
		$query = $this->db->query("DELETE from tb_return_bulk where sub_no='$sub_no'");
		return $this->db->affected_rows();
	}

	public function resetBulkResendCount($sub_no)
	{
		$query = $this->db->query("DELETE from tb_resend_bulk where sub_no='$sub_no'");
		return $this->db->affected_rows();
	}

	public function resetBulkSuspended($sub_no)
	{
		$query = $this->db->query("DELETE from tb_flagged_sub_bulk where sub_no='$sub_no'");
		return $this->db->affected_rows();
	}

	public function updateSMSStatus($data)
	{
		$this->db->update_batch('tb_return', $data, 'id');
		return $this->db->affected_rows();
	}

	public function getBulkSmsData()
	{
		$query = $this->db->query("SELECT 
			r.id,
			r.month_of_issue,
			r.year_of_issue,
			r.reason,
			c.mobile,
			c.name,
			r.sub_no
			from tb_return_bulk r 
			inner join tb_bulk_courier s on r.sub_no = s.sub_no 
			inner join tb_customer c on c.account_no = s.account_no 
			where c.mobile != ''
			and r.sms_status='Pending' ");
		return $query->result();
	} 

	public function updateBulkSMSStatus($data)
	{
		$this->db->update_batch('tb_return_bulk', $data, 'id');
		return $this->db->affected_rows();
	}

	public function save_new_return($data)
	{
		$data = $this->db->escape_str($data);
		$this->db->insert('tb_return',$data);
		return $this->db->affected_rows();
	}

	public function get_total_returns($sub_no)
	{
		$query = $this->db->query("SELECT id from tb_return where sub_no='$sub_no' ");
		return $query->num_rows();
	}

	public function is_subno_exist($sub_no)
	{
		$query = $this->db->query("SELECT sub_no from tb_main_subscriptions where sub_no = '$sub_no' ");
		if($query->num_rows()>0)
		{
			return true;
		}
		return false;
	}

	public function flag_sub($data,$sub_no)
	{
		$data = $this->db->escape_str($data);
		$this->db->insert('tb_flagged_sub',$data);
		// $this->update_flag_in_main($sub_no,"YES");
	}

	public function flag_bulk_sub($data,$sub_no)
	{
		$data = $this->db->escape_str($data);
		$this->db->insert('tb_flagged_sub_bulk',$data);
		// $this->update_flag_in_main($sub_no,"YES");
	}

	public function update_flag_in_main($sub_no,$value)
	{
		$query = $this->db->query("UPDATE tb_main_subscriptions set flag_status = '$value' where sub_no='$sub_no' ");
	}

	public function update_sub_status($sub_no,$value)
	{
		$query = $this->db->query("UPDATE tb_main_subscriptions set sub_status = '$value' where sub_no='$sub_no' ");
	}

	public function update_bulk_sub_status($sub_no,$value)
	{
		$query = $this->db->query("UPDATE tb_bulk_sub set sub_status = '$value' where sub_no='$sub_no' ");
		$query = $this->db->query("UPDATE tb_bulk_courier set sub_status = '$value' where sub_no='$sub_no' ");
	}


	public function get_mail_data($sub_no)
	{
		$query = $this->db->query("SELECT 
			s.sub_no,
			l.language_name,
			DATE(s.date_from) date_from,
			DATE(s.date_to) date_to,
			s.address_id,
			a.address,
			a.address2,
			a.landmark,
			a.city,
			a.state,
			a.country,
			a.pincode,
			c.email,
			c.name
			from tb_main_subscriptions s,
			tb_languages l,
			tb_address a,
			tb_customer c
			where s.sub_no ='$sub_no'
			and c.account_no = s.account_no
			and s.language_id = l.language_id 
			and s.address_id = a.address_id ");
		return $query->result();
	}

	function get_returned_data()
	{
        // $this->_get_query();
		if(isset($_POST['length']) && $_POST['length'] < 1) {
			$_POST['length']= '10';
		} else
		$_POST['length']= $_POST['length'];

		if(isset($_POST['start']) && $_POST['start'] > 1) {
			$_POST['start']= $_POST['start'];
		}

		if(isset($_POST['search']['value']) && $_POST['search']['value'] > 1) {
			$_POST['search']['value']= $_POST['search']['value'];
		}
		$searchKeys = $_POST['search']['value'];

		$columnToOrder = "sub_no";
		$sortOrder = "desc";

		$this->limit = $_POST['length'];
		$this->offset = $_POST['start'];
		
		$query = $this->db->query("SELECT
			r.id,
			r.sub_no,
			r.reason,
			DATE(r.received_date) received_date,
			r.month_of_issue,
			r.year_of_issue,
			l.language_name,
			c.name,
			c.email,
			c.mobile,
			a.pincode
			from tb_return r inner join tb_main_subscriptions s on r.sub_no = s.sub_no
			inner join tb_languages l on s.language_id = l.language_id
			inner join tb_customer c on s.account_no = c.account_no
			inner join tb_address a on s.address_id = a.address_id
			where 
			r.sub_no like '$searchKeys%' or
			l.language_name like '$searchKeys%' or
			c.name like '$searchKeys%' or
			c.mobile like '$searchKeys%' or
			c.email like '$searchKeys%' or
			a.pincode like '$searchKeys%'
			order by sub_no desc limit $this->limit offset $this->offset");
		
		return $query->result();	
		
	}


	public function count_all_returned()
	{
		if(isset($_POST['search']['value']) && $_POST['search']['value'] > 1) {
			$_POST['search']['value']= $_POST['search']['value'];
		}
		$searchKeys = $_POST['search']['value'];
		$query = $this->db->query("SELECT count(*) ckk from 
			(
			SELECT
			r.id,
			r.sub_no,
			r.reason,
			DATE(r.received_date) received_date,
			r.month_of_issue,
			r.year_of_issue,
			l.language_name,
			c.name,
			c.email,
			c.mobile,
			a.pincode
			from tb_return r inner join tb_main_subscriptions s on r.sub_no = s.sub_no
			inner join tb_languages l on s.language_id = l.language_id
			inner join tb_customer c on s.account_no = c.account_no
			inner join tb_address a on s.address_id = a.address_id
			where 
			r.sub_no like '$searchKeys%' or
			l.language_name like '$searchKeys%' or
			c.name like '$searchKeys%' or
			c.mobile like '$searchKeys%' or
			c.email like '$searchKeys%' or
			a.pincode like '$searchKeys%'
			) 
			ss");
		$ck = $query->result();
		return $ck[0]->ckk;
	}

	public function is_bulk_subno_exist($sub_no)
	{
		$query = $this->db->query("SELECT sub_no from tb_bulk_courier where sub_no = '$sub_no' ");
		if($query->num_rows()>0)
		{
			return true;
		}
		return false;
	}

	public function save_new_bulk_return($data)
	{
		$data = $this->db->escape_str($data);
		$this->db->insert('tb_return_bulk',$data);
		return $this->db->affected_rows();
	}

	public function get_total_bulk_returns($sub_no)
	{
		$query = $this->db->query("SELECT id from tb_return_bulk where sub_no='$sub_no' ");
		return $query->num_rows();
	}

	function get_bulk_returned_data()
	{
        // $this->_get_query();
		if(isset($_POST['length']) && $_POST['length'] < 1) {
			$_POST['length']= '10';
		} else
		$_POST['length']= $_POST['length'];

		if(isset($_POST['start']) && $_POST['start'] > 1) {
			$_POST['start']= $_POST['start'];
		}

		if(isset($_POST['search']['value']) && $_POST['search']['value'] > 1) {
			$_POST['search']['value']= $_POST['search']['value'];
		}
		$searchKeys = $_POST['search']['value'];

		$columnToOrder = "sub_no";
		$sortOrder = "desc";

		$this->limit = $_POST['length'];
		$this->offset = $_POST['start'];
		
		$query = $this->db->query("SELECT
			r.id,
			r.sub_no,
			r.reason,
			DATE(r.received_date) received_date,
			r.month_of_issue,
			r.year_of_issue,
			c.name,
			c.email,
			c.mobile,
			a.pincode
			from tb_return_bulk r 
			inner join tb_bulk_courier s on r.sub_no = s.sub_no
			inner join tb_customer c on s.account_no = c.account_no
			inner join tb_address a on s.address_id = a.address_id
			where r.sub_no like '$searchKeys%' or
			c.name like '$searchKeys%' or
			c.mobile like '$searchKeys%' or
			c.email like '$searchKeys%' or
			a.pincode like '$searchKeys%' 
			order by sub_no desc limit $this->limit offset $this->offset");
		return $query->result();	
		
	}


	public function count_all_bulk_returned()
	{
		if(isset($_POST['search']['value']) && $_POST['search']['value'] > 1) {
			$_POST['search']['value']= $_POST['search']['value'];
		}
		$searchKeys = $_POST['search']['value'];
		$query = $this->db->query("SELECT count(*) ckk from 
			(
			SELECT
			r.id,
			r.sub_no,
			r.reason,
			DATE(r.received_date) received_date,
			r.month_of_issue,
			r.year_of_issue,
			c.name,
			c.email,
			c.mobile,
			a.pincode
			from tb_return_bulk r 
			inner join tb_bulk_courier s on r.sub_no = s.sub_no
			inner join tb_customer c on s.account_no = c.account_no
			inner join tb_address a on s.address_id = a.address_id
			where r.sub_no like '$searchKeys%' or
			c.name like '$searchKeys%' or
			c.mobile like '$searchKeys%' or
			c.email like '$searchKeys%' or
			a.pincode like '$searchKeys%' 
			) 
			ss");
		$ck = $query->result();
		return $ck[0]->ckk;
	}

	public function is_imaze_subno_exist($sub_no)
	{
		$query = $this->db->query("SELECT sub_no from tb_imaze_subscriptions where sub_no = '$sub_no' ");
		if ($query->num_rows() > 0) {
			return true;
		}
		return false;
	}

	public function flag_imaze_sub($data, $sub_no)
	{
		$data = $this->db->escape_str($data);
		$this->db->insert('tb_imaze_flagged_sub', $data);
	}

	public function update_imaze_sub_status($sub_no, $value)
	{
		$query = $this->db->query("UPDATE tb_imaze_subscriptions set status = '$value' where sub_no='$sub_no' ");
	}


	//Ibees


	public function is_ibees_subno_exist($sub_no)
	{
		$query = $this->db->query("SELECT sub_no from tb_ibees_subscriptions where sub_no = '$sub_no' ");
		if ($query->num_rows() > 0) {
			return true;
		}
		return false;
	}

	public function flag_ibees_sub($data, $sub_no)
	{
		$data = $this->db->escape_str($data);
		$this->db->insert('tb_ibees_flagged_sub', $data);
	}

	public function update_ibees_sub_status($sub_no, $value)
	{
		$query = $this->db->query("UPDATE tb_ibees_subscriptions set status = '$value' where sub_no='$sub_no' ");
	}

}