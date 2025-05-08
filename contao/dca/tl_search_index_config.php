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
        'standard' => '{title_legend},name;{config_legend},adapter,providerFactory;{provider_general_config},queryParameter,perPage,highlightTag,urls,canonicals;{image_legend},imgSize;{template_legend},template;',
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true],
        ],
        'tstamp' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
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
            'filter' => true,
            'inputType' => 'select',
            'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'tl_class' => 'w50', 'submitOnChange' => true],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'queryParameter' => [
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'tl_class' => 'w33'],
            'sql' => ['type' => 'string', 'length' => 32, 'default' => 'keywords'],
        ],
        'perPage' => [
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'tl_class' => 'w33', 'rgxp' => 'natural'],
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 10],
        ],
        'highlightTag' => [
            'inputType' => 'select',
            'options' => ['em', 'mark', 'strong'],
            'eval' => ['mandatory' => true, 'tl_class' => 'w33'],
            'sql' => ['type' => 'string', 'length' => 8, 'default' => 'em'],
        ],
        'urls' => [
            'inputType' => 'listWizard',
            'eval' => ['multiple' => true, 'decodeEntities' => true, 'tl_class' => 'w33 clr'],
            'sql' => ['type' => 'text', 'notnull' => false],
        ],
        'canonicals' => [
            'inputType' => 'listWizard',
            'eval' => ['multiple' => true, 'decodeEntities' => true, 'tl_class' => 'w33'],
            'sql' => ['type' => 'text', 'notnull' => false],
        ],
        'imgSize' => [
            'inputType' => 'imageSize',
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => ['rgxp' => 'natural', 'includeBlankOption' => true, 'nospace' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 128, 'default' => ''],
        ],
        'template' => [
            'inputType' => 'select',
            'eval' => ['tl_class' => 'w50 clr'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => null, 'notnull' => false],
        ],
    ],
];
