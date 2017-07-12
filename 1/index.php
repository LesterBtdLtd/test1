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
     * Returns array of $arrLength with integer values on diapason
     * from $minValue to $arrLength
     *
     * @param int $minValue
     * @param int $arrLength
     * @return array
     */
    private function _generate(int $minValue = 0, int $arrLength = 10)
    {
        return range($minValue, $arrLength);
    }
}
$ODA = new OneDimensionArray();
$ODA->render();