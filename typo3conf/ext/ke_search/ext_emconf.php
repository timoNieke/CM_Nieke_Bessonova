<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "ke_search".
 *
 * Auto generated 26-06-2022 21:49
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
  'title' => 'Faceted Search',
  'description' => 'Faceted fulltext search for TYPO3. Fast, flexible and easy to install and use. Indexes content directly from the databases. Features faceting / filtering, file indexing, images in result lists and respects access restrictions.',
  'category' => 'plugin',
  'version' => '4.4.5',
  'state' => 'stable',
  'uploadfolder' => false,
  'clearcacheonload' => false,
  'author' => 'ke_search Dev Team',
  'author_email' => 'ke_search@tpwd.de',
  'author_company' => 'The People Who Do TPWD GmbH',
  'constraints' => 
  array (
    'depends' => 
    array (
      'php' => '7.4.0-8.9.99',
      'typo3' => '10.4.11-11.5.99',
    ),
    'suggests' => 
    array (
      'dashboard' => '10.4.11-11.5.99',
    ),
    'conflicts' => 
    array (
    ),
  ),
);

