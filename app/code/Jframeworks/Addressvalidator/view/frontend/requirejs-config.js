var config = {
    config: {
            mixins: {
                'Magento_Checkout/js/view/shipping': {
                    'Jframeworks_Addressvalidator/js/view/shipping': true
                }
            }
        },
	"map": {
        "*": {
			responsehandler: 'Jframeworks_Addressvalidator/js/response_handler'
        }
    }
};