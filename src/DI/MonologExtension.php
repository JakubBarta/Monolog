<?php

declare(strict_types = 1);

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

use Kdyby\Monolog\DI\MonologExtension24;
use Kdyby\Monolog\DI\MonologExtension30;

if (class_exists('\Nette\DI\Definitions\ServiceDefinition')) {
	class_alias(MonologExtension30::class, 'Kdyby\Monolog\DI\MonologExtension');
} else {
	class_alias(MonologExtension24::class, 'Kdyby\Monolog\DI\MonologExtension');
}
