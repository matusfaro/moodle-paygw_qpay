<?php

namespace paygw_qpay;

defined('MOODLE_INTERNAL') || die();

class controller
{
    /**
     * @return { enrolled: bool }
     */
    public static function check_status_and_enrol($object_id)
    {
        // TODO check qpay payment
        // TODO update DB status and modified time
        // TODO enrol if needed
        return array(
            'enrolled' => false,
        );
    }
}
