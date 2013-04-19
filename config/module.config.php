<?php
return [
    'service_manager' => [
        'factories' => [
            'valu.inputfilter.repository' => 'Valu\InputFilter\ServiceManager\InputFilterRepositoryFactory',
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