/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	config.toolbar=[
		{ name: 'document',    	items: [ 'Preview', 'Print' ] },
		{ name: 'clipboard',	items: [ 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo' ] },
		{ name: 'editing',		items: [ 'Scayt' ] },
		{ name: 'insert',		items: [ 'Image', 'Table', 'SpecialChar'] },
		'/',
		{ name: 'basicstyles',	items: [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript','-', 'RemoveFormat' ] },
		{ name: 'paragraph',	items: [ 'NumberedList', 'BulletedList', 'Blockquote', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ] },
		{ name: 'links',		items: [ 'Link', 'Unlink', 'Anchor' ] },
		'/',
		{ name: 'styles',		items: [ 'Styles', 'Format', 'Font', 'FontSize' ] },
		{ name: 'colors',		items: [ 'TextColor', 'BGColor' ] },
		{ name: 'tools',		items: [ 'Maximize'] }
		/*{ name: 'pbckcode',		items: [ 'pbckcode']}*/
	]; 
	/*config.extraPlugins = 'pbckcode';*/
};