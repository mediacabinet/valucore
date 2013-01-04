<?php
namespace Valu\Service\Exception;

class NotFoundException extends \Valu\Service\Exception\ServiceException {
    protected $code = 1003;
}