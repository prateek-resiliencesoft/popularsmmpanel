<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class perfect_money extends MX_Controller {
    public $tb_users;
    public $tb_transaction_logs;
    public $two_checkout;
    public $payment_type;
    public $currency_code;
    public $mode;

    public function __construct(){
        parent::__construct();
        $this->tb_users            = USERS;
        $this->tb_transaction_logs = TRANSACTION_LOGS;
        $this->payment_type		   = "perfect_money";
        $this->mode 			   = get_option("payment_environment", "");
        $this->currency_code       = (get_option("currency_code", "USD") == "")? 'USD' : get_option("currency_code", "");
//        $this->load->library("two_checkoutapi");
//        $this->two_checkout = new two_checkoutapi(get_option('2checkout_private_key',""), get_option('2checkout_seller_id',""), $this->mode);
    }

//    public function create_payment(){
//
//        $data = array(
//            "module"        => get_class($this),
//        );
//
//        $this->template->build('index', $data);
//    }

    /**
     *
     * Create payment
     *
     */
    public function response(){

//        print_r($_GET);
    }
    public function payment_success(){

//        print_r($_GET);
        if(isset($_POST['PAYER_ACCOUNT']) && isset($_POST['PAYMENT_BATCH_NUM']) && isset($_POST['PAYMENT_ID'])){

            unset_session("amount");
            $data = array(
                "ids" 				=> ids(),
                "uid" 				=> session("uid"),
                "type" 				=> $this->payment_type,
                "transaction_id" 	=> $_POST['PAYMENT_ID'],
                "amount" 	        =>$_POST['PAYER_ACCOUNT'],
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

