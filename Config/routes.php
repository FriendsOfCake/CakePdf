<?php
	$extensions = array_merge(array('pdf'), Router::extensions());
	Router::parseExtensions($extensions);