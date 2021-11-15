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
 * This module is responsible for qpay content in the gateways modal.
 *
 * @module     paygw_qpay/gateway_modal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Templates from 'core/templates';
import ModalFactory from 'core/modal_factory';
import * as Repository from './repository';

/**
 * Creates and shows a modal that contains a placeholder.
 * @returns {Promise<Modal>}
 */
const showPlaceholderModal = async () => {
    const modal = await ModalFactory.create({
        body: await Templates.render('paygw_qpay/qpay_button_placeholder', {})
    });
    modal.show();
    return modal;
};

/**
 * Creates and shows a modal that contains a placeholder.
 * @param {object} qpayconfig
 * @returns {Promise<Modal>}
 */
const showModal = async (qpayconfig) => {
    const modal = await ModalFactory.create({
        body: await Templates.render('paygw_qpay/qpay_button', qpayconfig)
    });
    modal.show();
    return modal;
};

/**
 * Make Ajax call to get state, redirect if successful.
 *
 * @param {string} component
 * @param {string} paymentArea
 * @param {integer} itemId
 * @param {string} description
 * @returns {Promise<string>}
 */
const getState = (component, paymentArea, itemId, description) => {
    return Repository.getState(component, paymentArea, itemId, description)
        .then(state => {
            if (state.status) {
                return Repository.createRedirectUrl(component, paymentArea, itemId)
                    .then(url => {
                        location.href = url;
                        // Return a promise that is never going to be resolved.
                        return new Promise(() => null);
                    });
            }
            return new Promise(() => null);
        });
};

/**
 * Process the payment.
 *
 * @param {string} component Name of the component that the itemId belongs to
 * @param {string} paymentArea The area of the component that the itemId belongs to
 * @param {number} itemId An internal identifier that is used by the component
 * @param {string} description Description of the payment
 * @returns {Promise<string>}
 */
export const process = (component, paymentArea, itemId, description) => {
    // This is a hack to get around linting. Promises are usually required to return
    // But we are hacking the process js to inject a redirect so need to wait for that to occur.
    return showPlaceholderModal()
        .then(placemodal => {
            return Repository.getForm(component, paymentArea, itemId, description)
                .then(qpayconfig => {
                    placemodal.hide();
                    return showModal(qpayconfig)
                        .then((modal) => {
                            let max = 100;
                            for (var i = 0; i < max; i++) {
                                setTimeout(function () {
                                    getState(component, paymentArea, itemId, description);
                                }, (i + i + 1) * 3000);
                            }
                            // Hide Modal when timing out.
                            setTimeout(function () {
                                modal.hide();
                            }, (max + max + 1) * 3000);
                            return new Promise(() => null);
                        });
                });
        });
};