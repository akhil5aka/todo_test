<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_Ticket extends CI_Model
{
	public $limit;
	public $offset;
	var $table = 'tb_ticket';
    var $column_order = array('ticket_no', 'sub_no','name','mobile','email'); //set column field database for datatable orderable
    var $column_search = array('ticket_no', 'sub_no','name','mobile','email'); //set column field database for datatable searchable
    var $order = array('id' => 'desc'); // default order

    public function __construct()
    {
    	parent::__construct();
    	$this->load->database();
    }

    public function isSubnoValid($sub_no)
    {
        $query = $this->db->query("SELECT sub_no from tb_main_subscriptions where sub_no = '$sub_no'");
        return $query->num_rows();
    }

    public function getSmsData()
    {
        $query = $this->db->query("SELECT
            sms.id,
            sms.ticket_no,
            c.mobile,
            c.name,
            s.sub_no
            from tb_ticket_sms sms
            inner join tb_ticket t on sms.ticket_no = t.ticket_no
            inner join tb_main_subscriptions s on t.sub_no = s.sub_no
            inner join tb_customer c on c.account_no = s.account_no
            where c.mobile != ''
            and sms.open_ticket_sms_status='Pending' ");
        return $query->result();
    }

    public function getSmsDataOfResend()
    {
        $query = $this->db->query("SELECT
            sms.id,
            sms.ticket_no,
            r.month_of_issue,
            r.year_of_issue,
            c.mobile,
            c.name,
            s.sub_no
            from tb_ticket_sms sms
            inner join tb_ticket t on sms.ticket_no = t.ticket_no
            inner join tb_resend r on t.ticket_no = r.ticket_no
            inner join tb_main_subscriptions s on t.sub_no = s.sub_no
            inner join tb_customer c on c.account_no = s.account_no
            where c.mobile != ''
            and sms.open_ticket_sms_status='Pending' ");
        return $query->result();
    }

    public function getCloseSmsData($ticket_no)
    {
        $query = $this->db->query("SELECT
            sms.id,
            sms.ticket_no,
            t.ticket_type,
            c.mobile,
            c.name,
            s.sub_no
            from tb_ticket_sms sms
            inner join tb_ticket t on sms.ticket_no = t.ticket_no
            inner join tb_main_subscriptions s on t.sub_no = s.sub_no
            inner join tb_customer c on c.account_no = s.account_no
            where t.ticket_no = '$ticket_no'
            and c.mobile != ''
            and t.ticket_status = 'Solved'
            and sms.close_ticket_sms_status='Pending' ");
        return $query->result();
    }

    public function updateSMSStatus($data)
    {
        $this->db->update_batch('tb_ticket_sms', $data, 'id');
        return $this->db->affected_rows();
    }

    public function getBulkSmsData()
    {
        $query = $this->db->query("SELECT
            sms.id,
            sms.ticket_no,
            c.mobile,
            c.name,
            s.sub_no
            from tb_bulk_ticket_sms sms
            inner join tb_ticket_bulk t on sms.ticket_no = t.ticket_no
            inner join tb_bulk_courier s on t.sub_no = s.sub_no
            inner join tb_customer c on c.account_no = s.account_no
            where c.mobile != ''
            and sms.open_ticket_sms_status='Pending' ");
        return $query->result();
    }

    public function getBulkSmsDataOfResend()
    {
        $query = $this->db->query("SELECT
            sms.id,
            sms.ticket_no,
            r.month_of_issue,
            r.year_of_issue,
            c.mobile,
            c.name,
            s.sub_no
            from tb_bulk_ticket_sms sms
            inner join tb_ticket_bulk t on sms.ticket_no = t.ticket_no
            inner join tb_resend_bulk r on t.ticket_no = r.ticket_no
            inner join tb_bulk_courier s on t.sub_no = s.sub_no
            inner join tb_customer c on c.account_no = s.account_no
            where c.mobile != ''
            and sms.open_ticket_sms_status='Pending' ");
        return $query->result();
    }

    public function getCloseBulkSmsData($ticket_no)
    {
        $query = $this->db->query("SELECT
            sms.id,
            sms.ticket_no,
            c.mobile,
            c.name,
            s.sub_no
            from tb_bulk_ticket_sms sms
            inner join tb_ticket_bulk t on sms.ticket_no = t.ticket_no
            inner join tb_bulk_courier s on t.sub_no = s.sub_no
            inner join tb_customer c on c.account_no = s.account_no
            where t.ticket_no = '$ticket_no'
            and c.mobile != ''
            and t.ticket_status = 'Solved'
            and sms.close_ticket_sms_status='Pending' ");
        return $query->result();
    }

    public function updateBulkSMSStatus($data)
    {
        $this->db->update_batch('tb_bulk_ticket_sms', $data, 'id');
        return $this->db->affected_rows();
    }

    public function save_new_ticket($data)
    {
    	$data = $this->db->escape_str($data);
    	$this->db->insert('tb_ticket',$data);
    	return $this->db->insert_id();
    }

    public function save_new_enquiry($data)
    {
        $data = $this->db->escape_str($data);
        $this->db->insert('tb_enquiries',$data);
        return $this->db->insert_id();
    }

    public function saveSmsData($data)
    {
        $data = $this->db->escape_str($data);
        $this->db->insert('tb_ticket_sms',$data);
        return $this->db->insert_id();
    }

    public function saveBulkSmsData($data)
    {
        $data = $this->db->escape_str($data);
        $this->db->insert('tb_bulk_ticket_sms',$data);
        return $this->db->insert_id();
    }
    function get_tickets($status)
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

    	$columnToOrder = "ticket_no";
    	$sortOrder = "desc";

    	$this->limit = $_POST['length'];
    	$this->offset = $_POST['start'];
    	$query = $this->db->query("SELECT t.ticket_no,DATE(t.received_date) received_date, s.sub_no,s.sub_status,c.name,c.email,c.mobile,l.language_name,a.state,t.ticket_type,t.ticket_status,a.phone
    		from tb_ticket t inner join tb_main_subscriptions s on t.sub_no = s.sub_no
    		inner join tb_customer c on s.account_no = c.account_no
    		inner join tb_languages l on s.language_id = l.language_id
    		inner join tb_address a on s.address_id = a.address_id
    		and(
    		t.ticket_no like '$searchKeys%' or
    		s.sub_no like '$searchKeys%' or
    		a.phone like '$searchKeys%' or
    		c.name like '$searchKeys%' or
    		c.mobile like '$searchKeys%' or
    		c.email like '$searchKeys%' or
    		l.language_name like '$searchKeys%' or
    		a.pincode like '$searchKeys%' or
    		t.ticket_status like '$searchKeys%' or
    		a.state like '$searchKeys%' or
    		DATE(t.received_date) like '$searchKeys%'
      ) order by ticket_status,ticket_no desc limit $this->limit offset $this->offset");

    	return $query->result();
    }

    public function count_all($status)
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
    		and(
    		t.ticket_no like '$searchKeys%' or
    		s.sub_no like '$searchKeys%' or
    		c.name like '$searchKeys%' or
    		c.mobile like '$searchKeys%' or
    		c.email like '$searchKeys%' or
    		l.language_name like '$searchKeys%' or
    		a.pincode like '$searchKeys%' or
    		a.state like '$searchKeys%' or
    		t.ticket_status like '$searchKeys%' or
    		DATE(t.received_date) like '$searchKeys%'
    		)
    		)
    		ss");
    	$ck = $query->result();
    	return $ck[0]->ckk;
    }

    public function get_ticket_details($ticket_no)
    {
    	$query = $this->db->query("SELECT
    		t.ticket_no,t.received_date,t.received_mode,t.ticket_type,t.ticket_status,t.description,t.resolution,t.updated_on,
    		s.sub_no,
    		c.name customer_name,c.mobile,c.email,
    		l.language_name,
    		e.name updated_by
    		FROM tb_ticket t
    		inner join tb_main_subscriptions s on t.ticket_no = '$ticket_no' and t.sub_no = s.sub_no
    		inner join tb_customer c on s.account_no = c.account_no
    		inner join tb_languages l on s.language_id = l.language_id
    		inner join tb_emp_login e on e.emp_id = t.updated_by
    		");
    	return $query->result();
    }

    public function get_ticket_trails($ticket_no)
    {
    	$query = $this->db->query("SELECT t.current_status,t.resolution,t.updated_on, e.name updated_by from tb_ticket_trails t,tb_emp_login e where t.ticket_no = '$ticket_no' and t.updated_by = e.emp_id order by updated_on desc");
    	return $query->result();
    }

    public function save_ticket_trail($data)
    {
    	$data = $this->db->escape_str($data);
    	$this->db->insert('tb_ticket_trails',$data);
    	return $this->db->affected_rows();
    }

    public function forward_ticket($data)
    {
        $data = $this->db->escape_str($data);
        $this->db->insert('tb_ticket_forward',$data);
        return $this->db->affected_rows();
    }

    public function get_dept_name($dept_id)
    {
     $query = $this->db->query("SELECT job_role from tb_job_role where job_role_id = '$dept_id'");
     return $query->result();
 }

 public function close_ticket($ticket_no)
 {
     $query = $this->db->query("UPDATE tb_ticket SET ticket_status = 'Solved' where ticket_no='$ticket_no' ");
     return true;
 }

 public function save_resend($data)
 {
  $data = $this->db->escape_str($data);
  $this->db->insert('tb_resend',$data);
  return $this->db->affected_rows();
}

public function get_resend_data($ticket_no)
{
    $query = $this->db->query("SELECT r.month_of_issue,r.year_of_issue,r.resend_status,l.docket_no,l.dispatched_on from tb_resend r left outer join tb_resend_labels l on r.ticket_no=l.ticket_no where r.ticket_no = '$ticket_no'
        ");
    return $query->result();
}

public function get_total_resend($sub_no)
{
    $query = $this->db->query("SELECT id from tb_resend where sub_no='$sub_no' ");
    return $query->num_rows();
}

public function update_ticket_status($ticket_status,$ticket_no)
{
    $query = $this->db->query("UPDATE tb_ticket set ticket_status = '$ticket_status' where ticket_no='$ticket_no'");
    return $this->db->affected_rows();
}

public function isPendingAnyWhere($ticket_no)
{
    $query = $this->db->query("SELECT id from tb_ticket_forward where ticket_no = '$ticket_no' and status = 'Pending'");
    return $query->num_rows();
}

public function isPendingAnyWhereElse($ticket_no,$dept)
{
    $query = $this->db->query("SELECT id from tb_ticket_forward where ticket_no = '$ticket_no' and status = 'Pending' and dept!='$dept'");
    return $query->num_rows();
}

public function get_ticket_no($forward_id)
{
    $query = $this->db->query("SELECT ticket_no,status from tb_ticket_forward where id='$forward_id'");
    return $query->result();

}

public function update_forward_status($forward_id)
{
    $query = $this->db->query("UPDATE tb_ticket_forward set status= 'Solved' where id='$forward_id' ");
    return $this->db->affected_rows();
}

public function isForwardedBefore($ticket_no,$dept)
{
    $query = $this->db->query("SELECT id from tb_ticket_forward where ticket_no='$ticket_no' and dept='$dept' and status='Pending'");
    return $query->num_rows();
}

    //Bulk

public function save_new_bulk_ticket($data)
{
    $data = $this->db->escape_str($data);
    $this->db->insert('tb_ticket_bulk',$data);
    return $this->db->insert_id();
}

public function save_bulk_resend($data)
{
  $data = $this->db->escape_str($data);
  $this->db->insert('tb_resend_bulk',$data);
  return $this->db->affected_rows();
}

public function get_total_resend_bulk($sub_no)
{
    $query = $this->db->query("SELECT id from tb_resend_bulk where sub_no='$sub_no' ");
    return $query->num_rows();
}

function get_bulk_tickets($status)
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

    $columnToOrder = "ticket_no";
    $sortOrder = "desc";

    $this->limit = $_POST['length'];
    $this->offset = $_POST['start'];
    $query = $this->db->query("SELECT t.ticket_no,DATE(t.received_date) received_date, s.sub_no,s.sub_status,c.name,c.email,c.mobile,a.state,t.ticket_type,t.ticket_status,a.phone
        from tb_ticket_bulk t inner join tb_bulk_courier s on t.sub_no = s.sub_no
        inner join tb_customer c on s.account_no = c.account_no
        inner join tb_address a on s.address_id = a.address_id
        and(
        t.ticket_no like '$searchKeys%' or
        s.sub_no like '$searchKeys%' or
        a.phone like '$searchKeys%' or
        c.name like '$searchKeys%' or
        c.mobile like '$searchKeys%' or
        c.email like '$searchKeys%' or
        a.pincode like '$searchKeys%' or
        t.ticket_status like '$searchKeys%' or
        a.state like '$searchKeys%' or
        DATE(t.received_date) like '$searchKeys%'
    ) order by ticket_status limit $this->limit offset $this->offset");

    return $query->result();
}

public function count_all_bulk_tickets($status)
{
    if(isset($_POST['search']['value']) && $_POST['search']['value'] > 1) {
        $_POST['search']['value']= $_POST['search']['value'];
    }
    $searchKeys = $_POST['search']['value'];
    $query = $this->db->query("SELECT count(*) ckk from
        (
        SELECT t.ticket_no,DATE(t.received_date) received_date, s.sub_no,c.name,c.email,c.mobile,a.state,t.ticket_type,t.ticket_status
        from tb_ticket_bulk t inner join tb_bulk_courier s on t.sub_no = s.sub_no
        inner join tb_customer c on s.account_no = c.account_no
        inner join tb_address a on s.address_id = a.address_id
        and(
        t.ticket_no like '$searchKeys%' or
        s.sub_no like '$searchKeys%' or
        c.name like '$searchKeys%' or
        c.mobile like '$searchKeys%' or
        c.email like '$searchKeys%' or
        a.pincode like '$searchKeys%' or
        a.state like '$searchKeys%' or
        t.ticket_status like '$searchKeys%' or
        DATE(t.received_date) like '$searchKeys%'
        )
        )
        ss");
    $ck = $query->result();
    return $ck[0]->ckk;
}

public function get_bulk_ticket_details($ticket_no)
{
    $query = $this->db->query("SELECT
        t.ticket_no,t.received_date,t.received_mode,t.ticket_type,t.ticket_status,t.description,t.resolution,t.updated_on,
        s.sub_no,
        c.name customer_name,c.mobile,c.email,
        e.name updated_by
        FROM tb_ticket_bulk t
        inner join tb_bulk_courier s on t.ticket_no = '$ticket_no' and t.sub_no = s.sub_no
        inner join tb_customer c on s.account_no = c.account_no
        inner join tb_emp_login e on e.emp_id = t.updated_by
        ");
    return $query->result();
}

public function get_bulk_resend_data($ticket_no)
{
    $query = $this->db->query("SELECT r.month_of_issue,r.year_of_issue,r.resend_status,l.docket_no,l.dispatched_on from tb_resend_bulk r left outer join tb_resend_labels_bulk l on r.ticket_no=l.ticket_no where r.ticket_no = '$ticket_no'
        ");
    return $query->result();
}

public function get_bulk_ticket_trails($ticket_no)
{
    $query = $this->db->query("SELECT t.description,t.resolution,t.updated_on,t.current_status, e.name updated_by from tb_bulk_ticket_trails t,tb_emp_login e where t.ticket_no = '$ticket_no' and t.updated_by = e.emp_id order by updated_on desc");
    return $query->result();
}

public function save_bulk_ticket_trail($data)
{
    $data = $this->db->escape_str($data);
    $this->db->insert('tb_bulk_ticket_trails',$data);
    return $this->db->affected_rows();
}

public function close_bulk_ticket($ticket_no)
{
    $query = $this->db->query("UPDATE tb_ticket_bulk SET ticket_status = 'Solved' where ticket_no='$ticket_no' ");
    return true;
}

public function isPendingAnyWhereBulk($ticket_no)
{
    $query = $this->db->query("SELECT id from tb_ticket_forward_bulk where ticket_no = '$ticket_no' and status = 'Pending'");
    return $query->num_rows();
}

public function isPendingAnyWhereElseBulk($ticket_no,$dept)
{
    $query = $this->db->query("SELECT id from tb_ticket_forward_bulk where ticket_no = '$ticket_no' and status = 'Pending' and dept!='$dept'");
    return $query->num_rows();
}

public function isForwardedBeforeBulk($ticket_no,$dept)
{
    $query = $this->db->query("SELECT id from tb_ticket_forward_bulk where ticket_no='$ticket_no' and dept='$dept' and status='Pending'");
    return $query->num_rows();
}


public function forward_ticket_bulk($data)
{
    $data = $this->db->escape_str($data);
    $this->db->insert('tb_ticket_forward_bulk',$data);
    return $this->db->affected_rows();
}

public function get_ticket_no_bulk($forward_id)
{
    $query = $this->db->query("SELECT ticket_no,status from tb_ticket_forward_bulk where id='$forward_id'");
    return $query->result();
}

public function update_forward_status_bulk($forward_id)
{
    $query = $this->db->query("UPDATE tb_ticket_forward_bulk set status= 'Solved' where id='$forward_id' ");
    return $this->db->affected_rows();
}

function get_enquiries()
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
    $query = $this->db->query("SELECT
        q.id,
        q.name customer_name,
        q.email,
        q.mobile,
        q.received_date,
        e.name emp_name
        from tb_enquiries q inner join tb_emp_login e on q.updated_by = e.emp_id
        where
        e.name like '$searchKeys%' or
        q.name like '$searchKeys%' or
        q.mobile like '$searchKeys%' or
        q.email like '$searchKeys%' order by q.updated_on desc limit $this->limit offset $this->offset");
    return $query->result();
}

public function get_enquiries_count()
{
 if(isset($_POST['search']['value']) && $_POST['search']['value'] > 1) {
    $_POST['search']['value']= $_POST['search']['value'];
}
$searchKeys = $_POST['search']['value'];
$query = $this->db->query("SELECT count(*) ckk from
    (
    SELECT
    q.id,
    q.name customer_name,
    q.email,
    q.mobile,
    q.received_date,
    e.name emp_name
    from tb_enquiries q inner join tb_emp_login e on q.updated_by = e.emp_id
    where
    e.name like '$searchKeys%' or
    q.name like '$searchKeys%' or
    q.mobile like '$searchKeys%' or
    q.email like '$searchKeys%'
    )
    ss");
$ck = $query->result();
return $ck[0]->ckk;
}

public function get_enquiry_details($enquiry_id)
{
    $query = $this->db->query("SELECT
    q.id,
    q.name customer_name,
    q.email,
    q.mobile,
    q.received_date,
    q.updated_by,
    q.description,
    e.name emp_name
    from tb_enquiries q
    inner join tb_emp_login e on q.updated_by = e.emp_id
    where q.id = '$enquiry_id'");
    return $query->result();
}

public function delete_enquiry($id)
{
    $query = $this->db->query("DELETE from tb_enquiries where id = '$id'");
    return $this->db->affected_rows();
}

public function update_enquiry($column,$new_value,$id)
{
    $new_value = $this->db->escape_str($new_value);
    $query = $this->db->query("UPDATE tb_enquiries set $column = '$new_value' where id = '$id' ");
    return 0;
}

}
