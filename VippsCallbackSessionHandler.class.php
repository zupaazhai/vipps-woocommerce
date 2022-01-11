<?php
/*
   This class implements a Woocommerce Session Handler that works with callbacks from Vipps to the store
   so that the customers session will be in effect when calculating shipping, calculating VAT and so forth. IOK 2019-10-22


This file is part of the plugin Checkout with Vipps for WooCommerce
Copyright (c) 2019 WP-Hosting AS

MIT License

Copyright (c) 2019 WP-Hosting AS

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

 */

class VippsCallbackSessionHandler extends WC_Session_Handler {
    protected $callbackorder = 0;
    protected $sessiondata=null;

    public function init() { 
        global $Vipps;
        $this->callbackorder = $Vipps->callbackorder;
        return parent::init();
    }

    public function get_session_cookie() {
        if (!$this->callbackorder) return false;
        $order = wc_get_order($this->callbackorder);
        if (empty($order) && is_wp_error($order))  {
            return false;
        }
        $sessionjson = $order->get_meta('_vipps_sessiondata');
error_log("sessiondata is " . $sessionjson);


        if (empty($sessionjson)) return false;
        $sessiondata = @json_decode($sessionjson,true);
        if (empty($sessiondata)) return false;
        list($customer_id, $session_expiration, $session_expiring, $cookie_hash) = $sessiondata;
        if (empty($customer_id)) return false;
        // Validate hash.
        $to_hash = $customer_id . '|' . $session_expiration;
        $hash    = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );
        if ( empty( $cookie_hash ) || ! hash_equals( $hash, $cookie_hash ) ) {
            return false;
        }
        $this->sessiondata = $sessiondata;

        return array($customer_id, $session_expiration, $session_expiring, $cookie_hash); 
    }

    public function has_session () {
        return !empty($this->sessiondata);
    }

    public function forget_session() {
        if (!$this->has_session()) return;
        $order = wc_get_order($this->callbackorder);
        if (empty($order) && is_wp_error($order)) return false;
        $order->delete_meta_data('_vipps_sessiondata');
        wc_empty_cart();
        $this->_data        = array();
        $this->_dirty       = false;
        $this->_customer_id = $this->generate_customer_id();
    }

}
