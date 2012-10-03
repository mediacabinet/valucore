<?php
namespace Valu\Model\ArrayAdapter;

use ArrayObject;
use stdClass;
use Valu\Model\ArrayAdapter;

interface DelegateInterface
{
    public function filterOut(ArrayAdapter $arrayAdapter, $object, ArrayObject $data, array $fetch, array $options);
}