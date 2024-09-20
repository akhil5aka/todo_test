<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_Dashboard extends CI_Model
{

	public $limit;
	public $offset;

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	public function get_solved_tickets()
	{
		$query = $this->db->query("SELECT count(ticket_no) as solved_tickets from tb_ticket where ticket_status = 'Solved' union all SELECT count(ticket_no) as solved_tickets from tb_ticket_bulk where ticket_status = 'Solved' ");
		return $query->result();
	}

	public function get_pending_tickets()
	{
		$query = $this->db->query("SELECT count(ticket_no) as pending_tickets from tb_ticket where ticket_status = 'Pending' union all SELECT count(ticket_no) as pending_tickets from tb_ticket_bulk where ticket_status = 'Pending'");
		return $query->result();
	}

	public function get_enquiries()
	{
		$query = $this->db->query("SELECT count(ticket_no) as enquiries from tb_ticket where ticket_type = 'Complaint' and ticket_status = 'Pending' union all SELECT count(ticket_no) as enquiries from tb_ticket_bulk where ticket_type = 'Complaint' and ticket_status = 'Pending' ");
		return $query->result();
	}

	public function get_resend_requests()
	{
		$query = $this->db->query("SELECT count(t.ticket_no) as resend_requests from tb_ticket t inner join tb_resend r on t.ticket_no = r.ticket_no where t.ticket_type = 'Resend' and r.resend_status = 'Pending' union all SELECT count(bt.ticket_no) as resend_requests from tb_ticket_bulk bt inner join tb_resend_bulk br on bt.ticket_no = br.ticket_no where bt.ticket_type = 'Resend' and br.resend_status = 'Pending' ");
		return $query->result();
	}

	public function get_returned_copies()
	{
		$query = $this->db->query("SELECT count(id) as returned_copies from tb_return union all SELECT count(id) as returned_copies from tb_return_bulk");
		return $query->result();
	}

	public function get_total_tickets()
	{
		$query = $this->db->query("SELECT count(ticket_no) as total_tickets from tb_ticket union all SELECT count(ticket_no) as total_tickets from tb_ticket_bulk ");
		return $query->result();
	}

}
