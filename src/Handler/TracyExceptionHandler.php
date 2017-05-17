<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Monolog\Handler;

use Kdyby\Monolog\Tracy\BlueScreenRenderer;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;



class TracyExceptionHandler extends AbstractProcessingHandler
{

	/**
	 * @var \Kdyby\Monolog\Tracy\BlueScreenRenderer
	 */
	private $blueScreenRenderer;



	public function __construct(BlueScreenRenderer $blueScreenRenderer, $level = Logger::DEBUG, $bubble = TRUE)
	{
		parent::__construct($level, $bubble);
		$this->blueScreenRenderer = $blueScreenRenderer;
	}



	/**
	 * {@inheritdoc}
	 */
	protected function write(array $record)
	{
		$exception = $record['context']['exception'];
		$filename = $this->blueScreenRenderer->getExceptionFile($exception);
		if (!file_exists($filename)) {
			$this->blueScreenRenderer->renderToFile($exception, $filename);
		}
	}



	/**
	 * {@inheritdoc}
	 */
	public function isHandling(array $record)
	{
		return parent::isHandling($record)
			&& !isset($record['context']['tracy'])
			&& isset($record['context']['exception'])
			&& $record['context']['exception'] instanceof \Throwable;
	}

}
