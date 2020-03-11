<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Notis Controller
 */
class Notis extends BE_Controller {

	/**
	 * Construt required variables
	 */
	function __construct() {

		parent::__construct( MODULE_CONTROL, 'NOTIS' );
	}

	/**
	* Load Notification Sending Form
	*/
	function index() {
		$this->data['action_title'] = "Push Notification";
		// get rows count
		$this->data['rows_count'] = $this->Noti->count_all_by( $conds );

		// get notimsgs
		$this->data['notimsgs'] = $this->Noti->get_all_by( $conds , $this->pag['per_page'], $this->uri->segment( 4 ) );

		parent::index();
	}

	/**
	 * Searches for the first match.
	 */
	function search() {
		

		// breadcrumb urls
		$this->data['action_title'] = get_msg( 'noti_search' );
		
		// condition with search term
		$conds = array( 'searchterm' => $this->searchterm_handler( $this->input->post( 'searchterm' )) );
		// no publish filter
		$conds['no_publish_filter'] = 1;

		// pagination
		$this->data['rows_count'] = $this->Noti->count_all_by( $conds );

		// search data
		$this->data['notimsgs'] = $this->Noti->get_all_by( $conds, $this->pag['per_page'], $this->uri->segment( 4 ) );
		
		// load add list
		parent::search();
	}

	/**
	 * Create new one
	 */
	function add() {

		// breadcrumb urls
		$this->data['action_title'] = get_msg( 'noti_add' );

		// call the core add logic
		parent::add();
	}

	/**
	* Sending Push Notification Message
	*/
	function push_message() { 

		if ( $this->input->server( 'REQUEST_METHOD' ) == "POST" ) {
			$message = htmlentities($this->input->post( 'message' ));

			$error_msg = "";
			$success_device_log = "";

			// Android Push Notification
			$devices = $this->Notitoken->get_all_by(array('os_type' => 'ANDROID'))->result();

			$device_ids = array();
			if ( count( $devices ) > 0 ) {
				foreach ( $devices as $device ) {
					$device_ids[] = $device->device_id;
				}
			}

			$status = $this->send_android_fcm( $device_ids, array( "message" => $message ));
			if ( !$status ) $error_msg .= "Fail to push all android devices <br/>";

			// // IOS Push Notification
			// $devices = $this->Notitoken->get_all_by(array('os_type' => 'IOS'))->result();
			
			// if ( count( $devices ) > 0 ) {
			// 	foreach ( $devices as $device ) {
			// 		if ( ! $this->send_ios_apns( $device->device_id, $message )) {
			// 			$error_msg .= "Fail to push ios device named ". $device->device_id ."<br/>";
			// 		} else {
			// 			$success_device_log .= " Device Id : " . $device->device_id . "<br>";
			// 		}
			// 	}
			// }
			// start the transaction
		$this->db->trans_start();
		$logged_in_user = $this->ps_auth->get_user_info();
		/** 
		 * Insert Notification Records 
		 */
		$data = array();

		// prepare noti name zawgyi
		if ( $this->has_data( 'message' )) {
			$data['message'] = $this->get_data( 'message' );
		}

		// prepare description zawgyi
		if ( $this->has_data( 'description' )) {
			$data['description'] = $this->get_data( 'description' );
		}

		$data['added_user_id'] = $logged_in_user->user_id;
		if($id == "") {
			//save
			$data['added_date'] = date("Y-m-d H:i:s");
		  } 
		// save notification
		if ( ! $this->Noti->save( $data, $id )) {
		// if there is an error in inserting user data,	

			// rollback the transaction
			$this->db->trans_rollback();

			// set error message
			$this->data['error'] = get_msg( 'err_model' );
			
			return;
		}
		/** 
		 * Upload Image Records 
		*/
	
		if ( !$id ) {
		// if id is false, this is adding new record

			if ( ! $this->insert_images( $_FILES, 'noti', $data['id'] )) {
			
			}

			
		}
			

			// commit the transaction
		if ( ! $this->check_trans()) {
        	
			// set flash error message
			$this->set_flash_msg( 'error', get_msg( 'err_model' ));
		} else {

			if ( $id ) {
			// if user id is not false, show success_add message
				
				//$this->set_flash_msg( 'success', get_msg( 'success_cat_edit' ));
			} else {
			// if user id is false, show success_edit message

				$this->set_flash_msg( 'success', get_msg( 'success_noti_add' ));
			}
		}

		}

		// $this->data['action_title'] = "Push Notification";
		redirect( $this->module_site_url());
	}

	/**
	* Sending Message From FCM For Android
	*/
	function send_android_fcm( $registatoin_ids, $message) 
    {
    	//Google cloud messaging GCM-API url
    	$url = 'https://fcm.googleapis.com/fcm/send';
    	$fields = array(
    	    'registration_ids' => $registatoin_ids,
    	    'data' => $message,
    	);
    	// Update your Google Cloud Messaging API Key
    	//define("GOOGLE_API_KEY", "AIzaSyCCwa8O4IeMG-r_M9EJI_ZqyybIawbufgg");
    	define("GOOGLE_API_KEY", $this->config->item( 'fcm_api_key' ));  	
    		
    	$headers = array(
    	    'Authorization: key=' . GOOGLE_API_KEY,
    	    'Content-Type: application/json'
    	);
    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_POST, true);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);	
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
    	$result = curl_exec($ch);				
    	if ($result === FALSE) {
    	    die('Curl failed: ' . curl_error($ch));
    	}
    	curl_close($ch);
    	
    	return $result;
    }


    /**
	* Sending Message From APNS For iOS
	*/
    function send_ios_apns($tokenId, $message) 
	{
		ini_set('display_errors','On'); 
		//error_reporting(E_ALL);
		// Change 1 : No braces and no spaces
		$deviceToken= $tokenId;
		//'fe2df8f5200b3eb133d84f73cc3ea4b9065b420f476d53ad214472359dfa3e70'; 
		// Change 2 : If any
		$passphrase = 'teamps'; 
		$ctx = stream_context_create();
		// Change 3 : APNS Cert File name and location.
		stream_context_set_option($ctx, 'ssl', 'local_cert', realpath('assets').'/apns/psnews_apns_cert.pem'); 
		stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
		// Open a connection to the APNS server
		$fp = stream_socket_client( 
		    'ssl://gateway.sandbox.push.apple.com:2195', $err,
		    $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
		if (!$fp)
		    exit("Failed to connect: $err $errstr" . PHP_EOL);
		// Create the payload body
		$body['aps'] = array(
		    'alert' => $message,
		    'sound' => 'default'
		    );
		// Encode the payload as JSON
		$payload = json_encode($body);
		// Build the binary notification
		$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
		// Send it to the server
		$result = fwrite($fp, $msg, strlen($msg));
		// Close the connection to the server
		fclose($fp);
		if (!$result) 
		    return false;

		return true;
	}

	/**
	 * Delete the record
	 * 1) delete notification
	 * 2) delete image from folder and table
	 * 3) check transactions
	 */
	function delete( $id ) {

		// start the transaction
		$this->db->trans_start();

		// check access
		$this->check_access( DEL );
		
		// delete categories and images
		if ( !$this->ps_delete->delete_noti( $id )) {

			// set error message
			$this->set_flash_msg( 'error', get_msg( 'err_model' ));

			// rollback
			$this->trans_rollback();

			// redirect to list view
			redirect( $this->module_site_url());
		}
			
		/**
		 * Check Transcation Status
		 */
		if ( !$this->check_trans()) {

			$this->set_flash_msg( 'error', get_msg( 'err_model' ));	
		} else {
        	
			$this->set_flash_msg( 'success', get_msg( 'success_noti_delete' ));
		}
		
		redirect( $this->module_site_url());
	}

	/**
	 * Determines if valid input.
	 *
	 * @return     boolean  True if valid input, False otherwise.
	 */
	function is_valid_input( $id = 0 ) 
	{

		return true;
	}

	
}