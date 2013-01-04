<?php
namespace Valu\Service\Exception;

class OperationNotFoundException extends NotFoundException {
    protected $code = 1004;
}