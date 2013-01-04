<?php
namespace Valu\Service\Exception;

class ServiceNotFoundException extends NotFoundException {
    protected $code = 1007;
}