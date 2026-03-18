<?php

require_once __DIR__.'/hn_core/inc/bootstrap.php';

cache()->set('test','HN works');

echo cache()->get('test');
