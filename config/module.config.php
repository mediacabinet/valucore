<?php
return [
    'service_manager' => [
        'factories' => [
            'ValuModelArrayAdapter' => 'Valu\\Model\\ArrayAdapter\\ArrayAdapterFactory',
            'valu.inputfilter.repository' => 'Valu\InputFilter\ServiceManager\InputFilterRepositoryFactory',
        ],
        'aliases' => [
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