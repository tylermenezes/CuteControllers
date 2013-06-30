<?php

require_once(implode(DIRECTORY_SEPARATOR, [dirname(dirname(__FILE__)), 'Exceptions', 'HttpError.php']));
require_once(implode(DIRECTORY_SEPARATOR, [dirname(dirname(__FILE__)), 'Exceptions', 'NoArgumentMatchException.php']));
require_once(implode(DIRECTORY_SEPARATOR, [dirname(dirname(__FILE__)), 'Exceptions', 'NoMethodMatchException.php']));
require_once(implode(DIRECTORY_SEPARATOR, [dirname(dirname(__FILE__)), 'Request.php']));
require_once(implode(DIRECTORY_SEPARATOR, [dirname(dirname(__FILE__)), 'Controller.php']));
require_once(implode(DIRECTORY_SEPARATOR, [dirname(dirname(__FILE__)), 'Router.php']));
