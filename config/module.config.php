<?php
return [
    'service_manager' => [
        'factories' => [
            'valu.array_adapter' => 'Valu\\Model\\ArrayAdapter\\ArrayAdapterFactory',
            'valu.inputfilter.repository' => 'Valu\InputFilter\ServiceManager\InputFilterRepositoryFactory',
        ],
        'aliases' => [
            'ArrayAdapter' => 'valu.array_adapter',
        ],
        'shared' => [
            'valu.array_adapter' => false
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
    'array_adapter' => [
        'model_listener' => [
            'namespaces' => [
            ],
            'proxy_namespaces' => [
                'DoctrineMongoODMModule\Proxy\__CG__'
            ]
        ],
        'date_formatter' => [
            'format' => DATE_ISO8601
        ]
    ]
];