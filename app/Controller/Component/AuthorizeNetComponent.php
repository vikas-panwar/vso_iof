<?php

/**
 * The AuthorizeNet PHP SDK. Include this file in your project.
 *
 * @package AuthorizeNet
 */
App::import('Vendor', 'AuthorizeNetRequest', array('file' => 'AuthorizeNet' . DS . 'shared' . DS . 'AuthorizeNetRequest.php'));
App::import('Vendor', 'AuthorizeNetTypes', array('file' => 'AuthorizeNet' . DS . 'shared' . DS . 'AuthorizeNetTypes.php'));
App::import('Vendor', 'AuthorizeNetXMLResponse', array('file' => 'AuthorizeNet' . DS . 'shared' . DS . 'AuthorizeNetXMLResponse.php'));
App::import('Vendor', 'AuthorizeNetResponse', array('file' => 'AuthorizeNet' . DS . 'shared' . DS . 'AuthorizeNetResponse.php'));
App::import('Vendor', 'AuthorizeNetAIM', array('file' => 'AuthorizeNet' . DS . 'AuthorizeNetAIM.php'));

/**
 * Exception class for AuthorizeNet PHP SDK.
 *
 * @package AuthorizeNet
 */
class AuthorizeNetComponent extends Component {
    function validate_card($loginid = null, $trankey = null, $amount = null, $card_num = null, $exp_date = null, $cvv = null) {
        $transaction = new AuthorizeNetAIM($loginid, $trankey);
        //pr($exp_date);die;
        $transaction->amount = $amount;
        $transaction->card_num = $card_num;
        $transaction->exp_date = $exp_date;
        $transaction->setSandbox(false);
        $transaction->card_code = $cvv;
        return $transaction->authorizeAndCapture();
    }

}
