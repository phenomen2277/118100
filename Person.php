<?php

class Person {
    public $_fullName;
    public $_streetAddress;
    public $_zipcode;
    public $_city;
    public $_phoneNumbers;
    public $_latitude;
    public $_longitude;

    public function __construct(){
        $this->_fullName = "";
        $this->_streetAddress = "";
        $this->_zipcode = 0;
        $this->_city = "";
        $this->_latitude = 0;
        $this->_longitude = 0;
        $this->_phoneNumbers = array();
    }

    public function addPhoneNumber($phoneNumber) {
        if(in_array($phoneNumber, $this->_phoneNumbers)) return false;

        $this->_phoneNumbers[] = $phoneNumber;
        return true;
    }

    public function isEqual(Person $person) {
        if ( $this->_fullName == $person->_fullName &&
            $this->_streetAddress == $person->_streetAddress &&
            $this->_zipcode == $person->_zipcode ) {
            return true;
        }

        return false;
    }
    public function getPhoneNumbersAsString() {
        if(count($this->_phoneNumbers) == 0 ) return "";
        return(implode(",", $this->_phoneNumbers));
    }

    public function getPhoneNumbersArray() {
        return($this->_phoneNumbers);
    }

    public function getCity() {
        return $this->_city;
    }
    public function setCity($city) {
        $this->_city = $city;
    }

    public function getZipcode() {
        return($this->_zipcode);
    }

    public function setZipcode($zipcode) {
        $this->_zipcode = $zipcode;
    }

    public function getStreetAddress() {
        return($this->_streetAddress);
    }

    public function setStreetAddress($streetAddress) {
        $this->_streetAddress = $streetAddress;
    }

    public function setFullName($fullName) {
        $this->_fullName = $fullName;
    }

    public function getFullName() {
        $this->_fullName;
    }

    public function curlGetLongLatFromGoogle() {
        $addressToFind = $this->_streetAddress . "+" .  $this->_zipcode . "+" . $this->_city .  "+" . "Sverige";

        $a = urlencode($addressToFind);
        $url = "http://maps.googleapis.com/maps/api/geocode/json?address=$a&sensor=false";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = json_decode(curl_exec($ch), true);
        sleep(1);
        if ($response['status'] != 'OK') {
            return false;
        } else {
            $geometry = $response['results'][0]['geometry'];
            $this->_latitude = $geometry['location']['lat'];
            $this->_longitude = $geometry['location']['lng'];
        }

        return true;
    }

    public function getAddressLatitude() {
        return $this->_latitude;
    }

    public function getAddressLongitude() {
        return $this->_longitude;
    }


}
?>