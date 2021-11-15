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
 * Gateway cleanup, check if remaining orders are paid, and if not, delete them to clean up.
 *
 * @package    paygw_qpay
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_qpay\task;

defined('MOODLE_INTERNAL') || die();

use paygw_qpay\qpay_api;
use paygw_qpay\qpay_helper;
use paygw_qpay\controller;
use core_payment\helper;

class cleanup extends \core\task\scheduled_task
{
    /**
     * Returns the name of this task.
     */
    public function get_name()
    {
        // Shown in admin screens.
        return get_string('cleanup', 'paygw_qpay');
    }

    /**
     * Executes task.
     */
    public function execute()
    {
        global $DB;

        // Get old expired orders.
        $orders = $DB->get_recordset_select(
            'paygw_qpay',
            '(status = ? OR status = ?) AND timemodified < ?',
            [qpay_helper::ORDER_STATUS_PENDING, qpay_helper::ORDER_STATUS_INIT, (time() - (HOURSECS))]
        );
        foreach ($orders as $order) {
            if ($order->status ==  qpay_helper::ORDER_STATUS_INIT) {
                $DB->delete_records('paygw_qpay', ['id' => $order->id]);
                continue;
            }

            try {
                $config = (object)helper::get_gateway_configuration(
                    $order->component,
                    $order->paymentarea,
                    $order->itemid,
                    'qpay'
                );
            } catch (\dml_exception $e) {
                // This payment method doesn't exist - delete the order - happens when enrol fee is removed from a course.
                $DB->delete_records('paygw_qpay', ['id' => $order->id]);
                continue;
            }

            // Sanity check if order was actually processed.
            if (!controller::check_status_and_enrol($order->object_id)->enrolled) {
                // This in an old unprocessed order - delete it.
                $DB->delete_records('paygw_qpay', ['id' => $order->id]);
            }
        }
        $orders->close();
    }
}
