<?php
namespace Valu\Service\Exception;

class InvalidServiceException extends \Valu\Service\Exception\ServiceException {
    protected $code = 1001;
}