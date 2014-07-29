<?php
/*
 * jQuery File Upload Plugin PHP Example 5.14
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */
//defined('_JEXEC') or die('You are dead');

error_reporting(E_ALL | E_STRICT);
require('UploadHandler.php');


$options = array(
            'upload_dir' => '/var/www/joomla3b/administrator/components/com_imc/models/fields/server/php/files2/',
            'upload_url' => 'http://localhost/joomla3b/administrator/components/com_imc/models/fields/server/php/files2/'
        );



$upload_handler = new UploadHandler($options);
