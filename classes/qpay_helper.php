<?php

namespace paygw_qpay;

use core_payment\helper;
use moodle_url;
use html_writer;
use paygw_qpay\nativepay;
use paygw_qpay\qpay_api;

defined('MOODLE_INTERNAL') || die();

/**
 * Class qpay_helper
 * @package paygw_qpay
 * @copyright 2021 Catalyst IT
 */
class qpay_helper
{
    public const ORDER_STATUS_INIT = 'INIT';
    public const ORDER_STATUS_PENDING = 'NEW';
    public const ORDER_STATUS_PAID = 'PAID';

    /**
     * Get an unprocessed order record - if one already exists - return it.
     *
     * @param string $component
     * @param string $paymentarea
     * @param string $itemid
     * @return false|\stdClass
     */
    public static function get_unprocessed_order($component, $paymentarea, $itemid)
    {
        global $USER, $DB;

        $existingorder = $DB->get_record('paygw_qpay', [
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
            'userid' => $USER->id,
            'status' => self::ORDER_STATUS_PENDING
        ]);
        if ($existingorder) {
            return $existingorder;
        }
        return false;
    }

    /**
     * Create a new order.
     *
     * @param string $component
     * @param string $paymentarea
     * @param integer $itemid
     * @param string $accountid
     * @return \stdClass
     */
    public static function create_order($component, $paymentarea, $itemid, $accountid, $cost, $description)
    {
        global $USER, $DB;

        $neworder = new \stdClass();
        $neworder->component = $component;
        $neworder->paymentarea = $paymentarea;
        $neworder->itemid = $itemid;
        $neworder->amount = $cost;
        $neworder->userid = $USER->id;
        $neworder->accountid = $accountid;
        $neworder->status = self::ORDER_STATUS_INIT;
        $neworder->timecreated = time();
        $neworder->timemodified = time();
        $neworder->modified = $neworder->timecreated;
        $id = $DB->insert_record('paygw_qpay', $neworder);
        $neworder->id = $id;

        $config_gateway = (object)helper::get_gateway_configuration($component, $paymentarea, $itemid, 'qpay');
        $response = qpay_api::create_invoice($config_gateway, $cost, $itemid, $description, $accountid, $id);

        $neworder->status = self::ORDER_STATUS_PENDING;
        $neworder->qrimg = $response->qr_image;
        $neworder->qrtext = $response->qr_text;
        $neworder->urls = json_encode($response->urls);
        $DB->update_record('paygw_qpay', $neworder);

        return $neworder;
    }

    /**
     * Check qpay to see if this order has been paid.
     *
     * @param \stdClass $config
     * @param \stdClass $order
     * @throws \Exception
     * @return boolean
     */
    public static function check_payment($config_gateway, $order)
    {
        $invoice_status = qpay_api::get_invoice_status($config_gateway, $order->itemid);
        return $invoice_status->paid_amount >= $order->amount;
    }

    /**
     * Process payment and deliver the order.
     * @param \stdClass $order
     * @return array
     * @throws \coding_exception
     */
    public static function process_payment($order)
    {
        global $DB;
        $payable = helper::get_payable($order->component, $order->paymentarea, $order->itemid);
        $cost = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), helper::get_gateway_surcharge('qpay'));
        $message = '';
        try {
            $paymentid = helper::save_payment(
                $payable->get_account_id(),
                $order->component,
                $order->paymentarea,
                $order->itemid,
                (int) $order->userid,
                $cost,
                $payable->get_currency(),
                'qpay'
            );

            // Store qpay extra information.
            $order->paymentid = $paymentid;
            $order->timemodified = time();
            $order->status = self::ORDER_STATUS_PAID;

            $DB->update_record('paygw_qpay', $order);

            helper::deliver_order($order->component, $order->paymentarea, $order->itemid, $paymentid, (int) $order->userid);
            $success = true;
        } catch (\Exception $e) {
            debugging('Exception while trying to process payment: ' . $e->getMessage(), DEBUG_DEVELOPER);
            $message = get_string('internalerror', 'paygw_qpay');
            $success = false;
        }

        return [
            'success' => $success,
            'message' => $message,
        ];
    }

    /**
     * Generate a unique order id based on timecreated and order->id field.
     *
     * @param \stdClass $order - the order record from paygw_qpay table.
     * @return string
     */
    protected static function get_orderid($order)
    {
        return $order->timecreated . '_' . $order->id;
    }
}
