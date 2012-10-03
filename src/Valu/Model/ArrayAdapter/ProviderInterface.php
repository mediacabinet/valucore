<?php
namespace Valu\Model\ArrayAdapter;

interface ProviderInterface
{
    /**
     * Retrieve array adapter instance
     * 
     * @return \Valu\Model\ArrayAdapter
     */
    public function getArrayAdapter();
}