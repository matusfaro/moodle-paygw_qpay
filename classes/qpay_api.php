<?php

namespace paygw_qpay;

use core_payment\helper;
use html_writer;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

class qpay_api
{
    public static function get_api_token($config_gateway)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => qpay_api::get_api_host() . '/v2/auth/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POST => 1,
            CURLOPT_USERPWD => $config_gateway->auth_user . ':' . $config_gateway->auth_pass,
        ));
        if (!$response = curl_exec($curl)) {
            error_log(print_r(curl_getinfo($curl), TRUE));
            throw new moodle_exception('Failed to load', 'paygw_qpay');
        }
        curl_close($curl);
        $response_json = json_decode($response);
        if (!empty($response_json->error) || empty($response_json->access_token)) {
            error_log(print_r($response_json, TRUE));
            throw new moodle_exception('Failed to load', 'paygw_qpay');
        }
        return $response_json->access_token;
    }

    /**
     * @return { invoice_id, qr_text, qr_image, urls: [ {name, description, link} ] }
     */
    public static function create_invoice($config_gateway, $amount, $itemid, $product_desc, $accountid, $invoice_id)
    {
        $data = array(
            'invoice_code' => $config_gateway->invoice_code,
            'sender_invoice_no' => $invoice_id . '',
            'invoice_receiver_code' => $accountid . '',
            'invoice_description' => $config_gateway->invoice_desc,
            'amount' => $amount,
            'lines' => array(
                array(
                    'sender_product_code' => $itemid . '',
                    'line_description' => $product_desc,
                    'line_quantity' => 1,
                    'line_unit_price' => $amount,
                ),
            ),
        );
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => qpay_api::get_api_host() . '/v2/invoice',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POST => 1,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . qpay_api::get_api_token($config_gateway),
                'Content-Type: application/json',
            ),
            CURLOPT_POSTFIELDS => json_encode($data),
        ));
        if (!$response = curl_exec($curl)) {
            error_log(print_r(curl_getinfo($curl), TRUE));
            throw new moodle_exception('Failed to load', 'paygw_qpay');
        }
        curl_close($curl);
        $response_json = json_decode($response);
        if (!empty($response_json->error)) {
            error_log(print_r($response_json, TRUE));
            throw new moodle_exception('Failed to load', 'paygw_qpay');
        }
        return $response_json;
    }

    /**
     * @return { count, paid_amount, rows: [ { payment_id, payment_status: NEW | FAILED | PAID | REFUNDED, payment_date, payment_fee, payment_amount, payment_currency, payment_wallet, transaction_type } ]}
     */
    public static function get_invoice_status($config_gateway, $object_id)
    {
        $data = array(
            'object_type' => 'INVOICE',
            'object_id' => $object_id,
        );
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => qpay_api::get_api_host() . '/v2/payment/check',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POST => 1,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . qpay_api::get_api_token($config_gateway),
                'Content-Type: application/json',
            ),
            CURLOPT_POSTFIELDS => json_encode($data),
        ));
        if (!$response = curl_exec($curl)) {
            error_log(print_r(curl_getinfo($curl), TRUE));
            throw new moodle_exception('Failed to load', 'paygw_qpay');
        }
        curl_close($curl);
        $response_json = json_decode($response);
        if (!empty($response_json->error)) {
            error_log(print_r($response_json, TRUE));
            throw new moodle_exception('Failed to load', 'paygw_qpay');
        }
        return $response_json;
    }

    public static function get_api_host()
    {
        return get_config('paygw_qpay', 'sandbox')
            ? 'https://merchant-sandbox.qpay.mn'
            : 'https://merchant.qpay.mn';
    }
}
