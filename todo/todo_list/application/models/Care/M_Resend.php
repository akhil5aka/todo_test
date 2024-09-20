<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_Resend extends CI_Model
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
			g.id,
			g.docket_no,
			r.month_of_issue,
			r.year_of_issue,
			g.ticket_no,
			csp.csp_name,
			c.mobile,
			c.name,
			date(g.dispatched_on) dispatched_on
			g.sub_no
			from tb_resend_labels g
			inner join tb_resend r on g.sub_no = r.sub_no
			inner join tb_main_subscriptions s on r.sub_no = s.sub_no
			inner join tb_courier_providers csp on g.csp_id = csp.csp_id
			inner join tb_customer c on c.account_no = s.account_no
			where g.dispatch_status = 'Dispatched'
			and c.mobile != ''
			and g.sms_status='Pending' limit 1");
		return $query->result();
	}

	public function updateSMSStatus($data)
	{
		$this->db->update_batch('tb_resend_labels', $data, 'id');
		return $this->db->affected_rows();
	}

	public function get_resends($resend_mode)
	{
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

		$columnToOrder = "ticket_no";
		$sortOrder = "desc";

		$this->limit = $_POST['length'];
		$this->offset = $_POST['start'];
		$query = $this->db->query("SELECT distinct t.ticket_no,DATE(t.received_date) received_date, s.sub_no,c.name,c.email,c.mobile,l.language_name,a.state,t.ticket_type,t.ticket_status
			from tb_resend r inner join tb_ticket t on r.ticket_no=t.ticket_no
			inner join tb_main_subscriptions s on t.sub_no = s.sub_no
			inner join tb_customer c on s.account_no = c.account_no
			inner join tb_languages l on s.language_id = l.language_id
			inner join tb_address a on s.address_id = a.address_id
			where r.resend_mode = '$resend_mode'
			and r.resend_status = 'Pending'
			and(
			t.ticket_no like '$searchKeys%' or
			s.sub_no like '$searchKeys%' or
			c.name like '$searchKeys%' or
			c.mobile like '$searchKeys%' or
			c.email like '$searchKeys%' or
			l.language_name like '$searchKeys%' or
			a.pincode like '$searchKeys%' or
			a.state like '$searchKeys%' or
			DATE(t.received_date) like '$searchKeys%'
			)
			order by received_date desc limit $this->limit offset $this->offset");

		return $query->result();
	}

	public function count_all($resend_mode)
	{
		if(isset($_POST['search']['value']) && $_POST['search']['value'] > 1) {
			$_POST['search']['value']= $_POST['search']['value'];
		}
		$searchKeys = $_POST['search']['value'];
		$query = $this->db->query("SELECT count(*) ckk from
			(
			SELECT distinct t.ticket_no,DATE(t.received_date) received_date, s.sub_no,c.name,c.email,c.mobile,l.language_name,a.state,t.ticket_type,t.ticket_status
			from tb_resend r inner join tb_ticket t on r.ticket_no=t.ticket_no
			inner join tb_main_subscriptions s on t.sub_no = s.sub_no
			inner join tb_customer c on s.account_no = c.account_no
			inner join tb_languages l on s.language_id = l.language_id
			inner join tb_address a on s.address_id = a.address_id
			where r.resend_mode = '$resend_mode'
			and r.resend_status = 'Pending'
			and(
			t.ticket_no like '$searchKeys%' or
			s.sub_no like '$searchKeys%' or
			c.name like '$searchKeys%' or
			c.mobile like '$searchKeys%' or
			c.email like '$searchKeys%' or
			l.language_name like '$searchKeys%' or
			a.pincode like '$searchKeys%' or
			a.state like '$searchKeys%' or
			DATE(t.received_date) like '$searchKeys%'
			)
			)
			ss");
		$ck = $query->result();
		return $ck[0]->ckk;
	}

	public function generate_label($resend_mode)
	{
		$query = $this->db->query("SELECT DISTINCT
			t.sub_no,
			t.ticket_no,
			l.language_name,
			a.*,
			c.name,
			c.mobile from
			tb_resend r
			inner join tb_ticket t on r.ticket_no = t.ticket_no
			inner join tb_main_subscriptions s on t.sub_no = s.sub_no
			inner join tb_languages l on s.language_id = l.language_id
			inner join tb_address a on s.address_id = a.address_id
			inner join tb_customer c on s.account_no = c.account_no
			where t.ticket_status = 'Pending'
			and r.resend_status = 'Pending'
			and t.ticket_type = 'Resend'
			and t.resend_mode = '$resend_mode'
			");
		$sd = $query->result();
		if(sizeof($sd)>0)
		{
			$s = $this->get_months_of_resend($sd);
			return $s;
		}
		return false;
	}

	private function get_months_of_resend($data)
	{
		$headSize = sizeof($data);
		for ($i=0; $i < $headSize; $i++)
		{
			$ticket_no = $data[$i]->ticket_no;
			$ticket_no = $this->db->escape_str($data[$i]->ticket_no);
			$query = $this->db->query("SELECT month_of_issue,year_of_issue from tb_resend where ticket_no='$ticket_no'");

			$inner_data = $query->result();
			$full[] = array
			(
				'outer'=> $data[$i],
				'inner' => $inner_data
			);
		}
		if(sizeof($full)>0)
		{
			return $full;
		}
	}

	public function save_generated_sub($resend_mode)
	{
		$query = $this->db->query("INSERT into tb_resend_labels
			SELECT DISTINCT
			null id,
			t.sub_no,
			'' docket_no,
			'Processing' dispatch_status,
			'' dispatched_on ,
			'' csp_id,
			t.ticket_no,
			'Pending' sms_status,
			'Pending' email_status
			from
			tb_ticket t
			inner join tb_resend r on r.sub_no = t.sub_no
			where t.ticket_status = 'Pending'
			and r.resend_status = 'Pending'
			and t.ticket_type = 'Resend'
			and t.resend_mode = '$resend_mode' ");
		if($this->db->affected_rows()>0)
		{
			return true;
		}
		return false;
	}

	public function update_ticket_status($resend_mode)
	{
		$query = $this->db->query("UPDATE tb_resend r
			inner join tb_ticket t on r.sub_no = t.sub_no
			set r.resend_status = 'Generated'
			where t.ticket_status = 'Pending'
			and r.resend_status = 'Pending'
			and t.ticket_type = 'Resend'
			and t.resend_mode = '$resend_mode' ");
		if($this->db->affected_rows()>0)
		{
			return true;
		}
		return false;
	}


	public function get_generated_labels($resend_mode)
	{
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
		$query = $this->db->query("SELECT DISTINCT
			g.id,
			g.sub_no,
			g.docket_no,
			c.name,
			c.email,
			c.mobile,
			a.address,
			a.address2,
			a.city,
			a.state,
			a.landmark,
			a.country,
			a.pincode from
			tb_ticket t,
			tb_resend_labels g,
			tb_customer c,
			tb_address a,
			tb_main_subscriptions s
			where g.ticket_no = t.ticket_no
			and g.dispatch_status!='Dispatched'
			and g.sub_no=s.sub_no
			and s.account_no=c.account_no
			and s.address_id=a.address_id
			and t.resend_mode = '$resend_mode'
			and(
			g.sub_no like '$searchKeys%'
		) order by sub_no desc limit $this->limit offset $this->offset");
		return $query->result();
	}

	public function count_all_generated($resend_mode)
	{
		if(isset($_POST['search']['value']) && $_POST['search']['value'] > 1) {
			$_POST['search']['value']= $_POST['search']['value'];
		}
		$searchKeys = $_POST['search']['value'];
		$query = $this->db->query("SELECT count(*) ckk from
			(
			SELECT DISTINCT
			g.id,
			g.sub_no,
			g.docket_no,
			c.name,
			c.email,
			c.mobile,
			a.address,
			a.address2,
			a.city,
			a.state,
			a.landmark,
			a.country,
			a.pincode from
			tb_ticket t,
			tb_resend_labels g,
			tb_customer c,
			tb_address a,
			tb_main_subscriptions s
			where g.ticket_no = t.ticket_no
			and g.dispatch_status!='Dispatched'
			and g.sub_no=s.sub_no
			and s.account_no=c.account_no
			and s.address_id=a.address_id
			and t.resend_mode = '$resend_mode'
			and (
			g.sub_no like '$searchKeys%'
			)
			)
			ss");
		$ck = $query->result();
		return $ck[0]->ckk;
	}

	public function check_all_dockets($resend_mode)
	{
		$query = $this->db->query("SELECT r.id from tb_resend_labels r inner join tb_ticket t on r.ticket_no=t.ticket_no where t.resend_mode='$resend_mode' and r.docket_no='' limit 1");
		return $query->num_rows();
	}


	public function save_docket($id,$docket_no,$csp_id)
	{
		$query = $this->db->query("UPDATE tb_resend_labels set docket_no='$docket_no',csp_id='$csp_id' where id='$id'");
		return $this->db->affected_rows();
	}

	public function update_docket($column,$new_value,$id)
	{
		$query = $this->db->query("UPDATE tb_resend_labels set $column = '$new_value' where id = '$id' ");
		return 0;
	}

	public function dispatch_resend($resend_mode)
	{
		date_default_timezone_set("Asia/Calcutta");
		$dispatched_on = date("Y-m-d H:i:s");
		if($resend_mode==1)
		{
			$query = $this->db->query("UPDATE tb_resend_labels r
				inner join tb_ticket t on r.ticket_no = t.ticket_no
				inner join tb_resend r1 on t.ticket_no = r1.ticket_no
				set r.dispatch_status = 'Dispatched',
				r1.resend_status = 'Dispatched',
				r.dispatched_on = '$dispatched_on',
				r.csp_id = 3,
				t.ticket_status = 'Solved'
				where t.resend_mode = '$resend_mode'
				and r.dispatch_status = 'Processing'
				");
		}
		else
		{
			$query = $this->db->query("UPDATE tb_resend_labels r
				inner join tb_ticket t on r.ticket_no = t.ticket_no
				inner join tb_resend r1 on t.ticket_no = r1.ticket_no
				set r.dispatch_status = 'Dispatched',
				r1.resend_status = 'Dispatched',
				r.dispatched_on = '$dispatched_on',
				t.ticket_status = 'Solved'
				where t.resend_mode = '$resend_mode'
				and r.dispatch_status = 'Processing'
				");
		}

		return $this->db->affected_rows();
	}

	public function get_email_data($resend_mode)
	{
		//Write Code here
	}

	public function is_generated($resend_mode)
	{
		$query = $this->db->query("SELECT r.id from tb_resend_labels r,tb_ticket t where r.ticket_no=t.ticket_no and r.dispatch_status='Processing'");
		if($query->num_rows()>0)
		{
			return $query->result();
		}
		return false;
	}

	public function clear_tables()
	{
		$query = $this->db->query("DELETE from tb_resend_docket_upload");
		$query = $this->db->query("ALTER table tb_resend_docket_upload auto_increment=1");
	}

	public function upload_to_temp($file)
	{
		$table = 'tb_resend_docket_upload';

		$query = $this->db->query('LOAD DATA LOCAL INFILE "'.$file.'"
			REPLACE INTO TABLE '.$table.'
			character set UTF8
			FIELDS TERMINATED by \',\'
			OPTIONALLY ENCLOSED BY \'"\'
			LINES TERMINATED BY \'\n\'
			IGNORE 1 ROWS
			(sub_no,docket_no)
			');
		return $this->db->affected_rows();
	}

	public function save_docket_no($csp_id,$dispatched_on)
	{
		$query = $this->db->query("UPDATE tb_resend_labels g inner join tb_resend_docket_upload u on g.sub_no = u.sub_no SET g.docket_no = trim(TRAILING '\r' from u.docket_no), g.csp_id='$csp_id'");
	}

	public function get_excel_data($resend_mode)
	{
		$query = $this->db->query("SELECT
			t.sub_no,
			c.name,
			a.address,
			a.address2,
			a.landmark,
			a.city,
			a.state,
			a.pincode,
			a.phone,
			c.mobile,
			l.language_name
			from tb_resend_labels r,
			tb_ticket t,
			tb_main_subscriptions s,
			tb_languages l,
			tb_address a,
			tb_customer c
			where
			r.ticket_no = t.ticket_no
			and t.resend_mode='$resend_mode'
			and r.dispatch_status!='Dispatched'
			and t.sub_no=s.sub_no
			and s.language_id = l.language_id
			and s.address_id = a.address_id
			and a.account_no = c.account_no
			");
		return $query->result();
	}

	public function get_all_resends($resend_mode)
	{
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
		$searchKeys = 1;

		$columnToOrder = "ticket_no";
		$sortOrder = "desc";

		$this->limit = $_POST['length'];
		$this->offset = $_POST['start'];

		// if($resend_mode="All")
		// {
		// 	$resend_mode = '';
		// }
		$query = $this->db->query("SELECT t.ticket_no,DATE(t.received_date) received_date, s.sub_no,c.name,c.email,c.mobile,l.language_name,a.state,t.ticket_type,t.ticket_status
			from tb_ticket t inner join tb_main_subscriptions s on t.sub_no = s.sub_no
			inner join tb_customer c on s.account_no = c.account_no
			inner join tb_languages l on s.language_id = l.language_id
			inner join tb_address a on s.address_id = a.address_id
			where
			-- t.resend_mode like '$resend_mode%'
			t.ticket_type = 'Resend'
			and(
			t.ticket_no like '$searchKeys%' or
			s.sub_no like '$searchKeys%' or
			c.name like '$searchKeys%' or
			c.mobile like '$searchKeys%' or
			c.email like '$searchKeys%' or
			l.language_name like '$searchKeys%' or
			a.pincode like '$searchKeys%' or
			a.state like '$searchKeys%' or
			DATE(t.received_date) like '$searchKeys%'
		) order by received_date desc limit $this->limit offset $this->offset");

		return $query->result();
	}

	public function count_all_resend($resend_mode)
	{
		if(isset($_POST['search']['value']) && $_POST['search']['value'] > 1) {
			$_POST['search']['value']= $_POST['search']['value'];
		}
		$searchKeys = $_POST['search']['value'];
		$query = $this->db->query("SELECT count(*) ckk from
			(
			SELECT t.ticket_no,DATE(t.received_date) received_date, s.sub_no,c.name,c.email,c.mobile,l.language_name,a.state,t.ticket_type,t.ticket_status
			from tb_ticket t inner join tb_main_subscriptions s on t.sub_no = s.sub_no
			inner join tb_customer c on s.account_no = c.account_no
			inner join tb_languages l on s.language_id = l.language_id
			inner join tb_address a on s.address_id = a.address_id
			where t.resend_mode = '$resend_mode'
			and(
			t.ticket_type = 'Resend' or
			t.ticket_no like '$searchKeys%' or
			s.sub_no like '$searchKeys%' or
			c.name like '$searchKeys%' or
			c.mobile like '$searchKeys%' or
			c.email like '$searchKeys%' or
			l.language_name like '$searchKeys%' or
			a.pincode like '$searchKeys%' or
			a.state like '$searchKeys%' or
			DATE(t.received_date) like '$searchKeys%'
			)
			)
			ss");
		$ck = $query->result();
		return $ck[0]->ckk;
	}


	//BULK

	public function get_bulk_resends($resend_mode)
	{
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


		$columnToOrder = "ticket_no";
		$sortOrder = "desc";

		$this->limit = $_POST['length'];
		$this->offset = $_POST['start'];

		$query = $this->db->query("SELECT distinct t.ticket_no,DATE(t.received_date) received_date, s.sub_no,c.name,c.email,c.mobile,a.state,t.ticket_type,t.ticket_status
			from tb_resend_bulk r inner join tb_ticket_bulk t on r.ticket_no=t.ticket_no
			inner join tb_bulk_courier s on t.sub_no = s.sub_no
			inner join tb_customer c on s.account_no = c.account_no
			inner join tb_address a on s.address_id = a.address_id
			where r.resend_mode = '$resend_mode'
			and r.resend_status = 'Pending'
			and(
			t.ticket_no like '$searchKeys%' or
			s.sub_no like '$searchKeys%' or
			c.name like '$searchKeys%' or
			c.mobile like '$searchKeys%' or
			c.email like '$searchKeys%' or
			a.pincode like '$searchKeys%' or
			a.state like '$searchKeys%' or
			DATE(t.received_date) like '$searchKeys%'
		) order by received_date desc limit $this->limit offset $this->offset");

		return $query->result();
	}

	public function count_all_bulk_resends($resend_mode)
	{
		if(isset($_POST['search']['value']) && $_POST['search']['value'] > 1) {
			$_POST['search']['value']= $_POST['search']['value'];
		}
		$searchKeys = $_POST['search']['value'];
		$query = $this->db->query("SELECT count(*) ckk from
			(
			SELECT distinct t.ticket_no,DATE(t.received_date) received_date, s.sub_no,c.name,c.email,c.mobile,a.state,t.ticket_type,t.ticket_status
			from tb_resend_bulk r inner join tb_ticket_bulk t on r.ticket_no=t.ticket_no
			inner join tb_bulk_courier s on t.sub_no = s.sub_no
			inner join tb_customer c on s.account_no = c.account_no
			inner join tb_address a on s.address_id = a.address_id
			where r.resend_mode = '$resend_mode'
			and r.resend_status = 'Pending'
			and(
			t.ticket_no like '$searchKeys%' or
			s.sub_no like '$searchKeys%' or
			c.name like '$searchKeys%' or
			c.mobile like '$searchKeys%' or
			c.email like '$searchKeys%' or
			a.pincode like '$searchKeys%' or
			a.state like '$searchKeys%' or
			DATE(t.received_date) like '$searchKeys%'
			)
			)
			ss");
		$ck = $query->result();
		return $ck[0]->ckk;
	}

	public function generate_bulk_label($resend_mode)
	{
		$query = $this->db->query("SELECT DISTINCT
			t.sub_no,
			r.month_of_issue,
			a.*,
			c.name,
			c.mobile from
			tb_resend_bulk r
			inner join tb_ticket_bulk t on r.ticket_no = t.ticket_no
			inner join tb_bulk_courier s on t.sub_no = s.sub_no
			inner join tb_address a on s.address_id = a.address_id
			inner join tb_customer c on s.account_no = c.account_no
			where t.ticket_status = 'Pending'
			and r.resend_status = 'Pending'
			and t.ticket_type = 'Resend'
			and t.resend_mode = '$resend_mode'
			");
		$sd = $query->result();

		if(sizeof($sd)>0)
		{
			$s = $this->get_languages($sd);
			return $s;
		}
		return false;
	}

	private function get_languages($data)
	{
		$headSize = sizeof($data);
		for ($i=0; $i < $headSize; $i++)
		{
			$sub_no = $data[$i]->sub_no;
			$sub_no = $this->db->escape_str($data[$i]->sub_no);
			$query = $this->db->query("SELECT
				b.id,l.language_code,
				sum(b.no_of_copies) ncp
				from tb_bulk_sub b,
				tb_languages l
				WHERE b.sub_no='$sub_no'
				and l.language_id = b.language_id
				group by b.language_id
				");

			$inner_data = $query->result();

			$full[] = array
			(
				'outer'=> $data[$i],
				'inner' => $inner_data
			);
		}
 // return $full;
		if(sizeof($full)>0)
		{
    // $output = json_encode($full,JSON_PRETTY_PRINT);
			return $full;
		}
	}

	public function save_generated_bulk_sub($resend_mode)
	{
		$query = $this->db->query("INSERT into tb_resend_labels_bulk
			SELECT DISTINCT
			null id,
			t.sub_no,
			'' docket_no,
			'Processing' dispatch_status,
			'' dispatched_on ,
			'' csp_id,
			t.ticket_no,
			'Pending' sms_status,
			'Pending' email_status
			from
			tb_ticket_bulk t
			inner join tb_resend_bulk r on r.sub_no = t.sub_no
			where t.ticket_status = 'Pending'
			and r.resend_status = 'Pending'
			and t.ticket_type = 'Resend'
			and t.resend_mode = '$resend_mode' ");
		if($this->db->affected_rows()>0)
		{
			return true;
		}
		return false;
	}

	public function update_bulk_ticket_status($resend_mode)
	{
		$query = $this->db->query("UPDATE tb_resend_bulk r
			inner join tb_ticket_bulk t on r.sub_no = t.sub_no
			set r.resend_status = 'Generated'
			where t.ticket_status = 'Pending'
			and r.resend_status = 'Pending'
			and t.ticket_type = 'Resend'
			and t.resend_mode = '$resend_mode' ");
		if($this->db->affected_rows()>0)
		{
			return true;
		}
		return false;
	}

	public function get_bulk_generated_labels($resend_mode)
	{
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
		$query = $this->db->query("SELECT DISTINCT
			g.id,
			g.sub_no,
			g.docket_no,
			c.name,
			c.email,
			c.mobile,
			a.address,
			a.address2,
			a.city,
			a.state,
			a.landmark,
			a.country,
			a.pincode from
			tb_ticket_bulk t,
			tb_resend_labels_bulk g,
			tb_customer c,
			tb_address a,
			tb_bulk_courier s
			where g.ticket_no = t.ticket_no
			and g.dispatch_status!='Dispatched'
			and g.sub_no=s.sub_no
			and s.account_no=c.account_no
			and s.address_id=a.address_id
			and t.resend_mode = '$resend_mode'
			and(
			g.sub_no like '$searchKeys%'
		) order by sub_no desc limit $this->limit offset $this->offset");
		return $query->result();
	}

	public function count_all_bulk_generated($resend_mode)
	{
		if(isset($_POST['search']['value']) && $_POST['search']['value'] > 1) {
			$_POST['search']['value']= $_POST['search']['value'];
		}
		$searchKeys = $_POST['search']['value'];
		$query = $this->db->query("SELECT count(*) ckk from
			(
			SELECT DISTINCT
			g.id,
			g.sub_no,
			g.docket_no,
			c.name,
			c.email,
			c.mobile,
			a.address,
			a.address2,
			a.city,
			a.state,
			a.landmark,
			a.country,
			a.pincode from
			tb_ticket_bulk t,
			tb_resend_labels_bulk g,
			tb_customer c,
			tb_address a,
			tb_bulk_courier s
			where g.ticket_no = t.ticket_no
			and g.dispatch_status!='Dispatched'
			and g.sub_no=s.sub_no
			and s.account_no=c.account_no
			and s.address_id=a.address_id
			and t.resend_mode = '$resend_mode'
			and (
			g.sub_no like '$searchKeys%'
			)
			)
			ss");
		$ck = $query->result();
		return $ck[0]->ckk;
	}

	public function save_bulk_docket($id,$docket_no,$csp_id)
	{
		$query = $this->db->query("UPDATE tb_resend_labels_bulk set docket_no='$docket_no',csp_id='$csp_id' where id='$id'");
		return $this->db->affected_rows();
	}

	public function update_bulk_docket($column,$new_value,$id)
	{
		$query = $this->db->query("UPDATE tb_resend_labels_bulk set $column = '$new_value' where id = '$id' ");
		return 0;
	}

	public function is_bulk_generated($resend_mode)
	{
		$query = $this->db->query("SELECT r.id from tb_resend_labels_bulk r,tb_ticket_bulk t where r.ticket_no=t.ticket_no and r.dispatch_status='Processing'");
		if($query->num_rows()>0)
		{
			return $query->result();
		}
		return false;
	}

	public function get_bulk_excel_data($resend_mode)
	{
		$query = $this->db->query("SELECT
			t.sub_no,
			c.name,
			a.address,
			a.address2,
			a.landmark,
			a.city,
			a.state,
			a.pincode,
			a.phone,
			c.mobile
			from tb_resend_labels_bulk r,
			tb_ticket_bulk t,
			tb_bulk_courier s,
			tb_address a,
			tb_customer c
			where
			r.ticket_no = t.ticket_no
			and t.resend_mode='$resend_mode'
			and r.dispatch_status!='Dispatched'
			and t.sub_no=s.sub_no
			and s.address_id = a.address_id
			and a.account_no = c.account_no
			");
		return $query->result();
	}

	public function check_all_bulk_dockets($resend_mode)
	{
		$query = $this->db->query("SELECT r.id from tb_resend_labels_bulk r inner join tb_ticket_bulk t on r.ticket_no=t.ticket_no where t.resend_mode='$resend_mode' and r.docket_no='' limit 1");
		return $query->num_rows();
	}


	public function dispatch_bulk_resend($resend_mode,$csp)
	{
		date_default_timezone_set("Asia/Calcutta");
		$dispatched_on = date("Y-m-d H:i:s");
		$query = $this->db->query("UPDATE tb_resend_labels_bulk r
			inner join tb_ticket_bulk t on r.ticket_no = t.ticket_no
			inner join tb_resend_bulk r1 on t.ticket_no = r1.ticket_no
			set r.dispatch_status = 'Dispatched',
			r.dispatched_on = '$dispatched_on',
			r1.resend_status = 'Dispatched',
			-- r.csp_id = '$csp',
			t.ticket_status = 'Solved'
			where t.resend_mode = '$resend_mode'
			and r.dispatch_status = 'Processing'
			");
		return $this->db->affected_rows();
	}

	public function save_bulk_docket_no($csp_id,$dispatched_on)
	{
		$query = $this->db->query("UPDATE tb_resend_labels_bulk g inner join tb_resend_docket_upload u on g.sub_no = u.sub_no SET g.docket_no = trim(TRAILING '\r' from u.docket_no), g.csp_id='$csp_id'");
	}

	public function getBulkSmsData()
	{
		$query = $this->db->query("SELECT
			g.id,
			g.docket_no,
			r.month_of_issue,
			r.year_of_issue,
			g.ticket_no,
			csp.csp_name,
			c.mobile,
			c.name,
			g.sub_no
			from tb_resend_labels_bulk g
			inner join tb_resend_bulk r on g.sub_no = r.sub_no
			inner join tb_bulk_courier s on r.sub_no = s.sub_no
			inner join tb_courier_providers csp on g.csp_id = csp.csp_id
			inner join tb_customer c on c.account_no = s.account_no
			where g.dispatch_status = 'Dispatched'
			and c.mobile != ''
			and g.sms_status='Pending' limit 1");
		return $query->result();
	}

	public function updateBulkSMSStatus($data)
	{
		$this->db->update_batch('tb_resend_labels_bulk', $data, 'id');
		return $this->db->affected_rows();
	}


}
