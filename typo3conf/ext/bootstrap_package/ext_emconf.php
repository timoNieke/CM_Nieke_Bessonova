<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "bootstrap_package".
 *
 * Auto generated 19-05-2022 18:25
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
  'title' => 'Bootstrap Package',
  'description' => 'Bootstrap Package delivers a full configured frontend theme for TYPO3, based on the Bootstrap CSS Framework.',
  'category' => 'templates',
  'version' => '12.0.5',
  'state' => 'stable',
  'uploadfolder' => false,
  'clearcacheonload' => false,
  'author' => 'Benjamin Kott',
  'author_email' => 'info@bk2k.info',
  'author_company' => 'private',
  'constraints' => 
  array (
    'depends' => 
    array (
      'typo3' => '10.4.0-11.5.99',
      'rte_ckeditor' => '10.4.0-11.5.99',
      'seo' => '10.4.0-11.5.99',
    ),
    'conflicts' => 
    array (
      'css_styled_content' => '*',
      'fluid_styled_content' => '*',
      'themes' => '*',
      'fluidpages' => '*',
      'dyncss' => '*',
    ),
    'suggests' => 
    array (
    ),
  ),
);

