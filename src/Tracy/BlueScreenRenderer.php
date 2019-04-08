<?php

declare(strict_types = 1);

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

use Kdyby\Monolog\Tracy\BlueScreenRenderer24;
use Kdyby\Monolog\Tracy\BlueScreenRenderer25;
use Tracy\Debugger;

$parts = explode('.', Debugger::VERSION);

if ($parts[0] === '2' && $parts[1] === '6') {
	class_alias(BlueScreenRenderer24::class, '\Kdyby\Monolog\Tracy\BlueScreenRenderer');
} else {
	class_alias(BlueScreenRenderer25::class, '\Kdyby\Monolog\Tracy\BlueScreenRenderer');
}
