<?php

declare(strict_types = 1);

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace Kdyby\Monolog\DI;

use Kdyby\Monolog\Handler\FallbackNetteHandler;
use Kdyby\Monolog\Logger as KdybyLogger;
use Kdyby\Monolog\Processor\PriorityProcessor;
use Kdyby\Monolog\Processor\TracyExceptionProcessor;
use Kdyby\Monolog\Processor\TracyUrlProcessor;
use Kdyby\Monolog\Tracy\BlueScreenRenderer;
use Kdyby\Monolog\Tracy\MonologAdapter;
use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\DI\Helpers as DIHelpers;
use Nette\PhpGenerator\ClassType as ClassTypeGenerator;
use Nette\PhpGenerator\PhpLiteral;
use Psr\Log\LoggerAwareInterface;
use Tracy\Debugger;
use Tracy\ILogger;

/**
 * Integrates the Monolog seamlessly into your Nette Framework application.
 */
class MonologExtension extends \Nette\DI\CompilerExtension
{

	private const TAG_HANDLER = 'monolog.handler';
	private const TAG_PROCESSOR = 'monolog.processor';
	private const TAG_PRIORITY = 'monolog.priority';

	/**
	 * @var mixed[]
	 */
	private $defaults = [
		'handlers' => [],
		'processors' => [],
		'name' => 'app',
		'hookToTracy' => TRUE,
		'tracyBaseUrl' => NULL,
		'usePriorityProcessor' => TRUE,
		'registerFallback' => FALSE,
		'accessPriority' => ILogger::INFO,
	];

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$config = $this->validateConfig($this->defaults);
		$config['logDir'] = self::resolveLogDir($builder->parameters);
		self::createDirectory($config['logDir']);
		$this->setConfig($config);

		if (!isset($builder->parameters[$this->name]) || (is_array($builder->parameters[$this->name]) && !isset($builder->parameters[$this->name]['name']))) {
			$builder->parameters[$this->name]['name'] = $config['name'];
		}

		if (!isset($builder->parameters['logDir'])) { // BC
			$builder->parameters['logDir'] = $config['logDir'];
		}

		$builder->addDefinition($this->prefix('logger'))
			->setType(KdybyLogger::class)
			->setArguments([$config['name']]);

		// Tracy adapter
		$builder->addDefinition($this->prefix('adapter'))
			->setType(MonologAdapter::class)
			->setArguments([
				'monolog' => $this->prefix('@logger'),
				'blueScreenRenderer' => $this->prefix('@blueScreenRenderer'),
				'email' => Debugger::$email,
				'accessPriority' => $config['accessPriority'],
			])
			->addTag('logger');

		// The renderer has to be separate, to solve circural service dependencies
		$builder->addDefinition($this->prefix('blueScreenRenderer'))
			->setType(BlueScreenRenderer::class)
			->setArguments([
				'directory' => $config['logDir'],
			])
			->setAutowired(FALSE)
			->addTag('logger');

		if ($config['hookToTracy'] === TRUE && $builder->hasDefinition('tracy.logger')) {
			// TracyExtension initializes the logger from DIC, if definition is changed
			$builder->removeDefinition($existing = 'tracy.logger');
			$builder->addAlias($existing, $this->prefix('adapter'));
		}

		$this->loadHandlers($config);
		$this->loadProcessors($config);
	}

	protected function loadHandlers(array $config): void
	{
		$builder = $this->getContainerBuilder();

		foreach ($config['handlers'] as $handlerName => $implementation) {
			$this->compiler->loadDefinitionsFromConfig([
				$serviceName = $this->prefix('handler.' . $handlerName) => $implementation,
			]);

			$builder->getDefinition($serviceName)
				->setAutowired(FALSE)
				->addTag(self::TAG_HANDLER)
				->addTag(self::TAG_PRIORITY, is_numeric($handlerName) ? $handlerName : 0);
		}
	}

	/**
	 * @param array $config
	 */
	protected function loadProcessors(array $config): void
	{
		$builder = $this->getContainerBuilder();

		if ($config['usePriorityProcessor'] === TRUE) {
			// change channel name to priority if available
			$builder->addDefinition($this->prefix('processor.priorityProcessor'))
				->setType(PriorityProcessor::class)
				->addTag(self::TAG_PROCESSOR)
				->addTag(self::TAG_PRIORITY, 20);
		}

		$builder->addDefinition($this->prefix('processor.tracyException'))
			->setType(TracyExceptionProcessor::class)
			->setArguments([
				'blueScreenRenderer' => $this->prefix('@blueScreenRenderer'),
			])
			->addTag(self::TAG_PROCESSOR)
			->addTag(self::TAG_PRIORITY, 100);

		if ($config['tracyBaseUrl'] !== NULL) {
			$builder->addDefinition($this->prefix('processor.tracyBaseUrl'))
				->setType(TracyUrlProcessor::class)
				->setArguments([
					'baseUrl' => $config['tracyBaseUrl'],
					'blueScreenRenderer' => $this->prefix('@blueScreenRenderer'),
				])
				->addTag(self::TAG_PROCESSOR)
				->addTag(self::TAG_PRIORITY, 10);
		}

		foreach ($config['processors'] as $processorName => $implementation) {
			$this->compiler->loadDefinitionsFromConfig([
				$serviceName = $this->prefix('processor.' . $processorName) => $implementation,
			]);

			$builder->getDefinition($serviceName)
				->addTag(self::TAG_PROCESSOR)
				->addTag(self::TAG_PRIORITY, is_numeric($processorName) ? $processorName : 0);
		}
	}

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		$logger = $builder->getDefinition($this->prefix('logger'));

		if (!$logger instanceof ServiceDefinition) {
			throw new \Nette\InvalidStateException(
				'Logger should be instance of ServiceDefinition, actual type is ' . get_class($logger)
			);
		}

		$handlers = $this->findByTagSorted(self::TAG_HANDLER);
		$serviceNames = array_keys($handlers);
		foreach ($serviceNames as $serviceName) {
			$logger->addSetup('pushHandler', ['@' . $serviceName]);
		}

		$serviceNames = array_keys($this->findByTagSorted(self::TAG_PROCESSOR));
		foreach ($serviceNames as $serviceName) {
			$logger->addSetup('pushProcessor', ['@' . $serviceName]);
		}

		/** @var array $config */
		$config = $this->getConfig();

		// use fallback handler if no handlers are set up or user forces it
		if (count($handlers) === 0 || $config['registerFallback']) {
			$logger->addSetup('pushHandler', [
				new Statement(FallbackNetteHandler::class, [
					'appName' => $config['name'],
					'logDir' => $config['logDir'],
				]),
			]);
		}

		foreach ($builder->findByType(LoggerAwareInterface::class) as $service) {
			if (!$service instanceof ServiceDefinition) {
				throw new \Nette\InvalidStateException(
					'Service should be instance of ServiceDefinition, actual type is ' . get_class($logger)
				);
			}

			$service->addSetup('setLogger', ['@' . $this->prefix('logger')]);
		}
	}

	protected function findByTagSorted(string $tag): array
	{
		$builder = $this->getContainerBuilder();

		$services = $builder->findByTag($tag);
		uksort($services, static function ($nameA, $nameB) use ($builder) {
			$pa = $builder->getDefinition($nameA)->getTag(self::TAG_PRIORITY) ?: 0;
			$pb = $builder->getDefinition($nameB)->getTag(self::TAG_PRIORITY) ?: 0;
			return $pa > $pb ? 1 : ($pa < $pb ? -1 : 0);
		});

		return $services;
	}

	public function afterCompile(ClassTypeGenerator $class): void
	{
		$initialize = $class->getMethod('initialize');

		/** @var array $config */
		$config = $this->config;

		if (empty(Debugger::$logDirectory)) {
			$initialize->addBody('?::$logDirectory = ?;', [new PhpLiteral(Debugger::class), $config['logDir']]);
		}
	}

	public static function register(Configurator $configurator): void
	{
		$configurator->onCompile[] = static function ($config, Compiler $compiler): void {
			$compiler->addExtension('monolog', new MonologExtension());
		};
	}

	private static function resolveLogDir(array $parameters): string
	{
		if (isset($parameters['logDir'])) {
			return DIHelpers::expand('%logDir%', $parameters);
		}

		if (Debugger::$logDirectory !== NULL) {
			return Debugger::$logDirectory;
		}

		return DIHelpers::expand('%appDir%/../log', $parameters);
	}

	private static function createDirectory(string $logDir): void
	{
		if (!@mkdir($logDir, 0777, TRUE) && !is_dir($logDir)) {
			throw new \RuntimeException(sprintf('Log dir %s cannot be created', $logDir));
		}
	}

}
