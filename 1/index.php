<?php
error_reporting(E_ALL);

class OneDimensionArray
{
    private $array = [];

    public function __construct(array $array = [])
    {
        if(empty($array)) {
            $array = $this->_generate(rand(10, 20));
        }
        $this->array = $array;
    }

    public function render()
    {
        $array = &$this->array;
        include "view.php";
    }

    /**
     * Returns array of random $arrLength with random integer values on diapason
     * from $minValue to $maxValue
     *
     * @param int $arrLength
     * @param int $minValue
     * @param int $maxValue
     * @return array
     */
    private function _generate(int $arrLength = 10, int $minValue = 0 , int $maxValue = 20)
    {
        $array = [];
        for($i = 0; $i < $arrLength; $i++) {
            $array[] = rand($minValue, $maxValue);
        }
        return $array;
    }
}
$ODA = new OneDimensionArray();
$ODA->render();