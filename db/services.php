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
 * External functions and service definitions for the qpay payment gateway plugin.
 *
 * @package    paygw_qpay
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'paygw_qpay_get_code' => [
        'classname'   => 'paygw_qpay\external\get_code',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Returns the qrcode for payment',
        'type'        => 'read',
        'ajax'        => true,
    ],
    'paygw_qpay_get_status' => [
        'classname'   => 'paygw_qpay\external\get_status',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Check if order has been paid',
        'type'        => 'read',
        'ajax'        => true,
    ],
];
