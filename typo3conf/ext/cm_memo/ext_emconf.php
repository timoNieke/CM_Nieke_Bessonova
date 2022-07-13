<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'memo',
    'description' => 'Testextension',
    'category' => 'plugin',
    'author' => 'Timo Nieke',
    'author_email' => 's4tiniek@uni-trier.de',
    'state' => 'alpha',
    'clearCacheOnLoad' => 0,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-11.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
