<?php
namespace Foaf\Acl\Role;

use Zend\Permissions\Acl\Role\GenericRole;

class UniversalRole extends GenericRole
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