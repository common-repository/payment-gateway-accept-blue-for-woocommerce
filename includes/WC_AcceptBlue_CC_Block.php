<?php
use \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

class WC_AcceptBlue_CC_Block extends AbstractPaymentMethodType
{

    private $gateway;
    protected $name = 'acceptblue-cc';

    public function __construct($payment_gateway)
    {
        $this->gateway = $payment_gateway;
    }

    public function initialize(): void
    {
        $this->settings = get_option('woocommerce_acceptblue-cc_settings', []);
    }

    public function is_active()
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles(): array
    {
        wp_register_script(
            'wc-acceptblue-cc-block-integration',
            PGABFW_WC_BLOCKS . 'credit_card_block.js',
            array(
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n'
            ),
            null,
            array()
        );

        return array('wc-acceptblue-cc-block-integration');
    }

    public function get_payment_method_data(): array
    {
        return array(
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'supports' => $this->gateway->supports,
            'customerIsLoggedIn' => is_user_logged_in()? 'true': 'false',
            'hasSubscription' => (class_exists('WC_Subscriptions_Cart') && WC_Subscriptions_Cart::cart_contains_subscription()) ? 'true' : 'false',
            'logos' => array(
                'visa' => PGABFW_IMAGES_FRONTEND . 'VISA.svg',
                'mastercard' => PGABFW_IMAGES_FRONTEND . 'MasterCard.svg',
                'amex' => PGABFW_IMAGES_FRONTEND . 'american-express.svg',
                'discover' => PGABFW_IMAGES_FRONTEND . 'discover.svg',
                'jcb' => PGABFW_IMAGES_FRONTEND . 'jcb.svg',
                'diners' => PGABFW_IMAGES_FRONTEND . 'diners-club.svg',
            )
        );
    }
}