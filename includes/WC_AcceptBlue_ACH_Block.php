<?php
use \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
class WC_AcceptBlue_ACH_Block extends AbstractPaymentMethodType
{
    private $gateway;
    protected $name = 'acceptblue-ach';

    public function __construct($payment_gateway)
    {
        $this->gateway = $payment_gateway;
    }

    public function initialize(): void
    {
        $this->settings = get_option('woocommerce_acceptblue-ach_settings', []);
    }

    public function is_active()
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles(): array
    {
        wp_register_script(
            'wc-acceptblue-ach-block-integration',
            PGABFW_WC_BLOCKS . 'ach_check_block.js',
            array(
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n'
            ),
            null,
            array()
        );

        return array('wc-acceptblue-ach-block-integration');
    }

    public function get_payment_method_data(): array
    {
        return array(
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'supports' => $this->gateway->supports
        );
    }
}