<?php
namespace Foaf\Doctrine\Model\Annotations;

use Doctrine\Common\Annotations\Annotation;

/** @Annotation */
final class Role extends Annotation
{
    public $type = 'role';
}