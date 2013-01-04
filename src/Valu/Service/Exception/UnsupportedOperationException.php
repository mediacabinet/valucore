<?php
namespace Valu\Service\Exception;

class UnsupportedOperationException extends SkippableException
{
    protected $code = 1014;
}