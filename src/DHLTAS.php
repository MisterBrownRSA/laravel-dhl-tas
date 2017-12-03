<?php

    namespace MisterBrownRSA\DHL\TAS;

    class DHLTAS
    {
        private $username;
        private $password;

        private $document;
        private $resultsRAW;
        private $results;

        private $currency;
        private $fromCountry;
        private $toCountry;
        private $total;
        private $reference;

        public function __construct($options = NULL)
        {
            if (!empty($options)) {
                foreach ($options as $option => $value) {
                    $this->$option = $value;
                }
            }
            $this->username = getenv('DHL_USER') ?: config('dhl.tas.DHL_USER', 'ZA');
            $this->username .= '_user';
            $this->password = getenv('DHL_PASSWORD') ?: config('dhl.tas.DHL_PASSWORD', 'ZA');
            $this->password .= '_pass';
            $this->currency = getenv('DHL_CURRENCY') ?: config('dhl.tas.DHL_CURRENCY', 'USD');;
            $this->fromCountry = getenv('DHL_COUNTRY') ?: config('dhl.tas.DHL_COUNTRY', 'ZA');
            $this->products = [];
        }

        public function toXML()
        {
            $xml = new \XmlWriter();
            $xml->openMemory();
            $xml->setIndent(TRUE);
            $xml->setIndentString("    ");
            $xml->startDocument('1.0', 'UTF-8');

            $xml->startElement('soapenv:Envelope');
            $xml->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
            $xml->writeAttribute('xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
            $xml->writeAttribute('xmlns:soapenv', "http://schemas.xmlsoap.org/soap/envelope/");
            $xml->writeAttribute('xmlns:urn', "urn:LandedCost");
            $xml->writeAttribute('xmlns:soapenc', "http://schemas.xmlsoap.org/soap/encoding/");


            $xml->startElement('soapenv:Header');
            $xml->text('');
            $xml->endElement();

            $xml->startElement('soapenv:Body');
            $xml->startElement('urn:getLandedCostEstimateForMultipleCommodities');
            $xml->writeAttribute('soapenv:encodingStyle', "http://schemas.xmlsoap.org/soap/encoding/");
            $xml->startElement('objLandedCostInput');
            $xml->writeAttribute('xsi:type', "cus:LandedCostMultipleCommoditiesInput");
            $xml->writeAttribute('xmlns:cus', "http://www.dhl.com/xmlns/F.040601/customer");

            $xml->startElement('domain');
            $xml->writeAttribute('xsi:type', "xsd:string");
            $xml->text("I");
            $xml->endElement();

            $xml->startElement('insuranceCurrency');
            $xml->writeAttribute('xsi:type', "xsd:string");
            $xml->text($this->currency);
            $xml->endElement();

            $xml->startElement('insuranceValue');
            $xml->writeAttribute('xsi:type', "xsd:string");
            $xml->text("0");
            $xml->endElement();

            $xml->startElement('receiverCountry');
            $xml->writeAttribute('xsi:type', "xsd:string");
            $xml->text($this->toCountry);
            $xml->endElement();

            $xml->startElement('shipmentCurrency');
            $xml->writeAttribute('xsi:type', "xsd:string");
            $xml->text($this->currency);
            $xml->endElement();

            $xml->startElement('shipperCountry');
            $xml->writeAttribute('xsi:type', "xsd:string");
            $xml->text($this->fromCountry);
            $xml->endElement();

            $xml->startElement('shipToState');
            $xml->writeAttribute('xsi:type', "xsd:string");
            $xml->text('');
            $xml->endElement();

            $xml->startElement('transportationCurrency');
            $xml->writeAttribute('xsi:type', "xsd:string");
            $xml->text($this->currency);
            $xml->endElement();

            $xml->startElement('transportationValue');
            $xml->writeAttribute('xsi:type', "xsd:string");
            $xml->text($this->total);
            $xml->endElement();

            $xml->startElement('type');
            $xml->writeAttribute('xsi:type', "xsd:string");
            $xml->text("HS"); //Harmonised System
            $xml->endElement();

            $xml->startElement('productInfo');
            $xml->writeAttribute('xsi:type', "cus:ArrayOf_tns1_ProductDetails");
            $xml->writeAttribute('soapenc:arrayType', "cus:ProductDetails[" . count($this->products) . "]");
            foreach ($this->products as $product) {
                $xml->startElement('product');
                $xml->startElement('countryCode');
                $xml->writeAttribute('xsi:type', "xsd:string");
                $xml->text($this->toCountry);
                $xml->endElement();

                $xml->startElement('description');
                $xml->writeAttribute('xsi:type', "xsd:string");
                $xml->text($product['name']);
                $xml->endElement();

                $xml->startElement('measurementType');
                $xml->writeAttribute('xsi:type', "xsd:string");
                $xml->text("NetWeight");
                $xml->endElement();

                $xml->startElement('priceCurrency');
                $xml->writeAttribute('xsi:type', "xsd:string");
                $xml->text($this->currency);
                $xml->endElement();

                $xml->startElement('priceValue');
                $xml->writeAttribute('xsi:type', "xsd:string");
                $xml->text($product['price']);
                $xml->endElement();

                $xml->startElement('productCode');
                $xml->writeAttribute('xsi:type', "xsd:string");
                $xml->text($product['hscode']); //"6404.1900"
                $xml->endElement();

                $xml->startElement('totalQuantity');
                $xml->writeAttribute('xsi:type', "xsd:string");
                $xml->text($product['quantity']);
                $xml->endElement();

                $xml->startElement('unit');
                $xml->writeAttribute('xsi:type', "xsd:string");
                $xml->text("KGM");
                $xml->endElement();

                $xml->startElement('value');
                $xml->writeAttribute('xsi:type', "xsd:string");
                $xml->text($product['weight']); //"1.6"
                $xml->endElement();
                $xml->endElement();
            }
            $xml->endElement();
            $xml->endElement();

            $xml->startElement('referenceID');
            $xml->writeAttribute('xsi:type', "xsd:string");
            $xml->text($this->reference);
            $xml->endElement();
            $xml->endElement();
            $xml->endElement();

            $xml->endElement();
            $xml->endDocument();

            return $this->document = $xml->outputMemory();
        }

        public function doCurlPost()
        {
            $ch = curl_init('https://tasapi.dhl.com/facts/servlet/rpcrouter');

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); //ssl
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); //ssl
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/xml']);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
            curl_setopt($ch, CURLOPT_NOBODY, FALSE);
            curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->document());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

            $result = curl_exec($ch);

            curl_close($ch);

            $this->resultsRAW = $result;

            try {
                $decodedXML = simplexml_load_string($result);
                $decodedXML->registerXPathNamespace('ns1', 'urn:LandedCost');
                $this->results = $decodedXML->xpath('//return');
            } catch (\Exception $exception) {
                return FALSE;
            }


            return $this->results;
        }

        public function document()
        {
            if (!isset($this->document)) {
                $this->toXML();
            }

            return $this->document;
        }

        public function results($cached = FALSE)
        {
            if ($cached && !empty($this->results)) {
                return $this->results;
            }

            $this->doCurlPost();

            return $this->results;
        }

        public function resultsRaw($cached = FALSE)
        {
            if ($cached && !empty($this->resultsRAW)) {
                return $this->resultsRAW;
            }

            $this->doCurlPost();

            return $this->resultsRAW;
        }

        public function products()
        {
            return $this->products;
        }

        public function addProduct($product)
        {
            /*TODO:: validation of product*/

            if (is_array($product)) {
                foreach ($product as $item) {
                    $this->products[] = $item;
                }
            } else {
                $this->products[] = $product;
            }

            return $this;
        }

        public function reference($value = NULL)
        {
            if (empty($value)) {
                return $this->reference;
            }

            $this->reference = $value;

            return $this;
        }

        public function total($value = NULL)
        {
            if (empty($value)) {
                return $this->total;
            }

            $this->total = $value;

            return $this;
        }

        public function toCountry($value = NULL)
        {
            if (empty($value)) {
                return $this->toCountry;
            }

            $this->toCountry = $value;

            return $this;
        }

        public function user()
        {
            return $this;
        }

        public function cart()
        {
            return $this;
        }

    }