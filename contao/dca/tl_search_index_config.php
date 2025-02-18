<?php

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_search_index_config'] =
[
    // Config
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'tstamp' => 'index',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['name'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;search,limit',
            'defaultSearchField' => 'name',
        ],
        'label' => [
            'fields' => ['name'],
            'format' => '%s',
        ],
    ],

    // Palettes
    'palettes' => [
        '__selector__' => ['providerFactory'],
        'default' => '{title_legend},name;{config_legend},adapter,providerFactory;',
        'standard' => '{title_legend},name;{config_legend},adapter,providerFactory;{provider_config},urls,canonicals',
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp' => [
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'name' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'adapter' => [
            'inputType' => 'select',
            'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'providerFactory' => [
            'search' => true,
            'inputType' => 'select',
            'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'tl_class' => 'w50', 'submitOnChange' => true],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],

        'urls' => [
            'inputType' => 'listWizard',
            'eval' => ['multiple' => true, 'decodeEntities' => true, 'tl_class' => 'clr'],
            'sql' => ['type' => 'text', 'notnull' => false],
        ],
        'canonicals' => [
            'inputType' => 'listWizard',
            'eval' => ['multiple' => true, 'decodeEntities' => true, 'tl_class' => 'clr'],
            'sql' => ['type' => 'text', 'notnull' => false],
        ],
    ],
];
