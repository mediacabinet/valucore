<?php
namespace Valu\Service\Exception;

class UnsupportedFeatureException extends SkippableException
{
    protected $code = 1011;
}