<?php
require_once("person.php");

/**
 * Class FindPerson - A PHP wrapper class for the swedish service 118100's API
 */
class FindPerson
{
    /**
     * @var Holds the API-key
     */
    private $_appKey = "";

    /**
     * @var Holds the 118100's API URL
     */
    private $_appURL = "";

    /**
     * @var An array of Person class for holding the search result
     */
    private $_personList = null;


    /**
     * @param $APIKey Pass the API-key which has been given to you by 118100
     */
    public function __construct($APIKey)
    {
        if(empty($APIKey)) {
            throw new Exception("The API key is empty");
        }

        $this->_appKey = $APIKey;
        $this->_appURL = "http://developer.118100.se:8080/openapi-1.1/appetizing?query=";
        $this->_personList = array();
    }

    /**
     * To add a Person to this object's person list. Only unique (non-existing) person will be added to the list
     * @param $person An instance of the Person class
     * @return bool true on successful, false if the person already exists in the list
     */
    private function addPersonToList($person)
    {
        if (count($this->_personList) == 0) {
            $this->_personList[] = $person;
            return true;
        }

        if ($this->personExistsInList($person)) {
            $personIndex = $this->getPersonIndex($person);
            if ($personIndex == -1) return false;

            foreach ($person->getPhoneNumbersArray() as $phonenr) {
                $this->_personList[$personIndex]->addPhoneNumber($phonenr);
            }
        } else {
            $this->_personList[] = $person;
            return true;
        }

        return false;
    }

    /**
     * Get the list of person who has been found by the find() method
     * @return the object's person list
     */
    public function getPersonList()
    {
        return ($this->_personList);
    }

    /**
     * Checks if the given person already exists in the person list
     * @param Person $person
     * @return bool true if it exists, otherwise false
     */
    private function personExistsInList(Person $person)
    {
        foreach ($this->_personList as $p) {
            if ($p->isEqual($person)) return true;
        }

        return false;
    }

    /**
     * Returns the index of of the person given in the person list
     * @param Person $person
     * @return int 0 or bigger if the person exists, otherwise -1
     */
    private function getPersonIndex(Person $person)
    {
        $personCount = count($this->_personList);
        if ($personCount == 0) return -1;

        for ($i = 0; $i < $personCount; $i++) {
            if ($this->_personList[$i]->isEqual($person)) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * Find a person using the 118100 API
     * @param string $keyword Ex. foo street 13
     * @param int $limit The maximum number that the search result should return. Default is 10
     * @return An|array person list
     * @throws Exception
     */
    public function find($keyword = "", $limit = 10)
    {
        $trimmedKeyword = trim($keyword);
        $encodedKeyword = urlencode($trimmedKeyword);
        $fileContent = null;

        if (empty($trimmedKeyword)) {
            throw new \Exception("The search term is empty" . $keyword);
        }

        $url = $this->_appURL . $encodedKeyword . "&pageSize=" . $limit . "&key=" . $this->_appKey;

        $fileContent = file_get_contents($url);

        if ($fileContent == "License key required as argument" || empty($fileContent)) {
            throw new Exception("License key is required as argument");
        }

        $xmlData = simplexml_load_string($fileContent);

        if(!empty($this->_personList))  {
            unset($this->_personList);
            $this->_personList = array();
        }

        if (!$this->isXmlResponseValid($xmlData)) {
            return $this->_personList;
        }

        foreach ($xmlData->response->personHits->person as $person) {
            $fname = (string)$person->individual[0]->name[0];
            $lname = (string)$person->individual[0]->name[1];
            $zipcode = (string)$person->address->zip;
            $city = (string)$person->address->city;
            $street = (string)$person->address->street->name;
            $number = (string)$person->address->street->number;
            $phoneNumber = "0" . $person->phoneNumber[0]->areaCode . "-" . $person->phoneNumber[0]->localNumber;

            if (empty($fname) || empty($lname) || empty($zipcode) || empty($city) || empty($street) || empty($number) || empty($phoneNumber))
                continue;

            $p = new Person();
            $p->setFullName($fname . " " . $lname);
            $p->setStreetAddress($street . " " . $number);
            $p->setCity($city);
            $p->setZipcode($zipcode);
            $p->addPhoneNumber($phoneNumber);

            $this->addPersonToList($p);
        }
        return $this->_personList;
    }


    /**
     * Checks if the XML-response returned by the API is valid
     * @param $XMLResponse
     * @return bool
     */
    private function isXmlResponseValid($XMLResponse)
    {
        if ($XMLResponse == false ||
            is_null($XMLResponse->response) ||
            is_null($XMLResponse->response->personHits) ||
            is_null($XMLResponse->response->personHits->person) ||
            count($XMLResponse->response->personHits->person) == 0
        ) {
            return false;
        }

        return true;
    }
}

?>