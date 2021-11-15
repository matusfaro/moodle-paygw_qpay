<?php

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading('paygw_qpay_settings', '', get_string('pluginname_desc', 'paygw_qpay')));

    $settings->add(new \admin_setting_configcheckbox(
        'paygw_qpay/sandbox',
        get_string('sandbox', 'paygw_qpay'),
        get_string('sandbox_help', 'paygw_qpay'),
        false
    ));

    \core_payment\helper::add_common_gateway_settings($settings, 'paygw_qpay');
}
