<?php
namespace App\Utility\Geo;

require_once "pcde.php";

final class Geo {
    private $citiesDE;
    private $dataMatches;

    public function __construct() {
        $this -> citiesDE = PCDE;
        $this -> dataMatches = [];
    }

    private function internalFilter($filter, $condition, $city, $filter_index, &$limit) {
        $matches = [];
        if (preg_match("/(?=$filter)\w+/", $city[$filter_index], $matches)) {
            if (strlen($matches[0]) == $condition) {
                array_push($this -> dataMatches, $city);
                $limit--;
            }
        }
    }

    public function getByPostalCode($postal_code, $limit = 10) {
        $this -> dataMatches = [];
        foreach ($this -> citiesDE as $key => $city) {
            if ($limit == 0) break;
            $this -> internalFilter($postal_code, 5, $city, 0, $limit);
        }
    }

    public function getByName($name, $limit = 10) {
        $this -> dataMatches = [];
        foreach ($this -> citiesDE as $key => $city) {
            if ($limit == 0) break;
            $this -> internalFilter($name, strlen($city[1]), $city, 1, $limit);
        }
    }

    public function exportZipCity() {
        $cityMatches = [];
        foreach ($this -> dataMatches as $key => $city) {
            array_push($cityMatches, [$city[0] => $city[1]]);
        }
        return $cityMatches;
    }

    public function encodeJSON() {
        return json_encode($this -> dataMatches, JSON_UNESCAPED_UNICODE);
    }
}

?>