<?php
namespace Valu\Utils;

use Valu\Utils\Uuid\UUID;

class UuidGenerator
{
    
    const VERSION_1 = 1;
    
    const VERSION_3 = 3;
    
    const VERSION_4 = 4;
    
    const VERSION_5 = 5;
    
    public static function generate($version = Uuid::VERSION_4, $node = null, $ns = null)
    {
        if(!in_array($version, array(1,3,4,5))){
            throw new \InvalidArgumentException('Invalid UUID version provided; generator supports versions 1, 3, 4 and 5');
        }
        
        return UUID::generate($version, UUID::FMT_STRING, $node, $ns);
    }
}