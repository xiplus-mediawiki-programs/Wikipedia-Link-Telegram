<?php

$finder = PhpCsFixer\Finder::create()
	->exclude('function/Mediawiki-urlencode')
	->in(__DIR__)
;

$config = new PhpCsFixer\Config();
return $config->setRules([
		'@PSR12' => true,
		'indentation_type' => true,
		'single_space_after_construct' => true,
	])
	->setFinder($finder)
	->setIndent("\t")
;
