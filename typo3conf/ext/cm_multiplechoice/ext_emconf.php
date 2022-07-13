<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'CM Multiple Choice',
    'description' => 'Diese Extension dient zur Erstellung von Multiplechoice Fragen',
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
