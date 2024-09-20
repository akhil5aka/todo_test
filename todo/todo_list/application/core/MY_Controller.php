<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
	}
	public function gettime()
	{
		$datetimeFormat = 'Y_m_d_H_i_s';
		date_default_timezone_set('Asia/Kolkata');
		$date = new \DateTime();
		$times = $date->getTimestamp();
		$set = $date->setTimestamp($times);
		$time1 = $date->format($datetimeFormat);
		return $time1;
	}
	public function gettimenew()
	{
		$datetimeFormat = 'Y-m-d H:i:s';
		date_default_timezone_set('Asia/Kolkata');
		$date = new \DateTime();
		$times = $date->getTimestamp();
		$set = $date->setTimestamp($times);
		$time1 = $date->format($datetimeFormat);
		return $time1;
	}
	//renewal sms sending
	public function renewal_sms_to_mobile($sub_no, $new_date_from, $new_date_to, $mobile)
	{
		
		$content = 'Dear Subscriber, Your Sub No: ' . $sub_no . ' is renewed and valid from ' . $new_date_from . ' -' . $new_date_to . '. Our Helpline number is: 04652350001 -Team Impact';
		$msgText = rawurlencode($content);
		// $url = 'http://alerts.digimiles.in/sendsms/bulksms?username=di80-impact&password=digimile&type=0&dlr=1&destination=' . $mobile . '&source=IMPCTT&message=' . $msgText . '&entityid=1201162253605202920&tempid=1207162272635401246';
		// $sendSms = $this->smsSend($url);
		return true;

		
	}
	//renewal sms sending
	public function is_logged_in($dept)
	{
		if (!isset($_SESSION))
			session_start();
		if (isset($_SESSION['login_id'])) {
			if ($dept != $_SESSION['jobRoleId']) {
				return false;
			}
			return true;
		} else {
			$msg = urlencode(base64_encode("Your session has been closed. Please login to continue."));
			redirect('Login/index/' . $msg);
		}
	}
	public function purge_files()
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, 'https://api.cloudflare.com/client/v4/zones/914482d8dade31bfa2197c3b22a4bfd5/purge_cache');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"purge_everything\":true}");

		$headers = array();
		$headers[] = 'X-Auth-Email: pranavs@kyurius.tech';
		$headers[] = 'X-Auth-Key: 495cf351ea80952506c73c33f3839d9f59bfc';
		$headers[] = 'Content-Type: application/json';
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			echo 'Error:' . curl_error($ch);
		}
		curl_close($ch);
	}
	public function get_count()
	{
		$this->load->model("Subs/M_Shift_sub");
		$change_count = $this->M_Shift_sub->get_count_of_changes();
		return $change_count;
	}

	public function apply_scheduled()
	{
		$this->load->model("Subs/M_Shift_sub");
		$scheduled_data = $this->M_Shift_sub->get_scheduled_changes();
		if (sizeof($scheduled_data) > 0) {
			foreach ($scheduled_data as $sd) {
				$dispatched = $this->check_if_dispatched($sd);
				echo $dispatched;
				if ($dispatched != 'failed') {
					$old_sub_no = $sd->old_sub_no;
					$new_sub_no = $sd->new_sub_no;
					$activate_new_sub = $this->M_Shift_sub->activate_scheduled($old_sub_no, $new_sub_no, $dispatched);
				}
			}
		}
	}

	public function check_if_dispatched($sd)
	{
		$old_sub_no = $sd->old_sub_no;
		$new_sub_no = $sd->new_sub_no;
		$this->load->model("Subs/M_Shift_sub");

		$old_sub_data = $this->M_Shift_sub->get_sub_details($old_sub_no);
		$old_dis_id = $old_sub_data[0]->dis_id;
		$old_type_id = $old_sub_data[0]->type_id;
		$old_language_id = $old_sub_data[0]->language_id;
		$old_no_of_sent = $old_sub_data[0]->no_of_sent;


		$new_sub_data = $this->M_Shift_sub->get_sub_details($new_sub_no);
		$new_dis_id = $new_sub_data[0]->dis_id;
		$new_type_id = $new_sub_data[0]->type_id;
		$new_language_id = $new_sub_data[0]->language_id;

		$latest_labelled_month = $this->M_Shift_sub->latest_labelled_month_data();

		$month = $latest_labelled_month[0]->month_of_issue;
		$year = $latest_labelled_month[0]->year_of_issue;

		$is_dispatched = $this->M_Shift_sub->check_if_dispatched($old_dis_id, $old_type_id, $old_language_id, $month, $year, $new_dis_id, $new_type_id, $new_language_id);
		//var_dump($is_dispatched);

		if (sizeof($is_dispatched) > 1) {
			return $old_no_of_sent;
		} else {
			$is_label_to = $this->is_label_generated($new_dis_id, $new_type_id, $new_language_id);
			if ($is_label_to) {
				return "failed";
			} else {
				$cut_off_date = $this->get_cut_off_date($new_dis_id, $new_type_id, $new_language_id);
				$is_any_sub = $this->M_Shift_sub->check_if_any_sub_exist($new_dis_id, $new_type_id, $new_language_id, $cut_off_date);
				if (sizeof($is_any_sub) > 0) {
					return "failed";
				} else {
					return $old_no_of_sent;
				}
			}
			//return "failed";
		}
	}

	public function is_label_generated($dis_id, $type_id, $language_id)
	{
		$latest_labelled_month = $this->M_Shift_sub->latest_labelled_month_data();
		if (sizeof($latest_labelled_month) == 0) {
			return false;
		}

		$month = $latest_labelled_month[0]->month_of_issue;
		$year = $latest_labelled_month[0]->year_of_issue;

		$this->load->model("Subs/M_Shift_sub");
		$result = $this->M_Shift_sub->is_label_generated($dis_id, $type_id, $language_id, $month, $year);

		if ($result > 0) {
			return true;
		}
		return false;
	}

	public function get_cut_off_date($dispatch_method, $sub_type_id, $language_id)
	{
		$this->load->model("Subs/M_Generate_label");
		$current_month =  (int)date("m");
		$day = (int)date("d");
		$year = (int)date("Y");
		if ($day >= 25) {
			$year_of_issue = $year;
			if ($current_month != 12) {
				$month_of_issue = $current_month + 1;
				$cut_off_month = $month_of_issue - 1;
			} else {
				$month_of_issue = 1;
				$year_of_issue = $year + 1;
				$cut_off_month = 12;
			}
		} else {
			$month_of_issue = $current_month;
			$year_of_issue = $year;
			$current_year = date("Y");
			if ($current_month != 1) {
				$cut_off_month = $month_of_issue - 1;
			} else {
				$cut_off_month = 12;
				$year = $year - 1;
			}
			$is_label_generated = $this->M_Generate_label->is_label_generated($dispatch_method, $sub_type_id, $language_id, $current_year, $current_month);

			if ($is_label_generated) {
				$mode_data = $this->M_Generate_label->get_mode_data($dispatch_method, $sub_type_id, $language_id);
				$month_name = date('F', mktime(0, 0, 0, $current_month, 10));
				$dispatch_name = $mode_data[0]->dis_method;
				$type_name = $mode_data[0]->type_name;
				$language_name = $mode_data[0]->language_name;
				$msg = urlencode(base64_encode("Label of '$dispatch_name => $type_name => $language_name  for $month_name $current_year' has been already generated."));
				redirect("Subs/Generate_label/index/" . $msg);
			}
			$cut_off_date = "$year-$cut_off_month-25";
			$cut_off_date = date("Y-m-d", strtotime($cut_off_date));
		}
		return $cut_off_date;
	}

	public function get_scheduled_date()
	{
		$current_month =  (int)date("m");
		$day = (int)date("d");
		$year = (int)date("Y");
		if ($day >= 25) {
			$year_of_issue = $year; //new line
			if ($current_month != 12) {
				$month_of_issue = $current_month + 1;
				$cut_off_month = $month_of_issue - 1; //new line
			} else {
				$month_of_issue = 1;
				$year_of_issue = $year + 1;
				$cut_off_month = 12; //new line
			}
			$cut_off_date = "$year-$month_of_issue-$day";
			$cut_off_date = date("Y-m-d", strtotime($cut_off_date));
		} else {
			$month_of_issue = $current_month; //new line
			$year_of_issue = $year;
			$current_year = date("Y");
			if ($current_month != 1) //new line starts
			{
				$cut_off_month = $month_of_issue - 1;
			} else {
				$cut_off_month = 12;
				$year = $year - 1;
			} //new line ends
			$cut_off_date = "$year-$cut_off_month-25";
			$cut_off_date = date("Y-m-d", strtotime($cut_off_date));
		}
		return $cut_off_date;
	}
	public function smsSend($myXMLData)
	{
		$curl_url = curl_init();
		curl_setopt_array($curl_url, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_URL => $myXMLData,
			CURLOPT_USERAGENT => 'Codular Sample cURL Request'
		));
		$product_result = curl_exec($curl_url);
		return $product_result;
	}

	public function activate_scheduled()
	{
		$this->load->model("Subs/M_Shift_sub");
		$this->M_Shift_sub->activate_scheduled_subs();
	}
	public function sendNewSubSms($sub_no, $started_date, $expiry_date, $mobile, $language_name)
	{
		if ($language_name == 'CHILDREN') {
			$language_name = 'TELL ME MORE';
		} else if ($language_name == 'KIDS') {
			$language_name = 'APPLE BEES';
		} else {
			$language_name = 'KUTTY ULAKAM';
		}
		$content = 'Dear Subscriber, Welcome to ' . $language_name . ' Your Sub No is: ' . $sub_no . ' valid from ' . $started_date . ' - ' . $expiry_date . ' Our Helpline number is: 04652350001 -Team Impact';
		// $content='Dear Subscriber, Thanks for choosing '.$language_name.' Your Sub No is: '.$sub_no.' valid from '.$started_date.' - '.$expiry_date.' Our Helpline number is: 999988877 -Team Impact';
		$msgText = rawurlencode($content);
		// $url = 'http://alerts.digimiles.in/sendsms/bulksms?username=di80-impact&password=digimile&type=0&dlr=1&destination=' . $mobile . '&source=IMPCTT&message=' . $msgText . '&entityid=1201162253605202920&tempid=1207164863599889322';
		// $url='http://alerts.digimiles.in/sendsms/bulksms?username=di80-impact&password=digimile&type=0&dlr=1&destination=8848298309&source=IMPCTT&message='.$msgText.'&entityid=1201162253605202920&tempid=1207162324643386309';
		// $sendSms = $this->smsSend($url);
		return true;
	}
	protected function get_pending_tickets() //get tickets pending for > 3 days to notify admin
	{
		$this->load->model("Admin/M_Dashboard");
		$result = $this->M_Dashboard->get_pending_tickets();
		return $result;
	}

	public function auto_renew_subscriptions()
	{
		$this->load->model("DataEntry/M_Renew");
		$this->M_Renew->activate_renewal();
		$this->M_Renew->activate_shift_renewal();
	}

	public function update_sub_status_on_expiry() //Set sub status to 'Expired' after sending final subscription issue.
	{
		$this->load->model("Dispatch/M_Upload_docket");
		$saveExpireSmsData = $this->M_Upload_docket->saveExpireSmsData();
		$status = $this->M_Upload_docket->update_sub_status_on_expiry();
		$isConn = $this->is_connected();
		if ($isConn) {
			$this->sendExpiredSms();
		}
		if ($status) {
			$this->auto_renew_subscriptions();
		}
	}

	public function saveAndSendExpiringInSMSData($months)
	{
		$this->load->model("Dispatch/M_Upload_docket");
		$saveExpiringInSMSData = $this->M_Upload_docket->saveExpiringInSMSData($months);
		$isConn = $this->is_connected();
		if ($isConn) {
			$this->sendExpiringInSMS($months);
		}
	}

	public function saveAndSendBulkExpiringInSMSData($months)
	{
		$this->load->model("Dispatch/M_Upload_docket");
		$saveExpiringInSMSData = $this->M_Upload_docket->saveBulkExpiringInSMSData($months);
		$isConn = $this->is_connected();
		if ($isConn) {
			$this->sendBulkExpiringInSMS($months);
		}
	}

	public function update_bulk_sub_status_on_expiry() //Set sub status to 'Expired' after sending final subscription issue.
	{
		$this->load->model("Dispatch/M_Upload_docket");
		$saveExpireSmsData = $this->M_Upload_docket->saveBulkExpireSmsData();
		$status = $this->M_Upload_docket->update_bulk_sub_status_on_expiry();
		$isConn = $this->is_connected();
		if ($isConn) {
			$this->sendBulkExpiredSms();
		}
		if ($status) {
			$this->auto_renew_bulk_subscriptions();
		}
	}

	public function auto_renew_bulk_subscriptions()
	{
		$this->load->model("DataEntry/M_Bulk_renew");
		$this->M_Bulk_renew->activate_renewal();
	}

	public function get_count_of_forwards($dept)
	{
		$this->load->model("Care/M_Subscribers");
		$forwarded = $this->M_Subscribers->get_count_of_forwards_to_acc($dept);
		$forward_count = $forwarded[0]->cnt;
		return $forward_count;
	}

	public function get_count_of_sub_forwards()
	{
		$this->load->model("Care/M_Subscribers");
		$forwarded = $this->M_Subscribers->get_count_of_sub_forwards();
		$forward_count = $forwarded[0]->cnt;
		return $forward_count;
	}

	public function get_count_of_forwards_bulk($dept)
	{
		$this->load->model("Care/M_Subscribers");
		$forwarded = $this->M_Subscribers->get_count_of_forwards_bulk($dept);
		$forward_count = $forwarded[0]->cnt;
		return $forward_count;
	}

	public function sendDispatchSms()
	{
		// echo "string";
		$this->load->model("Dispatch/M_New_docket");
		$dataSet = $this->M_New_docket->getSmsData();
		// var_dump($dataSet);
		if (sizeof($dataSet) > 0) {
			// echo "string";
			$id = array();
			$msgTextStart = 'http://api.smscountry.com/SMSCWebservice_MultiMessages.asp?User=aolmagazine&passwd=guruji1958&mno_msg=';
			$msgTextMiddle = '';
			foreach ($dataSet as $key => $ds) {
				$id[] = array(
					'id' => $ds->id,
					'sms_status' => 'Sent'
				);
				$mobile = '91' . $ds->mobile;
				$senderId = 'RISMUK';
				$month = date('F', mktime(0, 0, 0, $ds->month_of_issue, 10));
				$year = $ds->year_of_issue;
				$csp_name = $ds->csp_name;
				$language_name = $ds->language_name;
				$name = $ds->name;
				$dispatched_on = date("d-m-Y", strtotime($ds->dispatched_on));
				$sub_no = $ds->sub_no;
				$docket_no = $ds->docket_no;
				if ($csp_name != "India Post" and $csp_name !== "Skip Now") {
					$csp_name .= " Courier";
				}
				if ($csp_name == "Skip Now") {
					$text = "Dear " . $name . ", Your Rishimukh " . $language_name . " Subscription (" . $sub_no . ") for " . $month . " " . $year . " has been dispatched through " . $dispatched_on;
				} else {
					$text = "Dear " . $name . ", Your Rishimukh " . $language_name . " Subscription (" . $sub_no . ") for " . $month . " " . $year . " has been dispatched through " . $csp_name . " through " . $dispatched_on;
				}

				if ($docket_no != '') {
					$text .= " Docket No. for tracking: " . $docket_no;
				}
				$msgTextMiddle .= $mobile . "^" . $text . "~";
			}
			// $msgText= $msgTextStart.rawurlencode($msgTextMiddle).'&sid=RISMUK&mtype=N&DR=Y';
			$post = $msgTextMiddle;
			$sendSms = $this->sendSms($post);
			// $sendSms = $this->sendSms($msgText);
			$this->M_New_docket->updateSMSStatus($id);
			return true;
		} else {
			// echo "No records";
			return false;
		}
	}



	public function sendNewSubSmsorg()
	{
		$this->load->model("DataEntry/M_Data_entry");
		$dataSet = $this->M_Data_entry->getSmsData();
		if (sizeof($dataSet) > 0) {
			$id = array();
			$msgTextStart = 'http://api.smscountry.com/SMSCWebservice_MultiMessages.asp?User=aolmagazine&passwd=guruji1958&mno_msg=';
			$msgTextMiddle = '';
			foreach ($dataSet as $key => $ds) {
				$id[] = array(
					'id' => $ds->id,
					'sms_status' => 'Sent'
				);
				$mobile = '91' . $ds->mobile;
				$name = $ds->name;
				$language_name = $ds->language_name;
				$started_date = $ds->date_from;
				$day = date("d", strtotime($started_date));

				$month = date("m", strtotime($started_date));
				$year = date("Y", strtotime($started_date));
				$date = mktime(0, 0, 0, $month, 1, $year);
				if ($day >= 25) {
					$started_date = strftime('%B %Y', strtotime('+2 month', $date));
				} else {
					$started_date = strftime('%B %Y', strtotime('+1 month', $date));
				}

				$expiry_date = $ds->date_to;
				$day = date("d", strtotime($expiry_date));
				$month = date("m", strtotime($expiry_date));
				$year = date("Y", strtotime($expiry_date));

				$date = mktime(0, 0, 0, $month, 1, $year);
				if ($day >= 25) {
					$expiry_date = strftime('%B %Y', strtotime('+1 month', $date));
				} else {
					$month = date("F", strtotime($expiry_date));
					$year = date("Y", strtotime($expiry_date));
					$expiry_date = $month . " " . $year;
				}
				$senderId = 'RISMUK';
				$sub_no = $ds->sub_no;
				$text = "Dear " . $name . ", Thank you for subscribing to Rishimukh " . $language_name . ". Your Subscription Id. (" . $sub_no . ") starts " . $started_date . " Ends " . $expiry_date;

				$msgTextMiddle .= $mobile . "^" . $text . "~";
			}
			$msgText = $msgTextStart . rawurlencode($msgTextMiddle) . '&sid=RISMUK&mtype=N&DR=Y';
			$sendSms = $this->sendSms($msgText);
			$this->M_Data_entry->updateSMSStatus($id);
			return true;
		} else {
			return false;
		}
	}

	public function sendNewBulkSubSms()
	{
		$this->load->model("DataEntry/M_Bulk_entry");
		$dataSet = $this->M_Bulk_entry->getSmsData();
		if (sizeof($dataSet) > 0) {
			$id = array();
			$msgTextStart = 'http://api.smscountry.com/SMSCWebservice_MultiMessages.asp?User=aolmagazine&passwd=guruji1958&mno_msg=';
			$msgTextMiddle = '';
			foreach ($dataSet as $key => $ds) {
				$id[] = array(
					'id' => $ds->id,
					'sms_status' => 'Sent'
				);
				$mobile = '91' . $ds->mobile;
				$name = $ds->name;
				$text = $ds->msg;
				$senderId = 'RISMUK';
				$sub_no = $ds->sub_no;

				$msgTextMiddle .= $mobile . "^" . $text . "~";
			}
			// $msgText= $msgTextStart.rawurlencode($msgTextMiddle).'&sid=RISMUK&mtype=N&DR=Y';
			// $sendSms = $this->sendSms($msgText);
			$post = $msgTextMiddle;
			$sendSms = $this->sendSms($post);
			$this->M_Bulk_entry->updateSMSStatus($id);
			return true;
		} else {
			return false;
		}
	}

	public function sendExpiredSms()
	{
		$this->load->model("Dispatch/M_Upload_docket");
		$dataSet = $this->M_Upload_docket->getExpiredSmsData();
		if (sizeof($dataSet) > 0) {
			$id = array();
			$msgTextStart = 'http://api.smscountry.com/SMSCWebservice_MultiMessages.asp?User=aolmagazine&passwd=guruji1958&mno_msg=';
			$msgTextMiddle = '';
			foreach ($dataSet as $key => $ds) {
				$id[] = array(
					'id' => $ds->id,
					'sms_status' => 'Sent'
				);
				$mobile = '91' . $ds->mobile;
				$senderId = 'RISMUK';
				$sub_no = $ds->sub_no;
				$name = $ds->name;
				$text = "Dear " . $name . ", Your Rishimukh Subscription Id. (" . $sub_no . ") has been expired. To Resubscribe, log onto http://www.rishimukh.org/subscribe/ or Call +91 8861324646";
				$msgTextMiddle .= $mobile . "^" . $text . "~";
			}
			// $msgText= $msgTextStart.rawurlencode($msgTextMiddle).'&sid=RISMUK&mtype=N&DR=Y';
			$isConn = $this->is_connected();
			if ($isConn) {
				$post = $msgTextMiddle;
				$sendSms = $this->sendSms($post);
				$this->M_Upload_docket->updateExpiredSMSStatus($id);
			}
			return true;
		} else {
			return false;
		}
	}

	public function sendBulkExpiredSms()
	{
		$this->load->model("Dispatch/M_Upload_docket");
		$dataSet = $this->M_Upload_docket->getBulkExpiredSmsData();
		if (sizeof($dataSet) > 0) {
			$id = array();
			$msgTextStart = 'http://api.smscountry.com/SMSCWebservice_MultiMessages.asp?User=aolmagazine&passwd=guruji1958&mno_msg=';
			$msgTextMiddle = '';
			foreach ($dataSet as $key => $ds) {
				$id[] = array(
					'id' => $ds->id,
					'sms_status' => 'Sent'
				);
				$mobile = '91' . $ds->mobile;
				$senderId = 'RISMUK';
				$sub_no = $ds->sub_no;
				$name = $ds->name;
				$language_name = $ds->language_name;
				$copies = $ds->no_of_copies;
				$text = "Dear " . $name . ", " . $copies . " copies of " . $language_name . " in your Rishimukh Subscription Id. (" . $sub_no . ") has been expired. To Resubscribe, log onto http://www.rishimukh.org/subscribe/ or Call +91 8861324646";
				$msgTextMiddle .= $mobile . "^" . $text . "~";
			}
			// $msgText= $msgTextStart.rawurlencode($msgTextMiddle).'&sid=RISMUK&mtype=N&DR=Y';
			$isConn = $this->is_connected();
			if ($isConn) {
				$post = $msgTextMiddle;
				$sendSms = $this->sendSms($post);
				$this->M_Upload_docket->updateBulkExpiredSMSStatus($id);
			}
			return true;
		} else {
			return false;
		}
	}

	public function sendExpiringInSMS($months)
	{
		$this->load->model("Dispatch/M_Upload_docket");
		$dataSet = $this->M_Upload_docket->getExpiringInSmsData($months);
		if (sizeof($dataSet) > 0) {
			$id = array();
			$msgTextStart = 'http://api.smscountry.com/SMSCWebservice_MultiMessages.asp?User=aolmagazine&passwd=guruji1958&mno_msg=';
			$msgTextMiddle = '';
			foreach ($dataSet as $key => $ds) {
				$id[] = array(
					'id' => $ds->id,
					'sms_status' => 'Sent'
				);
				$mobile = '91' . $ds->mobile;
				$senderId = 'RISMUK';
				$sub_no = $ds->sub_no;
				$name = $ds->name;
				$expiry_date = $ds->date_to;
				$day = date("d", strtotime($expiry_date));
				$month = date("m", strtotime($expiry_date));
				$year = date("Y", strtotime($expiry_date));

				$date = mktime(0, 0, 0, $month, 1, $year);
				if ($day >= 25) {
					$expiry_date = strftime('%B %Y', strtotime('+1 month', $date));
				} else {
					$month = date("F", strtotime($expiry_date));
					$year = date("Y", strtotime($expiry_date));
					$expiry_date = $month . " " . $year;
				}
				$text = "Dear " . $name . ", Your Rishimukh Subscription Id. (" . $sub_no . ") will expire in " . $expiry_date . " To Resubscribe, log onto http://www.rishimukh.org/subscribe/ or Call +91 8861324646";
				$msgTextMiddle .= $mobile . "^" . $text . "~";
			}
			// $msgText= $msgTextStart.rawurlencode($msgTextMiddle).'&sid=RISMUK&mtype=N&DR=Y';
			$isConn = $this->is_connected();
			if ($isConn) {
				$post = $msgTextMiddle;
				$sendSms = $this->sendSms($post);
				$this->M_Upload_docket->updateExpiringInSMSStatus($months, $id);
			}
			return true;
		} else {
			return false;
		}
	}

	public function sendBulkExpiringInSMS($months)
	{
		$this->load->model("Dispatch/M_Upload_docket");
		$dataSet = $this->M_Upload_docket->getBulkExpiringInSmsData($months);
		if (sizeof($dataSet) > 0) {
			$id = array();
			$msgTextStart = 'http://api.smscountry.com/SMSCWebservice_MultiMessages.asp?User=aolmagazine&passwd=guruji1958&mno_msg=';
			$msgTextMiddle = '';
			foreach ($dataSet as $key => $ds) {
				$id[] = array(
					'id' => $ds->id,
					'sms_status' => 'Sent'
				);
				$mobile = '91' . $ds->mobile;
				$senderId = 'RISMUK';
				$sub_no = $ds->sub_no;
				$name = $ds->name;
				$language_name = $ds->language_name;
				$copies = $ds->no_of_copies;
				$expiry_date = $ds->date_to;
				$day = date("d", strtotime($expiry_date));
				$month = date("m", strtotime($expiry_date));
				$year = date("Y", strtotime($expiry_date));

				$date = mktime(0, 0, 0, $month, 1, $year);
				if ($day >= 25) {
					$expiry_date = strftime('%B %Y', strtotime('+1 month', $date));
				} else {
					$month = date("F", strtotime($expiry_date));
					$year = date("Y", strtotime($expiry_date));
					$expiry_date = $month . " " . $year;
				}
				$text = "Dear " . $name . ",\r\n" . $copies . " copies of " . $language_name . " magazines in your Rishimukh Subscription Id (" . $sub_no . ") will expire in " . $expiry_date . " To Resubscribe, sms RENEW to 8861324646 to get a call back for Renewal";
				$msgTextMiddle .= $mobile . "^" . $text . "~";
			}
			// $msgText= $msgTextStart.rawurlencode($msgTextMiddle).'&sid=RISMUK&mtype=N&DR=Y';
			$isConn = $this->is_connected();
			if ($isConn) {
				$post = $msgTextMiddle;
				$sendSms = $this->sendSms($post);
				$this->M_Upload_docket->updateBulkExpiringInSMSStatus($months, $id);
			}
			return true;
		} else {
			return false;
		}
	}

	public function sendSms($post)
	{
	}

	public function sendSmsOld($myXMLData)
	{
		$ch = curl_init();
		if (!$ch) {
			die("Couldn't initialize a cURL handle");
		}
		$ret = curl_setopt($ch, CURLOPT_URL, $myXMLData);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Length: 0'));
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		$ret = curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$curlresponse = curl_exec($ch); // execute
		if (curl_errno($ch))
			echo 'curl error : ' . curl_error($ch);
		if (empty($ret)) {
			// some kind of an error happened
			die(curl_error($ch));
			curl_close($ch); // close cURL handler
		} else {
			$info = curl_getinfo($ch);
			curl_close($ch); // close cURL handler
			echo $curlresponse; //echo "Message Sent Succesfully" ;
		}
	}

	// public function sendSmsolddd($myXMLData)
	// {
	// 	// $url = 'http://www.smscountry.com/SaveMultiXMLJobData.asp';
	// 	// $post_data = array('XML_DATA' => $myXMLData);
	// 	// // var_dump($post_data);
	// 	// $stream_options = array(
	// 	// 	'http' => array(
	// 	// 		'method'  => 'POST',
	// 	// 		'header'  => 'Content-type: application/x-www-form-urlencoded' . "\r\n",
	// 	// 		'content' =>  http_build_query($post_data)));

	// 	// $context  = stream_context_create($stream_options);
	// 	// $response = file_get_contents($url, null, $context);
	// 	$response = file_get_contents($myXMLData);
	// }

	public function is_connected()
	{
		$connected = @fsockopen("www.smscountry.com", 80);
		if ($connected) {
			$is_conn = true;
			fclose($connected);
		} else {
			$is_conn = false;
		}
		return $is_conn;
	}


	public function get_offices()
	{
		$this->load->model("Admin/M_Offices");
		$offices = $this->M_Offices->get_all_offices();
		return $offices;
	}

	public function get_my_office_id($emp_id)
	{
		$this->load->model("Admin/M_Offices");
		$emp_data = $this->M_Offices->get_my_office_id($emp_id);
		$office_id = $emp_data[0]->office_id;
		return $office_id;
	}

	public function sendDispatchEmail()
	{
		$this->load->model("Admin/M_Offices");
		$dataSet = $this->M_Offices->getEmailData();
		var_dump($dataSet);
		if (sizeof($dataSet) > 0) {
			$id = array();
			foreach ($dataSet as $key => $ds) {
				$id[] = array(
					'id' => $ds->id,
					'email_status' => 'Sent'
				);
				$data['email'] = $ds->email;
				// $senderId = 'RISMUK';
				$data['sub_no'] = $ds->sub_no;
				$data['name'] = $ds->name;
				$data['language_name'] = $ds->language_name;
				$month = date('F', mktime(0, 0, 0, $ds->month_of_issue, 10));
				$year = $ds->year_of_issue;
				$data['month'] = $month;
				$data['year'] = $year;
				$data['date_of_sent'] = date("d-m-Y", strtotime($ds->dispatched_on));
				// $data['date'] = mktime( 0, 0, 0, $month, 1, $year );
				// $text = "Dear ".$name.", Your AppleBees Subscription (".$sub_no.") for ".$month." ".$year." has been dispatched.";
			}
			$view = $this->load->view('Dispatch/dispatched_post_mail', $data, true);
			echo $view;
			$this->send_email($view);
			// $sendSms = $this->sendEmail($view);
			// $this->M_Upload_docket->updateBulkExpiringInSMSStatus($months,$id);

			return true;
		} else {
			return false;
		}
	}

	public function send_email($view)
	{
		$config = array(
			'protocol' => 'smtp',
			'smtp_host' => 'ssl://smtp.transmail.com',
			'smtp_port' => 465,
			'smtp_user' => 'emailapikey',
			'smtp_pass' => 'wSsVR61+/xSiCqp6nTOqJu86kV8HB1/zFxt7igekuSKuT/2W/McznkbOVg+iSKAdRWE6EjQaorx8kR0JgTQGiNsunAsBXiiF9mqRe1U4J3x17qnvhDzKWWlckBOMLI0NwgRjn2JmG8Eq+g==',
			'mailtype'  => 'html',
			'charset'   => 'iso-8859-1'
		);
		$this->load->library('email', $config);
		$this->email->set_newline("\r\n");
		$this->email->from('admin@applebeesmagazine.com', 'AppleBees');
		$this->email->to('soorajkr580@gmail.com');
		$this->email->subject('AppleBees Magazine');
		$this->email->message($view);
		$this->email->send();

		echo $this->email->print_debugger();
	}
	public function date_function()
	{
		$datetimeFormat = 'Y:m:d H:i:s';
		date_default_timezone_set('Asia/Kolkata');
		$dates = new \DateTime();
		$timestamp = $dates->getTimestamp();
		$dates = $dates->setTimestamp($timestamp);
		$date = $dates->format($datetimeFormat);
		return $date;
	}

	public function get_count_pwd()
	{
		$this->load->model("Subs/M_view_password_requset");
		$change_count = $this->M_view_password_requset->get_count_of_pwd();
		return $change_count;
	}
	public function send_password_sms_to_mobile($mobile, $pwd)
	{
		$content = 'Thank you for choosing iMaze. Your Username is ' . $mobile . ' Password is ' . $pwd . ' -Team Impact';
		$msgText = rawurlencode($content);
		// $url = 'http://alerts.digimiles.in/sendsms/bulksms?username=di80-impact&password=digimile&type=0&dlr=1&destination=' . $mobile . '&source=IMPCTT&message=' . $msgText . '&entityid=1201162253605202920&tempid=1207165656531804376';
		// $sendSms = $this->smsSend($url);
		return true;
	}

	public function send_forgot_password_otp_mobile($mobile, $pwd)
	{
		$content = 'Your OTP to reset the password is ' . $pwd . '. -Team Impact';
		$msgText = rawurlencode($content);
		// $url = 'http://alerts.digimiles.in/sendsms/bulksms?username=di80-impact&password=digimile&type=0&dlr=1&destination=' . $mobile . '&source=IMPCTT&message=' . $msgText . '&entityid=1201162253605202920&tempid=1207165656543083709';
		// $sendSms = $this->smsSend($url);
		return true;
	}

	public function send_ibees_password_sms_to_mobile($mobile, $pwd)
	{
		$content = 'Thank you for choosing iBees. Your Username is ' . $mobile . ' Password is ' . $pwd . ' -Team Impact';
		$msgText = rawurlencode($content);
		$url = 'http://alerts.digimiles.in/sendsms/bulksms?username=di80-impact&password=digimile&type=0&dlr=1&destination=' . $mobile . '&source=IMPCTT&message=' . $msgText . '&entityid=1201162253605202920&tempid=1207165656531804376';
		$sendSms = $this->smsSend($url);
		return true;
	}

	public function send_ibees_email($email, $new_password)
	{
		$config = array(
			'protocol' => 'smtp',
			'smtp_host' => 'ssl://smtp.googlemail.com',
			'smtp_port' => 465,
			'smtp_user' => 'testingktsemail@gmail.com',
			'smtp_pass' => 'krqpevbtfjohtuvr',
			'mailtype'  => 'html',
			'charset'   => 'iso-8859-1'
		);
		$this->load->library('email', $config);
		$this->email->set_newline("\r\n");
		$this->email->from('testingktsemail@gmail.com', 'AppleBees');
		$this->email->to($email);
		$this->email->subject('Ibees login details');
		$this->email->message("Your user name is $email and password is $new_password.");
		$this->email->send();

		echo $this->email->print_debugger();
	}

	// public function sendMail($mail_id, $otp)
	// {


	//     //   $data['data'] = $dat;


	//     $config = array(
	//         'protocol' => 'smtp',
	//         'smtp_host' => 'ssl://smtp.googlemail.com',
	//         'smtp_port' => 465,
	//         'smtp_user' => "testingktsemail@gmail.com",
	//         'smtp_pass' => "krqpevbtfjohtuvr",
	//         'mailtype'  => 'html',
	//         'charset'   => 'iso-8859-1',
	//         'newline' => "\r\n"
	//     );
	//     $this->email->initialize($config);
	//     $this->load->library('email', $config);
	//     $this->email->set_newline("\r\n");

	//     $this->email->from('testingktsemail@gmail.com', '');

	//     $this->email->to("akhilkumaraka5@gmail.com");

	//     $view = "customer_quote_templates";

	//     //   $body = $this->load->view($view, $data, true);

	//     $this->email->message("This is your otp  to change password  $otp ");


	//     $result = $this->email->send(FALSE);


	//     return true;
	// }


}
