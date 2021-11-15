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
 * Contains class for qpay payment gateway.
 *
 * @package    paygw_qpay
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_qpay;

/**
 * The gateway class for qpay payment gateway.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway extends \core_payment\gateway
{
    /**
     * Only CNY supported for now  (native qpay).
     * @return string[]
     */
    public static function get_supported_currencies(): array
    {
        // 3-character ISO-4217: https://en.wikipedia.org/wiki/ISO_4217#Active_codes.
        return [
            'MNT'
        ];
    }

    /**
     * Configuration form for the gateway instance
     *
     * Use $form->get_mform() to access the \MoodleQuickForm instance
     *
     * @param \core_payment\form\account_gateway $form
     */
    public static function add_configuration_to_gateway_form(\core_payment\form\account_gateway $form): void
    {
        $mform = $form->get_mform();

        $mform->addElement('text', 'auth_user', get_string('auth_user', 'paygw_qpay'));
        $mform->setType('auth_user', PARAM_TEXT);
        $mform->addHelpButton('auth_user', 'auth_user', 'paygw_qpay');

        $mform->addElement('password', 'auth_pass', get_string('auth_pass', 'paygw_qpay'));
        $mform->setType('auth_pass', PARAM_TEXT);
        $mform->addHelpButton('auth_pass', 'auth_pass', 'paygw_qpay');

        $mform->addElement('text', 'invoice_desc', get_string('invoice_desc', 'paygw_qpay'));
        $mform->setType('invoice_desc', PARAM_TEXT);
        $mform->addHelpButton('invoice_desc', 'invoice_desc', 'paygw_qpay');

        $mform->addElement('text', 'invoice_code', get_string('invoice_code', 'paygw_qpay'));
        $mform->setType('invoice_code', PARAM_TEXT);
        $mform->addHelpButton('invoice_code', 'invoice_code', 'paygw_qpay');
    }

    /**
     * Validates the gateway configuration form.
     *
     * @param \core_payment\form\account_gateway $form
     * @param \stdClass $data
     * @param array $files
     * @param array $errors form errors (passed by reference)
     */
    public static function validate_gateway_form(
        \core_payment\form\account_gateway $form,
        \stdClass $data,
        array $files,
        array &$errors
    ): void {
        if (
            $data->enabled &&
            (empty($data->auth_user) || empty($data->auth_pass) || empty($data->invoice_desc) || empty($data->invoice_code))
        ) {
            $errors['enabled'] = get_string('gatewaycannotbeenabled', 'payment');
        }
    }
}
