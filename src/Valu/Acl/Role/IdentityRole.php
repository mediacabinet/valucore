<?php
namespace Valu\Acl\Role;

use ArrayAccess;

class IdentityRole extends UniversalRole
{
    protected $identity;
    
    public function __construct($roleId, ArrayAccess $identity)
    {
        $this->identity = $identity;
        parent::__construct($roleId, $this->identity['id']);
    }
}