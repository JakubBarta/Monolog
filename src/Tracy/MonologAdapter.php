<?php

declare(strict_types = 1);

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

use Kdyby\Monolog\Tracy\MonologAdapter24;
use Kdyby\Monolog\Tracy\MonologAdapter25;
use Tracy\Debugger;

$parts = explode('.', Debugger::VERSION);

if ($parts[0] === '2' && $parts[1] === '6') {
	class_alias(MonologAdapter24::class, '\Kdyby\Monolog\Tracy\MonologAdapter');
} else {
	class_alias(MonologAdapter25::class, '\Kdyby\Monolog\Tracy\MonologAdapter');
}
