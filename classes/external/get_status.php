<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This class contains a list of webservice functions related to the qpay payment gateway.
 *
 * @package    paygw_qpay
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_qpay\external;

use paygw_qpay\qpay_helper;
use core_payment\helper;
use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Class get status
 * @copyright 2021 Catalyst IT
 * @package paygw_qpay
 */
class get_status extends external_api
{

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters
    {
        return new external_function_parameters([
            'component' => new external_value(PARAM_COMPONENT, 'Component'),
            'paymentarea' => new external_value(PARAM_AREA, 'Payment area in the component'),
            'itemid' => new external_value(PARAM_INT, 'An identifier for payment area in the component'),
            'description' => new external_value(PARAM_TEXT, 'The description of the payment'),
        ]);
    }

    /**
     * Checks to see if the user has paid.
     *
     * @param string $component
     * @param string $paymentarea
     * @param int $itemid
     * @param string $description
     * @return string[]
     */
    public static function execute(string $component, string $paymentarea, int $itemid, string $description): array
    {
        self::validate_parameters(self::execute_parameters(), [
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
            'description' => $description
        ]);


        $order = qpay_helper::get_unprocessed_order($component, $paymentarea, $itemid);

        if ($order) {
            $config_gateway = (object)helper::get_gateway_configuration($component, $paymentarea, $itemid, 'qpay');
            if (qpay_helper::check_payment($config_gateway, $order)) {
                qpay_helper::process_payment($order);
                // This order has already been paid - prevent them from paying again.
                return [
                    'status' => true
                ];
            }
        }

        return [
            'status' => false
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure
    {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Status of order'),
        ]);
    }
}
