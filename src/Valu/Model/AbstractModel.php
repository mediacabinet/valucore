<?php
namespace Valu\Model;

use Valu\Model\InputFilterTrait;
use Valu\Model\ArrayAdapterTrait;

/**
 * Abstract model
 * 
 * @author juhasuni
 */
abstract class AbstractModel
{
    use InputFilterTrait, ArrayAdapterTrait;
}