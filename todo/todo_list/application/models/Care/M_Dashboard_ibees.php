<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_Dashboard_ibees extends CI_Model
{

    public $limit;
    public $offset;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    // public function get_solved_tickets()
    // {
    // 	$query = $this->db->query("SELECT count(ticket_no) as solved_tickets from tb_ticket where ticket_status = 'Solved' union all SELECT count(ticket_no) as solved_tickets from tb_ticket_bulk where ticket_status = 'Solved' ");
    // 	return $query->result();
    // }

    // public function get_pending_tickets()
    // {
    // 	$query = $this->db->query("SELECT count(ticket_no) as pending_tickets from tb_ticket where ticket_status = 'Pending' union all SELECT count(ticket_no) as pending_tickets from tb_ticket_bulk where ticket_status = 'Pending'");
    // 	return $query->result();
    // }

    // public function get_enquiries()
    // {
    // 	$query = $this->db->query("SELECT count(ticket_no) as enquiries from tb_ticket where ticket_type = 'Complaint' and ticket_status = 'Pending' union all SELECT count(ticket_no) as enquiries from tb_ticket_bulk where ticket_type = 'Complaint' and ticket_status = 'Pending' ");
    // 	return $query->result();
    // }

    // public function get_resend_requests()
    // {
    // 	$query = $this->db->query("SELECT count(t.ticket_no) as resend_requests from tb_ticket t inner join tb_resend r on t.ticket_no = r.ticket_no where t.ticket_type = 'Resend' and r.resend_status = 'Pending' union all SELECT count(bt.ticket_no) as resend_requests from tb_ticket_bulk bt inner join tb_resend_bulk br on bt.ticket_no = br.ticket_no where bt.ticket_type = 'Resend' and br.resend_status = 'Pending' ");
    // 	return $query->result();
    // }

    // public function get_returned_copies()
    // {
    // 	$query = $this->db->query("SELECT count(id) as returned_copies from tb_return union all SELECT count(id) as returned_copies from tb_return_bulk");
    // 	return $query->result();
    // }

    // public function get_total_tickets()
    // {
    // 	$query = $this->db->query("SELECT count(ticket_no) as total_tickets from tb_ticket union all SELECT count(ticket_no) as total_tickets from tb_ticket_bulk ");
    // 	return $query->result();
    // }



    public function total_subs()
    {
        $query = $this->db->query("SELECT count(sub_no) as total_sub from tb_ibees_subscriptions");
        return $query->result();
    }
    public function active_subs()
    {
        $query = $this->db->query("SELECT count(sub_no) as active_sub from tb_ibees_subscriptions where status='Active'");
        return $query->result();
    }

    public function suspended_subs()
    {
        $query = $this->db->query("SELECT count(sub_no) as suspended_sub from tb_ibees_subscriptions where status='suspended'");
        return $query->result();
    }

    public function shifted_subs()
    {
        $query = $this->db->query("SELECT count(sub_no) as shifted_sub from tb_ibees_subscriptions where status='shifted'");
        return $query->result();
    }
    public function expiring_in_three()
    {

        $query = $this->db->query("SELECT count(sub_no) as expiring from tb_ibees_subscriptions  where  TIMESTAMPDIFF(month,CURRENT_TIMESTAMP,date_to)='3' ");
        return $query->result();
    }

    public function expiring_in_one()
    {

        $query = $this->db->query("SELECT count(sub_no) as expiring from tb_ibees_subscriptions  where  TIMESTAMPDIFF(month,CURRENT_TIMESTAMP,date_to)='1' ");
        return $query->result();
    }

    public function expired_subs()
    {
        $query = $this->db->query("SELECT count(sub_no) as expired_sub from tb_ibees_subscriptions where status='expired'");
        return $query->result();
    }

    public function field_executive()
    {
        $query = $this->db->query("SELECT count(id) as field_count from tb_field_executive where status='Active'");
        return $query->result();
    }

    public function total_sales_today()
    {

        $query = $this->db->query("SELECT count(sub_no) as sales_today from tb_ibees_subscriptions where date(started_date) =curdate()");

        return $query->result();
    }


    public function new_sub_count()
    {
        $query = $this->db->query("SELECT count(sub_no) as new_sub from tb_ibees_subscriptions where date(date_from) =curdate()");
        return $query->result();
    }

    public function get_dashboard_details($condition)
    {
        $searchKeys = trim($_POST['search']['value']);
        $this->limit = $_POST['length'];
        $this->offset = $_POST['start'];

        $query = $this->db->query("SELECT
        s.sub_no,
           s.status,
           s.address_id,
           s.date_from,
           s.date_to,
          sma.address,
           sma.email,
       
         sma.name,
     
       sma.mobile_number,
           o.office,
           ut.user_type
       
       from tb_ibees_subscriptions s
           inner join tb_ibees_address sma on s.address_id = sma.address_id
           inner join tb_ibees_user_type ut on sma.address_id = ut.address_id
           inner join tb_offices o on s.office_id = o.office_id
           $condition
       order by sma.address_id desc limit $this->limit offset $this->offset");

        return $query->result();
    }

    public function count_dashboard_subs($condition)
	{
		if (isset($_POST['search']['value']) && $_POST['search']['value'] > 1) {
			$_POST['search']['value'] = $_POST['search']['value'];
		}
		$searchKeys = $_POST['search']['value'];
		$query = $this->db->query("SELECT count(*) ckk from
				(
				SELECT
				s.sub_no,
				s.status,
				c.name,
				c.mobile_number,
				c.email,
				
				DATE(s.date_from) date_from,
				DATE(s.date_to) date_to
				from tb_ibees_subscriptions s
				inner join tb_ibees_address c on s.address_id=c.address_id

				inner join tb_offices d on d.office_id=s.office_id
				-- inner join tb_languages l on s.language_id = l.language_id
				-- inner join tb_address a on s.address_id = a.address_id
				-- inner join tb_final_type ft on s.final_type_id = ft.final_type_id
				--  inner join tb_duration dr on ft.duration_id=dr.duration_id
				and(
				-- c.account_no like '$searchKeys%' or
				s.sub_no like '$searchKeys%' or
				c.name like '$searchKeys%' or
				c.mobile_number like '$searchKeys%' or
				c.email like '$searchKeys%' or
				-- l.language_name like '$searchKeys%' or
				-- a.pincode like '$searchKeys%' or
				-- a.state like '$searchKeys%' or
				DATE(s.date_from) like '$searchKeys%' 
				-- d.dis_method like '$searchKeys%'
			)  $condition

				)
				ss");
		$ck = $query->result();
		return $ck[0]->ckk;
	}
}
