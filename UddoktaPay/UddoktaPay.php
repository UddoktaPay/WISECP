<?php
class UddoktaPay
{
    public $checkout_id, $checkout;
    public $name, $commission = true;
    public $config = [], $lang = [], $page_type = "in-page", $callback_type = "server-sided";
    public $payform = false;

    function __construct()
    {
        $this->config     = Modules::Config("Payment", __CLASS__);
        $this->lang       = Modules::Lang("Payment", __CLASS__);
        $this->name       = __CLASS__;
        $this->payform   = __DIR__ . DS . "pages" . DS . "payform";
    }

    public function get_auth_token()
    {
        $syskey = Config::get("crypt/system");
        $token  = md5(Crypt::encode("UddoktaPay-Auth-Token=" . $syskey, $syskey));
        return $token;
    }

    public function set_checkout($checkout)
    {
        $this->checkout_id = $checkout["id"];
        $this->checkout    = $checkout;
    }


    public function cid_convert_code($id = 0)
    {
        Helper::Load("Money");
        $currency   = Money::Currency($id);
        if ($currency) return $currency["code"];
        return false;
    }

    public function get_ip()
    {
        if (isset($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        } elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else {
            $ip = $_SERVER["REMOTE_ADDR"];
        }
        return $ip;
    }

    public function get_fields($successful = '', $failed = '')
    {

        $checkout_data          = $this->checkout["data"];
        $user_data              = $checkout_data["user_data"];
        $callback_url           = Controllers::$init->CRLink("payment", ['UddoktaPay', $this->get_auth_token(), 'callback']);

        $amount = number_format($checkout_data["total"], 2, '.', '');
        $currency = $this->cid_convert_code($checkout_data["currency"]);
        $usd_exchange_rate = !empty($this->config["settings"]["usd_exchange_rate"]) ?  $this->config["settings"]["usd_exchange_rate"] : '1';

        if ($currency != 'BDT') {
            $amount = $amount * $usd_exchange_rate;
        }

        $first_name = !empty($user_data["name"]) ? $user_data["name"] : 'John';
        $last_name = !empty($user_data["surname"]) ? $user_data["surname"] : 'Doe';
        $email = !empty($user_data["email"]) ? $user_data["email"] : 'test@gmail.com';
        $invoice = $this->checkout_id;

        $fields                 = [
            'full_name'     => $first_name . ' ' . $last_name,
            'email'         => $email,
            'amount'        => $amount,
            'metadata'      => [
                'invoice'    => $invoice
            ],
            'redirect_url'  => $successful,
            'cancel_url'    => $failed,
            'webhook_url'   => $callback_url
        ];

        $data = $this->up_create_payment($fields);
        return $data;
    }


    public function up_create_payment($data = [])
    {
        if (empty($data)) {
            return [
                'status'    => false,
                'message'   => 'Data can\'t be empty.'
            ];
        }

        $host = parse_url($this->config["settings"]["api_url"],  PHP_URL_HOST);
        $api_url = "https://{$host}/api/checkout";

        // Setup request to send json via POST.
        $headers = [];
        $headers[] = "Content-Type: application/json";
        $headers[] = "RT-UDDOKTAPAY-API-KEY:" . $this->config["settings"]["api_key"];

        // Contact UuddoktaPay Gateway and get URL data
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($response, true);
        return $result;
    }


    public function execute_payment()
    {
        $headerApi = isset($_SERVER['HTTP_RT_UDDOKTAPAY_API_KEY']) ? $_SERVER['HTTP_RT_UDDOKTAPAY_API_KEY'] : null;

        if ($headerApi == null) {
            return [
                'status'    => false,
                'message'   => 'Invalid API Key.'
            ];
        }

        $apiKey = trim($this->config["settings"]["api_key"]);

        if ($headerApi != $apiKey) {
            return [
                'status'    => false,
                'message'   => 'Unauthorized Action.'
            ];
        }

        $response = strip_tags(trim(file_get_contents('php://input')));

        if (!empty($response)) {
            // Decode response data
            $data = json_decode($response, true);

            if (is_array($data)) {
                return $data;
            }
        }

        return [
            'status'    => false,
            'message'   => 'Invalid response from UddoktaPay API.'
        ];
    }

    public function up_execute_payment()
    {
        $data = $this->execute_payment();
        if (isset($data) && $data['status'] == 'COMPLETED') {
            $invoice_id = $data['invoice_id'];
            if (!isset($invoice_id)) {
                return [
                    'status'    => false,
                    'message'   => 'Invalid Response.'
                ];
            }

            // Generate API URL
            $host = parse_url($this->config["settings"]["api_url"],  PHP_URL_HOST);
            $verifyUrl = "https://{$host}/api/verify-payment";

            // Set data
            $data = [
                'invoice_id'    => $invoice_id
            ];

            // Setup request to send json via POST.
            $headers = [];
            $headers[] = "Content-Type: application/json";
            $headers[] = "RT-UDDOKTAPAY-API-KEY:" . $this->config["settings"]["api_key"];

            // Contact UuddoktaPay Gateway and get URL data
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $verifyUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($response, true);

            if (is_array($result)) {
                return $result;
            }
        }
        return [
            'status'    => false,
            'message'   => 'Invalid response from UddoktaPay API.'
        ];
    }

    public function payment_result()
    {
        $data = $this->up_execute_payment();

        if ($data['status'] == 'COMPLETED') {
            $checkout_id        = $data['metadata']['invoice'];
            $checkout           = Basket::get_checkout($checkout_id);

            if (!$checkout)
                return [
                    'status' => "ERROR",
                    'status_msg' => Bootstrap::$lang->get("errors/error6", Config::get("general/local")),
                    'return_msg' => "IPN Error: " . Bootstrap::$lang->get("errors/error6", Config::get("general/local")),
                ];

            $this->set_checkout($checkout);

            $txn_id         = $data['transaction_id'];

            $invoice = Invoices::search_pmethod_msg('"txn_id":"' . $txn_id . '"');

            if ($invoice) {
                $checkout["data"]["invoice_id"] = $invoice;
                $invoice = Invoices::get($invoice);
            }

            if ($invoice && $invoice["status"] == "paid") {
                Basket::set_checkout($checkout["id"], ['status' => "paid"]);
                return [
                    'status' => "SUCCESS",
                    'return_msg' => "OK",
                ];
            }


            Basket::set_checkout($checkout["id"], ['status' => "paid"]);
            if ($invoice) {
                Invoices::paid($checkout, "SUCCESS", $invoice["pmethod_msg"]);
                return [
                    'status' => "SUCCESS",
                    'return_msg' => "OK",
                ];
            } else {
                return [
                    'status' => "SUCCESS",
                    'checkout'    => $checkout,
                    'status_msg' => Utility::jencode([
                        'txn_id' => $txn_id
                    ]),
                    'return_msg' => "OK",
                ];
            }
        }

        return [
            'status' => "PENDING",
            'return_msg' => "Pending",
        ];
    }
}
