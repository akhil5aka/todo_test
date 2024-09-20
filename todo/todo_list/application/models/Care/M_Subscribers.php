<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_Subscribers extends CI_Model {
    public $limit;
    public $offset;
    var $table = 'tb_main_subscriptions';
    var $column_order = array('account_no', 'sub_no','name','mobile','email'); //set column field database for datatable orderable
    var $column_search = array('account_no', 'sub_no','name','mobile','email'); //set column field database for datatable searchable
    var $order = array('id' => 'desc'); // default order

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    private function _get_query()
    {
        $this->db->from($this->table);
        $i = 0;
        foreach ($this->column_search as $emp) // loop column
        {
            if(isset($_POST['search']['value']) && !empty($_POST['search']['value'])){
                $_POST['search']['value'] = $_POST['search']['value'];
            } else
            $_POST['search']['value'] = '';
        if($_POST['search']['value']) // if datatable send POST for search
        {
            if($i===0) // first loop
            {
                $this->db->group_start();
                $this->db->like(($emp), $_POST['search']['value']);
            }
            else
            {
                $this->db->or_like(($emp), $_POST['search']['value']);
            }

            if(count($this->column_search) - 1 == $i) //last loop
                $this->db->group_end(); //close bracket
            }
            $i++;
        }

        if(isset($_POST['order'])) // here order processing
        {
            $this->db->order_by($this->column_order[$_POST['order']['0']['column']], $_POST['order']['0']['dir']);
        }
        else if(isset($this->order))
        {
            $order = $this->order;
            $this->db->order_by(key($order), $order[key($order)]);
        }
    }

    function get_subscribers()
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
            c.account_no,
            s.sub_no,
            s.sub_status,
            l.language_name,
            c.name,
            c.mobile,
            c.email,
            a.address,
            a.city,
            a.state,
            a.country,
            a.pincode,
            DATE(s.date_from) date_from,
            DATE(s.date_to) date_to
            from tb_customer c, tb_main_subscriptions s, tb_dispatch_method d, tb_languages l ,tb_address a
            where s.account_no=c.account_no
            and s.dis_method=d.dis_id
            and s.language_id = l.language_id
            and s.address_id = a.address_id
            and(
            c.account_no like '$searchKeys%' or
            s.sub_no like '$searchKeys%' or
            c.name like '$searchKeys%' or
            c.mobile like '$searchKeys%' or
            c.email like '$searchKeys%' or
            l.language_name like '$searchKeys%' or
            a.pincode like '$searchKeys%' or
            a.state like '$searchKeys%' or
            a.city like '$searchKeys%' or
            DATE(s.date_from) like '$searchKeys%' or
            d.dis_method like '$searchKeys%'
        ) order by sub_status,sub_no desc limit $this->limit offset $this->offset");
        return $query->result();
    }

    public function count_all()
    {
     if(isset($_POST['search']['value']) && $_POST['search']['value'] > 1) {
        $_POST['search']['value']= $_POST['search']['value'];
    }
    $searchKeys = $_POST['search']['value'];
    $query = $this->db->query("SELECT count(*) ckk from
        (
        SELECT
        c.account_no,
        s.sub_no,
        l.language_name,
        c.name,
        c.mobile,
        c.email,
        a.address,
        a.city,
        a.state,
        a.country,
        a.pincode,
        DATE(s.date_from) date_from,
        DATE(s.date_to) date_to
        from tb_customer c,
        tb_main_subscriptions s,
        tb_dispatch_method d,
        tb_languages l,
        tb_address a
        where s.account_no=c.account_no
        and s.dis_method=d.dis_id
        and s.address_id = a.address_id
        and s.language_id = l.language_id
        and(
        c.account_no like '$searchKeys%' or
        s.sub_no like '$searchKeys%' or
        c.name like '$searchKeys%' or
        c.mobile like '$searchKeys%' or
        c.email like '$searchKeys%' or
        l.language_name like '$searchKeys%' or
        a.pincode like '$searchKeys%' or
        a.state like '$searchKeys%' or
        a.city like '$searchKeys%' or
        DATE(s.date_from) like '$searchKeys%' or
        d.dis_method like '$searchKeys%'
        )
        )
        ss");
    $ck = $query->result();
    return $ck[0]->ckk;
}

public function get_count_of_forwards_to_acc($dept)
{
    $query = $this->db->query("SELECT sum(cnt) cnt from (SELECT count(f.id) cnt from tb_ticket_forward f 
    inner join tb_ticket t on f.ticket_no = t.ticket_no 
    inner join tb_main_subscriptions ms on ms.sub_no=t.sub_no
   inner join tb_offices o on o.office_id=ms.office_id 

    where f.status='Pending' 
    and  f.dept = '$dept' 
    and o.exec_type_id=2
    union all
     SELECT count(fb.id) cnt from tb_ticket_forward_bulk fb 
     inner join tb_ticket_bulk tb on fb.ticket_no = tb.ticket_no 
     where fb.status='Pending' 
     and  fb.dept = '$dept') ss ");
    return $query->result();
}

public function get_count_of_sub_forwards()
{
    $query = $this->db->query("SELECT count(f.id) cnt from tb_ticket_forward f inner join tb_ticket t on f.ticket_no = t.ticket_no where f.status='Pending' and  f.dept = 'Subscription Management Team' ");
    return $query->result();
}

public function get_forwarded_tickets($status,$dept)
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
    $query = $this->db->query("SELECT t.ticket_no,DATE(t.received_date) received_date,f.forwarded_by,f.forwarded_on,f.status,f.id forward_id, s.sub_no,c.name,c.email,c.mobile,l.language_name,a.state,t.ticket_type,t.ticket_status,a.phone
        from tb_ticket t inner join tb_ticket_forward f on f.ticket_no = t.ticket_no
        inner join tb_main_subscriptions s on t.sub_no = s.sub_no
        inner join tb_customer c on s.account_no = c.account_no
        inner join tb_languages l on s.language_id = l.language_id
        inner join tb_address a on s.address_id = a.address_id
        where f.dept = '$dept'
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
        a.city like '$searchKeys%' or
        DATE(t.received_date) like '$searchKeys%'
    ) order by ticket_status limit $this->limit offset $this->offset");

    return $query->result();
}

public function count_all_forwarded_tickets($status,$dept)
{
    if(isset($_POST['search']['value']) && $_POST['search']['value'] > 1) {
        $_POST['search']['value']= $_POST['search']['value'];
    }
    $searchKeys = $_POST['search']['value'];
    $query = $this->db->query("SELECT count(*) ckk from
        (
        SELECT t.ticket_no,DATE(t.received_date) received_date,f.forwarded_by,f.forwarded_on, s.sub_no,c.name,c.email,c.mobile,l.language_name,a.state,t.ticket_type,t.ticket_status
        from tb_ticket t inner join tb_ticket_forward f on f.ticket_no = t.ticket_no
        inner join tb_main_subscriptions s on t.sub_no = s.sub_no
        inner join tb_customer c on s.account_no = c.account_no
        inner join tb_languages l on s.language_id = l.language_id
        inner join tb_address a on s.address_id = a.address_id
        where t.ticket_status!='Solved'
        and f.status = 'Pending'
        and f.dept = '$dept'
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
        a.city like '$searchKeys%' or
        DATE(t.received_date) like '$searchKeys%'
        )
        )
        ss");
    $ck = $query->result();
    return $ck[0]->ckk;
}
//bulk

public function get_forwarded_tickets_bulk($status,$dept)
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
    $query = $this->db->query("SELECT t.ticket_no,DATE(t.received_date) received_date,f.forwarded_by,f.forwarded_on,f.status,f.id forward_id, s.sub_no,c.name,c.email,c.mobile,a.state,t.ticket_type,t.ticket_status,a.phone
        from tb_ticket_bulk t inner join tb_ticket_forward_bulk f on f.ticket_no = t.ticket_no
        inner join tb_bulk_courier s on t.sub_no = s.sub_no
        inner join tb_customer c on s.account_no = c.account_no
        inner join tb_address a on s.address_id = a.address_id
        where f.dept = '$dept'
        and(
        t.ticket_no like '$searchKeys%' or
        s.sub_no like '$searchKeys%' or
        a.phone like '$searchKeys%' or
        c.name like '$searchKeys%' or
        c.mobile like '$searchKeys%' or
        c.email like '$searchKeys%' or
        a.pincode like '$searchKeys%' or
        a.city like '$searchKeys%' or
        t.ticket_status like '$searchKeys%' or
        a.state like '$searchKeys%' or
        DATE(t.received_date) like '$searchKeys%'
    ) order by ticket_status limit $this->limit offset $this->offset");

    return $query->result();
}

public function count_all_forwarded_tickets_bulk($status,$dept)
{
    if(isset($_POST['search']['value']) && $_POST['search']['value'] > 1) {
        $_POST['search']['value']= $_POST['search']['value'];
    }
    $searchKeys = $_POST['search']['value'];
    $query = $this->db->query("SELECT count(*) ckk from
        (
        SELECT t.ticket_no,DATE(t.received_date) received_date,f.forwarded_by,f.forwarded_on,f.status,f.id forward_id, s.sub_no,c.name,c.email,c.mobile,a.state,t.ticket_type,t.ticket_status
        from tb_ticket_bulk t inner join tb_ticket_forward_bulk f on f.ticket_no = t.ticket_no
        inner join tb_bulk_courier s on t.sub_no = s.sub_no
        inner join tb_customer c on s.account_no = c.account_no
        inner join tb_address a on s.address_id = a.address_id
        where f.dept = '$dept'
        and(
        t.ticket_no like '$searchKeys%' or
        s.sub_no like '$searchKeys%' or
        c.name like '$searchKeys%' or
        c.mobile like '$searchKeys%' or
        c.email like '$searchKeys%' or
        a.pincode like '$searchKeys%' or
        a.city like '$searchKeys%' or
        a.state like '$searchKeys%' or
        t.ticket_status like '$searchKeys%' or
        DATE(t.received_date) like '$searchKeys%'
        )
        )
        ss");
    $ck = $query->result();
    return $ck[0]->ckk;
}

public function get_count_of_forwards_bulk($dept)
{
    $query = $this->db->query("SELECT count(f.id) cnt from tb_ticket_forward_bulk f inner join tb_ticket t on f.ticket_no = t.ticket_no where f.status='Pending' and  f.dept = '$dept' ");
    return $query->result();
}
}
