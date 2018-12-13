<?php

class NameValueCollection
{
    private $values;
    public function __construct(array $values = null)
    {
        $this->values = $values !== null ? $values : array();
    }
    public function AllKeys()
    {
        return array_keys($this->values);
    }
    public function Add($key, $value)
    {
        $this->values[$key] = $value;
    }
    public function AddCollection(NameValueCollection $collection)
    {
        foreach ($collection->AllKeys() as $key)
        {
            $this->Add($key, $collection->Get($key));
        }
    }
    public function GetIndex($index)
    {
        $keys = $this->AllKeys();
        return $this->values[$keys[$index]];
    }
    public function Get($key)
    {
        return array_key_exists($key, $this->values) ? $this->values[$key] : null;
    }
    public function HasKeys()
    {
        return (count($this->AllKeys()) > 0);
    }
    public function Remove($key)
    {
        unset($this->values[$key]);
    }
    public function Set($key, $value)
    {
        if (array_key_exists($key, $this->values))
        {
            $this->values[$key] = $value;
        }
    }
}