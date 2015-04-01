<?php


namespace Api\DataStructures;


use JsonSerializable;

/**
 * Class ApiCollection
 *
 * @SWG\Model(
 *      id="ApiCollection",
 *      description="An array of results, with useful metadata.",
 *      required="['collection', 'current_page', 'results_per_page', 'total_num_results', 'total_num_pages']"
 * )
 *
 * @SWG\Property(
 *      name="collection",
 *      type="array",
 *      @SWG\Items("ApiEloquent"),
 *      description="The array of entities requested."
 * )
 * @SWG\Property(
 *      name="current_page",
 *      type="integer",
 *      minimum=1,
 *      description="The page the current results belong to."
 * )
 * @SWG\Property(
 *      name="results_per_page",
 *      type="integer",
 *      minimum=1,
 *      maximum=1000,
 *      description="The maximum number of results expected per page."
 * )
 * @SWG\Property(
 *      name="total_num_results",
 *      type="integer",
 *      description="The count of the total results across all pages."
 * )
 * @SWG\Property(
 *      name="total_num_pages",
 *      type="integer",
 *      description="The count of the total number of pages."
 * )
 *
 *
 * @package Api\DataStructure
 */
class ApiCollection implements JsonSerializable {

    public $collection;
    public $current_page;
    public $results_per_page;
    public $total_num_results;
    public $total_num_pages;

    public function __construct($collection, $current_page, $results_per_page, $total_num_results) {

        $this->collection = $collection;
        $this->current_page = $current_page;
        $this->results_per_page = $results_per_page;
        $this->total_num_results = $total_num_results;
        $this->total_num_pages = ceil($total_num_results / $results_per_page);
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        return (array)$this;
    }

    /**
     * @return mixed
     */
    public function __toString()
    {
        return json_encode($this);
    }
}