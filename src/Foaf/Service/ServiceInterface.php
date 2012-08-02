<?php
namespace Foaf\Service;

interface ServiceInterface
{
    public function __invoke(ServiceEvent $e);
}