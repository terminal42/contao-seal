<?php

$GLOBALS['TL_DCA']['tl_content']['palettes']['search'] = '{type_legend},type,headline;{config_legend},search_index,perPage;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['search_index'] = [
    'inputType' => 'select',
    'eval' => ['mandatory' => true],
    'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
];
