<?php
declare(encoding = "utf-8");

class ForwardFW_Object
{
    /*
     * ID value
     *
     * @var mixed
     */
    protected $ID = 0;

    /**
     * Name of the field in data array, which holds the ID
     *
     * @var string
     */
    protected $strIdFieldName = 'ID';

    /**
     * Constructur
     *
     * @param strinf $_strIdFieldName Name of the ID field in data
     *
     * @return new instance
     */
    function __construct($_strIdFieldName = 'ID')
    {
        $this->strIdFieldName = $_strIdFieldName;
    }

    /**
     * Loads the model data out of an array as data set
     *
     * @param array &$arRow The array with data to read out
     *
     * @return void
     */
    function loadByArray(&$arRow)
    {
        $this->ID = $arRow[$this->strIdFieldName];
    }

    /**
     * Saves the model data into an array as data set
     *
     * @param array &$arRow The array into which the data will be written
     *
     * @return void
     */
    function saveToArray(&$arRow)
    {
        $arRow[$this->strIdFieldName] = $this->ID;
    }
}
?>