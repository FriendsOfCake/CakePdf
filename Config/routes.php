<?php
	$extensions = array_merge(array('pdf'), Router::extensions());
	call_user_func_array('Router::parseExtensions', $extensions);