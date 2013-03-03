<?php

// Setup the namespace
Autoloader::namespaces(array( 'AutoSchema' => __DIR__.'/lib' ));

// Create out aliases
Autoloader::alias('AutoSchema\AutoSchema', 'AutoSchema');
Autoloader::alias('AutoSchema\AutoForm', 'AutoForm');