<?php
namespace Paydock\Sdk;

require_once(__DIR__."/../tools/ServiceHelper.php");
require_once(__DIR__."/../tools/JsonTools.php");

use Paydock\Sdk\serviceHelper;
/*
 * This file is part of the Paydock.Sdk package.
 *
 * (c) Paydock
 *
 * For the full copyright and license information, please view
 * the LICENSE file which was distributed with this source code.
 */
final class Charges
{
    private $chargeData;
    private $token;
    private $customerId;
    private $paymentSourceData = array();
    private $customerData = array();
    private $action;
    private $meta;
    
    public function create($amount, $currency, $description = "", $reference = "")
    {
        $this->chargeData = array("amount" => $amount, "currency"=>$currency, "description"=>$description, "reference" => $reference);
        $this->action = "create";
        return $this;
    }

    public function withToken($token)
    {
        $this->token = $token;
        return $this;
    }

    public function withCreditCard($gatewayId, $cardNumber, $expireYear, $expireMonth, $cardHolderName, $ccv)
    {
        $this->paymentSourceData = array("gateway_id" => $gatewayId, "card_number" => $cardNumber, "expire_month" => $expireMonth, "expire_year" => $expireYear, "card_name" => $cardHolderName, "card_ccv" => $ccv);
        return $this;
    }
    
    public function withBankAccount($gatewayId, $accountName, $accountBsb, $accountNumber, $accountHolderType = "", $accountBankName = "")
    {
        $this->paymentSourceData = array("gateway_id" => $gatewayId, "type" => "bank_account", "account_name" => $accountName, "account_bsb" => $accountBsb, "account_number" => $accountNumber, "account_holder_type" => $accountHolderType, "account_bank_name" => $accountBankName);
        return $this;
    }

    public function withCustomerId($customerId, $paymentSourceId = "")
    {
        $this->customerId = $customerId;
        if (!empty($paymentSourceId)) {
            $this->customerData["payment_source_id"] = $paymentSourceId;
        }
        return $this;
    }

    public function includeCustomerDetails($firstName, $lastName, $email, $phone)
    {
        $this->customerData += array("first_name" => $firstName, "last_name" => $lastName, "email" => $email, "phone" => $phone);
        return $this;
    }

    public function includeAddress($addressLine1, $addressLine2, $addressState, $addressCountry, $addressCity, $addressPostcode)
    {
        $this->paymentSourceData += array("address_line1" => $addressLine1, "address_line2" => $address_line2, "address_state" => $addressState, "address_country" => $addressCountry, "address_city" => $addressCity, "address_postcode" => $addressPostcode);
    }

    public function includeMeta($meta)
    {
        $this->meta = $meta;
    }

    // TODO: add: get charges, refund, archived

    private function buildJson()
    {
        switch ($this->action) {
            case "create":
                return $this->buildCreateJson();
        }
    }

    private function buildCreateJson()
    {
        if (empty($this->token) && empty($this->customerId) && count($this->paymentSourceData) == 0) {
            throw new \BadMethodCallException("must call with a token, customer or payment information");
        }

        $arrayData = [
            'amount'      => $this->chargeData["amount"],
            'currency'    => $this->chargeData["currency"],
            'reference'   => $this->chargeData["reference"],
            'description'   => $this->chargeData["description"]
        ];

        if (!empty($this->token)) {
            $arrayData += ["token" => $this->token];
        } else if (!empty($this->customerId)) {
            $arrayData += ["customer_id" => $this->customerId];
        }
    
        if (!empty($this->customerData)) {
            $arrayData += ["customer" => $this->customerData];
        }

        if (!empty($this->paymentSourceData)) {
            if (empty($arrayData["customer"])) {
                $arrayData["customer"] = array();
            }
            $arrayData["customer"]["payment_source"] = $this->paymentSourceData;
        }

        if (!empty($this->meta)) {
            $arrayData += ["meta" => $this->meta];
        }

        $jsonTools = new JsonTools();
        $arrayData = $jsonTools->CleanArray($arrayData);
        
        return json_encode($arrayData);
    }

    public function call()
    {
        $data = $this->buildJson();

        return ServiceHelper::privateApiCall("POST", "charges", $data);
    }
}
?>