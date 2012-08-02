<?php
namespace Foaf\Model;

use Foaf\Model\InputFilterTrait;
use Foaf\Model\ArrayAdapterTrait;

/**
 * Abstract model
 * 
 * @author juhasuni
 */
abstract class AbstractModel
{
    use InputFilterTrait, ArrayAdapterTrait;
}