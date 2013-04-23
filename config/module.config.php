<?php
return [
    'service_manager' => [
        'factories' => [
            'ValuModelArrayAdapter' => 'Valu\\Model\\ServiceManager\\ArrayAdapterFactory',
            'valu.inputfilter.repository' => 'Valu\InputFilter\ServiceManager\InputFilterRepositoryFactory',
        ],
        'aliases' => [
            'Cache' => 'ValuCache',
            'ArrayAdapter' => 'ValuModelArrayAdapter',
        ],
        'shared' => [
            'ValuModelArrayAdapter' => false
        ]
    ],
    'valu_so' => [
        'services' => [
            'valuinputfilter' => [
                'name'    => 'InputFilter',
                'factory' => 'Valu\InputFilter\ServiceManager\InputFilterServiceFactory',
            ]
        ]
    ],
];