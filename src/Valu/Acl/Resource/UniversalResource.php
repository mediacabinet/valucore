<?php
namespace Valu\Acl\Resource;

use Zend\Permissions\Acl\Resource\GenericResource;

class UniversalResource extends GenericResource
{
    protected $uuid;
    
    public function __construct($roleId, $uuid)
    {
        $this->uuid = $uuid;
        
        parent::__construct($roleId);
    }

    public function getUuid()
    {
        return $this->uuid;
    }
    
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }
}