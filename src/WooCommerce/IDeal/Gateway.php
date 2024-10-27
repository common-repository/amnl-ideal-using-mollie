<?php
/**
 * This file is part of AMNL iDeal using Mollie.
 *
 * (c) Arno Moonen <info@arnom.nl>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @author Arno Moonen <info@arnom.nl>
 * @copyright Copyright (c) 2012, Arno Moonen <info@arnom.nl>
 * @package AMNL_iDEAL_using_Mollie
 */

namespace AMNL\WooCommerce\IDeal;

/**
 *
 *
 * @author Arno Moonen <info@arnom.nl>
 */
class Gateway extends \WC_Payment_Gateway
{

    private $mollie_partner_id;
    private $mollie_profile_key;
    private $testmode = true;
    private $transaction_description = 'Order #%ORDER_ID%';
    private $bankListCache = false;

    public function __construct()
    {
        $this->id = 'amnl_mollie_ideal';
        $this->method_title = __('iDEAL', 'amnl_mollie_ideal');
        $this->icon = 'http://www.mollie.nl/images/badge-ideallogo-small.gif';
        $this->has_fields = true;

        // Define user set variables
        $this->title = $this->settings['title'];
        $this->description = $this->settings['description'];
        $this->configurationId = $this->settings['configuration_id'];

        // Load the form fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        // Define user set variables
        $this->title = $this->settings['title'];
        $this->description = $this->settings['description'];
        $this->testmode = $this->settings['testmode'];
        $this->mollie_partner_id = $this->settings['mpartner_id'];
        $this->mollie_profile_key = $this->settings['mprofile_key'];
        $this->transaction_description = $this->settings['transaction_descr'];

        // Actions
        add_action('init', array($this, 'processReporting'));
        add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_amnl_mollie_ideal', array($this, 'thanks_page'));
        add_action('woocommerce_order_actions', array($this, 'check_button_show'));
        add_action('woocommerce_process_shop_order_meta', array($this, 'check_button_action'));
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'amnl_mollie_ideal'),
                'type' => 'checkbox',
                'label' => __('Enable iDEAL', 'amnl_mollie_ideal'),
                'default' => 'no'
            ),
            'mpartner_id' => array(
                'title' => __('Partner ID', 'amnl_mollie_ideal'),
                'type' => 'text',
                'description' => '<br />' . __('Your personal Partner ID / Customer ID.', 'amnl_mollie_ideal'),
                'default' => '',
            ),
            'mprofile_key' => array(
                'title' => __('Profile Key', 'amnl_mollie_ideal'),
                'type' => 'text',
                'description' => '<br />' . __('Optional Mollie payment-profile key.', 'amnl_mollie_ideal'),
                'default' => '',
            ),
            'testmode' => array(
                'title' => __('Test Mode', 'amnl_mollie_ideal'),
                'type' => 'checkbox',
                'label' => __('Enable testmode', 'amnl_mollie_ideal'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Title', 'amnl_mollie_ideal'),
                'type' => 'text',
                'description' => '<br />' . __('This controls the title which the user sees during checkout.', 'amnl_mollie_ideal'),
                'default' => __('iDEAL', 'amnl_mollie_ideal')
            ),
            'description' => array(
                'title' => __('Description', 'amnl_mollie_ideal'),
                'type' => 'textarea',
                'description' => '' . __('Give the customer instructions about paying with iDEAL and the additional terms.', 'amnl_mollie_ideal'),
                'default' => __('With iDEAL you can easily pay online in the secure environment of your own bank.', 'amnl_mollie_ideal')
            ),
            'transaction_descr' => array(
                'title' => __('Transaction Description', 'amnl_mollie_ideal'),
                'type' => 'text',
                'description' => '<br />' . __('This will be shown on the bank statement of the customer. Add <code>%%order</code> where you wish to display the order number.', 'amnl_mollie_ideal'),
                'default' => get_bloginfo('name') . ' ' . __('Order %%order', 'amnl_mollie_ideal')
            ),
        );
    }

    public function is_valid_for_use()
    {
        return get_woocommerce_currency() === 'EUR';
    }

    public function admin_options()
    {
        ?>
        <h3><?php _e('iDEAL (via Mollie)', 'amnl_mollie_ideal') ?></h3>
        <p><?php _e("Accept iDEAL payments using Mollie's service.", 'amnl_mollie_ideal'); ?></p>
        <table class="form-table">
            <?php
            if ($this->is_valid_for_use()) :

                // Generate the HTML For the settings form.
                $this->generate_settings_html();

            else :
                ?>
                <div class="inline error"><p><strong><?php _e('Gateway Disabled', 'amnl_mollie_ideal'); ?></strong>: <?php _e('Mollie does not support your store currency.', 'amnl_mollie_ideal'); ?></p></div>
            <?php
            endif;
            ?>
        </table>
        <?php
    }

    protected function getNewIDealPaymentInstance()
    {
        \AMNL\Autoloader::loadMollieClass();
        $ideal = new \iDEAL_Payment($this->mollie_partner_id);
        $ideal->setProfileKey($this->mollie_profile_key);
        $ideal->setTestmode(($this->testmode == 'yes'));
        return $ideal;
    }

    protected function getBanks($iDEALPayment = null)
    {

        // Check cache
        if (is_array($this->bankListCache)) {
            return $this->bankListCache;
        }

        // Get banks
        if (is_null($iDEALPayment)) {
            $iDEALPayment = $this->getNewIDealPaymentInstance();
        }
        $this->bankListCache = $iDEALPayment->getBanks();

        return $this->bankListCache;
    }

    protected function getReportingUrl($order_id)
    {
        $fields = array(
            'a' => 'cb',
            'wc' => base_convert($order_id, 10, 36),
        );

        $data = base64_encode(json_encode($fields, JSON_FORCE_OBJECT));

        return trailingslashit(home_url()) . '?amnl_mollie=' . urLencode($data) . '&';
    }

    protected function getTransactionDescription($order_id)
    {
        return str_replace('%%order', trim($order_id), $this->transaction_description);
    }

    protected function createTransactionForOrder($order_id, $bank_id)
    {
        global $woocommerce;

        // Get order
        $order = &new \WC_Order($order_id);

        // IDeal
        $idp = $this->getNewIDealPaymentInstance();
        if ($idp->createPayment($bank_id, ceil($order->get_order_total() * 100), $this->getTransactionDescription($order->id), $this->get_return_url($order), $this->getReportingUrl($order->id))) {

            // Payment created
            // Store payment ID
            update_post_meta($order->id, 'amnl_mollie_transaction_id', $idp->getTransactionId());

            // Redirect
            return array(
                'result' => 'success',
                'redirect' => $idp->getBankURL(),
            );
        } else {
            $woocommerce->add_error(__('Could not create payment.', 'amnl_mollie_ideal'));
            $errorMsg = $idp->getErrorMessage();
            if (!empty($errorMsg)) {
                $woocommerce->add_error('#M' . $idp->getErrorCode() . ': ' . $errorMsg);
            }
            return array('result' => 'failed');
        }
    }

    function process_payment($order_id)
    {
        $order = &new \WC_Order($order_id);

        // Mark as pending
        $order->update_status('pending', __('Awaiting iDEAL payment and authorization', 'amnl_mollie_ideal'));

        // Create payment
        return $this->createTransactionForOrder($order_id, $_REQUEST['amnl_mollie_ideal_bank']);
    }

    public function payment_fields()
    {
        global $woocommerce;

        // Description
        $output = (!empty($this->description)) ? '<p>' . $this->description . '</p>' : '';

        // Get banks
        $ideal = $this->getNewIDealPaymentInstance();
        $banks = $this->getBanks($ideal);

        // Success?
        if (!is_array($banks) || count($banks) == 0) {
            $errorMsg = $ideal->getErrorMessage();
            if (!empty($errorMsg)) {
                $errorMsg .= ' (#M' . $ideal->getErrorCode() . ')';
            } else {
                $errorMsg = __('We are experiencing some difficulties whilst contacting the payment service provider. Please try again in a moment.', 'amnl_mollie_ideal');
            }
            $output .= '<p>' . $errorMsg . '</p>';

            $woocommerce->add_error($errorMsg);

            echo wpautop(wptexturize($output));
            return;
        }

        // Add banks
        $output .= '<fieldset>
      <p class="form-row form-row-first">
           <label for="amnl_mollie_ideal_bank">' . __('Bank', 'amnl_mollie_ideal') . ' <span class="required">*</span></label>
           <select name="amnl_mollie_ideal_bank" id="amnl_mollie_ideal_bank" class="woocommerce-select">
                <option disabled>' . __('Choose your bank', 'amnl_mollie_ideal') . '</option>';

        foreach ($banks as $bankId => $bankName) {
            $output .= "\n                <option value=\"$bankId\">$bankName</option>";
        }

        $output .= '
            </select>
      </p>
      <div class="clear"></div>
      </fieldset>';

        echo wpautop(wptexturize($output));
    }

    public function validate_fields()
    {
        global $woocommerce;

        // Value?
        if (empty($_REQUEST['amnl_mollie_ideal_bank'])) {
            $woocommerce->add_error(__('You must select your bank to use the iDEAL payment option.', 'amnl_mollie_ideal'));
            return false;
        }

        // Get banks
        $banks = $this->getBanks();

        // Known ID?
        // TODO Divide condition below and give seperate errors
        if (!is_array($banks) || !array_key_exists($_REQUEST['amnl_mollie_ideal_bank'], $banks)) {
            $woocommerce->add_error(__('Invalid Bank for iDEAL payment supplied.', 'amnl_mollie_ideal'));
            return false;
        }

        return true;
    }

    public function thanks_page()
    {
        if (!empty($_REQUEST['order']) && !empty($_REQUEST['transaction_id'])) {
            $order = new \WC_Order($_REQUEST['order']);
            $order = $this->checkTransaction($order);

            if ($order->status == 'pending') {
                echo '<p class="amnl_mollie_notice">' . __('Your payment is currently being processed.', 'amnl_mollie_ideal') . '</p>';
            }
        }
    }

    public function check_button_show($order_id)
    {

        // Get transaction id
        $transactionId = get_post_meta($order_id, 'amnl_mollie_transaction_id', true);
        if (empty($transactionId)) {
            return;
        }

        // Order
        $order = new \WC_Order($order_id);
        if (is_null($order->status) || $order->status != 'pending') {
            return;
        }

        // Show button
        echo '<li><input type="submit" class="button tips" name="amnl_mollie_ideal_check" value="' . __('Check transaction', 'amnl_mollie_ideal') . '" data-tip="' . __('Request a status update from Mollie', 'amnl_mollie_ideal') . '" /></li>';
    }

    public function check_button_action($post_id, $post)
    {
        if (!empty($_POST['amnl_mollie_ideal_check'])) {
            $order = new \WC_Order($post_id);
            $this->checkTransaction($order);
        }
    }

    protected function checkTransaction(&$order)
    {
        // Only check when the order is pending
        if ($order->status == 'pending') {

            $transactionId = get_post_meta($order->id, 'amnl_mollie_transaction_id', true);
            if (empty($transactionId)) {
                return $order;
            }

            // iDEAL_Payment
            $idp = $this->getNewIDealPaymentInstance();
            $idp->checkPayment($transactionId);
            $status = strtolower(trim((empty($idp->status)) ? 'pending' : $idp->status));

            if ($idp->getPaidStatus() === true || $status == 'checkedbefore') {
                // Transaction completed (might be checked before)
                // Store consumer data in note
                $consumer = $idp->getConsumerInfo();
                if (count($consumer) == 3) {
                    $custDataNote = __('Bank Account:', 'amnl_mollie_ideal') . ' ' . trim($consumer['consumerAccount']) . " \n";
                    $custDataNote .= __('Holder:', 'amnl_mollie_ideal') . ' ' . trim($consumer['consumerName']) . ' (' . trim($consumer['consumerCity']) . ')';
                    $order->add_order_note($custDataNote, false);
                }
                // Payment complete
                $order->payment_complete();
            } elseif ($status == 'cancelled') {
                // Cancelled by customer
                $order->cancel_order(__('iDEAL payment has been cancelled.', 'amnl_mollie_ideal'));
            } elseif ($status == 'failure') {
                // Failed
                $order->update_status('failed', __('iDEAL payment failed.', 'amnl_mollie_ideal'));
            } elseif ($status == 'expired') {
                // Expired
                $order->update_status('failed', __('iDEAL payment expired.', 'amnl_mollie_ideal'));
            }
        }
        return new \WC_Order($order->id);
    }

    public function processReporting()
    {
        if (empty($_GET['amnl_mollie'])) {
            // Not a reporting call
            return;
        }

        // Extract data from amnl_mollie
        $reportData = json_decode(base64_decode(urldecode($_GET['amnl_mollie'])), true);
        if (empty($reportData['a']) || empty($reportData['wc']) || $reportData['a'] != 'cb') {
            return;
        }
        $order_id = base_convert($reportData['wc'], 36, 10);
        if (!filter_var($order_id, FILTER_VALIDATE_INT) || $order_id < 1) {
            wp_die("Invalid Order number (" . $order_id . ").");
        }

        // Get order
        $order = new \WC_Order($order_id);

        // Check transaction
        $order = $this->checkTransaction($order);

        // Clean buffer
        @ob_clean();

        if (is_null($order->id)) {
            wp_die('Order not found.');
        } elseif ($order->status == 'pending') {
            // Something probably went wrong
            wp_die("Order is still pending.");
        } else {
            // We take it that everything went well
            header('HTTP/1.1 200 OK');
            echo 'OK';
            exit;
        }
    }

}

