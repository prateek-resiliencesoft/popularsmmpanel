<?php
defined('BASEPATH') OR exit('No direct script access allowed');
 
class add_funds extends MX_Controller {
	public $tb_users;
	public $tb_transaction_logs;
	public $module_name;
	public $module_icon;

	public function __construct(){
		parent::__construct();
		$this->load->model(get_class($this).'_model', 'model');
		$this->tb_users            = USERS;
		$this->tb_transaction_logs = TRANSACTION_LOGS;
	}

	public function index(){
		$data = array(
			"module"        => get_class($this),
		);

		$this->template->build('index', $data);
	}

	public function process(){
		$amount = post("amount");
		$agree  = post("agree");
		$payment_method = post('payment_method');

		if ($amount  == "") {
			ms(array(
				"status"  => "error",
				"message" => lang("amount_is_required"),
			));
		}

		if ($amount  < 0) {
			ms(array(
				"status"  => "error",
				"message" => lang("amount_must_be_greater_than_zero"),
			));
		}

		/*----------  Get Min ammout  ----------*/
		$min_ammount = get_option("payment_transaction_min");
//		$min_ammount = get_option($payment_method."_payment_transaction_min");
//        echo $min_ammount;
//        die();
		if ($min_ammount < 0 || $min_ammount == "") {
			$min_ammount = get_option('payment_transaction_min');
		}

		if ($amount  < $min_ammount) {
			ms(array(
				"status"  => "error",
				"message" => lang("minimum_amount_is")." ".$min_ammount,
			));
		}

		if (!$agree) {
			ms(array(
				"status"  => "error",
				"message" => lang("you_must_confirm_to_the_conditions_before_paying")
			));
		}
		
		$transaction_fee = 0;
		if ($payment_method != "") {
             $payment_method;
			 $transaction_fee = get_option($payment_method."_chagre_fee", 4);
		}
		
		 $total_amount = $amount + (($amount*$transaction_fee)/100);


		if (in_array($payment_method, ['coinbase', 'hesabe'])) {
			$data = array(
				"module"             => get_class($this),
				"amount"             => $total_amount,
			);

			$payment_module = new $payment_method();
			$payment_module->create_payment($data);
		}else{
			set_session("real_amount", $amount);
			set_session("amount", (float)$total_amount);
			ms(array(
				"status" => "success",
				"message" => lang("processing_"),
			));
		}

	}



	public function two_checkout_form(){
		$data = array(
			"module"        => get_class($this),
			"amount"        => session('amount'),
		);
		$this->template->build('2checkout_form', $data);
	}

    public function perfectmoney(){
        $data = array(
            "module"        => get_class($this),
//            "module"        => "perfect_money",
            "amount"        => session('amount'),
            "uid"        => session('uid')
        );
        $this->template->build('perfect_money', $data);
    }

	public function stripe_form(){
		$data = array(
			"module"        => get_class($this),
			"amount"        => session('amount'),
		);
		$this->template->build('stripe_form', $data);
	}

	public function success(){
		$id = session("transaction_id");
		$transaction = $this->model->get("*", $this->tb_transaction_logs, "id = '{$id}' AND uid ='".session('uid')."'");
		if (!empty($transaction)) {
			$data = array(
				"module"        => get_class($this),
				"transaction"   => $transaction,
			);
			unset_session("transaction_id");
			$this->template->build('payment_successfully', $data);
		}else{
			redirect(cn("add_funds"));
		}
	}

	public function unsuccess(){
		$data = array(
			"module"        => get_class($this),
		);
		$this->template->build('payment_unsuccessfully', $data);
	}



    public function payment_success(){

//        print_r($_GET);
        if(isset($_POST['PAYER_ACCOUNT']) && isset($_POST['PAYMENT_BATCH_NUM']) && isset($_POST['PAYMENT_ID'])){

            unset_session("amount");
            $data = array(
                "ids" 				=> ids(),
                "uid" 				=> session("uid"),
                "type" 				=> "perfect_money",
                "transaction_id" 	=> $_POST['PAYMENT_BATCH_NUM'],
                "amount" 	        =>$_POST['PAYMENT_AMOUNT'],
                "created" 			=> NOW,
            );

            $this->db->insert($this->tb_transaction_logs, $data);
            $transaction_id = $this->db->insert_id();

            /*----------  Add funds to user balance  ----------*/
            $user_balance = get_field($this->tb_users, ["id" => session("uid")], "balance");
            $user_balance += session('real_amount');
            $this->db->update($this->tb_users, ["balance" => $user_balance], ["id" => session("uid")]);
            unset_session("real_amount");

            /*----------  Send payment notification email  ----------*/
            if (get_option("is_payment_notice_email", '')) {
                $CI = &get_instance();
                if(empty($CI->payment_model)){
                    $CI->load->model('model', 'payment_model');
                }
                $check_send_email_issue = $CI->payment_model->send_email(get_option('email_payment_notice_subject', ''), get_option('email_payment_notice_content', ''), session('uid'));
                if($check_send_email_issue){
                    ms(array(
                        "status" => "error",
                        "message" => $check_send_email_issue,
                    ));
                }
            }
            set_session("transaction_id", $transaction_id);
            redirect(cn("add_funds/success"));

        }else{
            redirect(cn("add_funds/unsuccess"));
        }
    }


    public function payment_failed(){
        redirect(cn("add_funds/unsuccess"));
    }

}