<?php

$GLOBALS['TL_DCA']['tl_content']['palettes']['seal_search'] = '{type_legend},type,headline;{config_legend},search_index;{template_legend:collapsed},customTpl;{protected_legend:collapsed},protected;{expert_legend:collapsed},cssID;{invisible_legend:collapsed},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['search_index'] = [
    'inputType' => 'select',
    'eval' => ['mandatory' => true],
    'sql' => ['type' => 'string', 'default' => ''],
];
