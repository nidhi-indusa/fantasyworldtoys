define([
    'jquery',
    'uiComponent',
    'mage/validation',
    'ko',
    'Indusa_Deliverymethod/js/action/save-customer',
    ], function ($, Component, validation, ko, saveAction) {
        'use strict';
        var totalCustomer= ko.observableArray([]);
        return Component.extend({
            defaults: {
                template: 'Indusa_Deliverymethod/shipping'
            },
 
            initialize: function () {
                this._super();
            },
            save: function (saveForm) {
                var self = this;
                var saveData = {},
                    formDataArray = $(saveForm).serializeArray();
 
                formDataArray.forEach(function (entry) {
                    saveData[entry.name] = entry.value;
                });
 
                if($(saveForm).validation()
                    && $(saveForm).validation('isValid')
                ) {
                    saveAction(saveData, totalCustomer).always(function() {
                        console.log(totalCustomer());
                    });
                }
            },
            getTotalCustomer: function () {
                return totalCustomer;
            }
        });
    }
);