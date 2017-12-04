/*global define,alert*/
define(
    [
        'ko',
        'jquery',
        'mage/storage',
        'mage/translate',
    ],
    function (
        ko,
        $,
        storage,
        $t
    ) {
        'use strict';
        return function (customerData, totalCustomer) {
            return storage.post(
                'knockout/ajax/save',
                JSON.stringify(customerData),
                false
            ).done(
                function (response) {
                    if (response) {
                        totalCustomer([]);
                        $.each(response, function (i, v) {
                            totalCustomer.push(v);
                        });
                    }
                }
            ).fail(
                function (response) {
                }
            );
        };
    }
);