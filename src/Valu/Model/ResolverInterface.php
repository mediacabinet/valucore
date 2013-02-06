<?php
namespace Valu\Model;

/**
 * Model resolver interface
 * 
 * @author juhasuni
 *
 */
interface ResolverInterface
{
    /**
     * Resolve the model instance that given data represents
     * 
     * @param string $name   Model name
     * @param mixed $data    Data presenting a model instance
     * @return object
     */
    public function resolve($name, $data);
}