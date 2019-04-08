<?php

declare(strict_types = 1);

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Monolog\Tracy;

use Tracy\BlueScreen;

class BlueScreenRenderer24 extends \Tracy\Logger
{

	use \Kdyby\StrictObjects\Scream;

	public function __construct(string $directory, BlueScreen $blueScreen)
	{
		parent::__construct($directory, NULL, $blueScreen);
	}

	/**
	 * @param \Exception|\Throwable $exception
	 * @param string $file
	 * @return string logged error filename
	 */
	public function renderToFile(\Throwable $exception, string $file): string
	{
		return parent::logException($exception, $file);
	}

	/**
	 * @internal
	 * @deprecated
	 * @param mixed $message
	 * @param string $priority
	 * @return string|null
	 */
	public function log($message, $priority = self::INFO)
	{
		throw new \Kdyby\Monolog\Exception\NotSupportedException('This class is only for rendering exceptions');
	}

	/**
	 * @internal
	 * @deprecated
	 * @param mixed $message
	 * @param string $email
	 */
	public function defaultMailer($message, string $email): void
	{
		// pass
	}

}
