<?php
/**
 * Groups configuration for default Minify implementation
 * @package Minify
 */

/** 
 * You may wish to use the Minify URI Builder app to suggest
 * changes. http:yourdomain/min/builder/
 *
 * See http:code.google.com/p/minify/wiki/CustomSource for other ideas
 **/

return array(
	'css_i' => array('//css/style.css','//css/bootstrap.css','//css/bootstrap-responsive.css'),
	'css_d' => array('//DataTables/css/jquery.dataTables.css','//css/jquery-ui-1.10.3.custom.css'),
	'css_m' => array('//css/bootstrap-wysihtml5.css'),
	
	'js_i' => array('//js/jquery-1.10.2.js','//js/bootstrap.min.js','//js/noty/jquery.noty.js','//js/noty/layouts/top.js','//js/noty/themes/default.js','//js/jquery.nimble.loader.js'),
	'js_m' => array('//js/wysihtml5-0.3.0.min.js','//js/bootstrap-wysihtml5.js'),
	'js_d' => array('//DataTables/js/jquery.dataTables.min.js','//js/jquery-ui-1.10.3.custom.min.js')
);