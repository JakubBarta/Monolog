<?php

declare(strict_types = 1);

/**
 * Test: Kdyby\Monolog\FallbackNetteHandler.
 *
 * @testCase
 */

namespace KdybyTests\Monolog;

use DateTime;
use Kdyby\Monolog\Handler\FallbackNetteHandler;
use Monolog\Logger as MonologLogger;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class FallbackNetteHandlerTest extends \Tester\TestCase
{

	/**
	 * @var \Kdyby\Monolog\Handler\FallbackNetteHandler
	 */
	private $handler;

	/**
	 * @var \DateTime
	 */
	private $now;

	/**
	 * @var string
	 */
	private $logDir;

	protected function setUp(): void
	{
		$this->logDir = TEMP_DIR . '/log_' . getmypid() . '_' . number_format(microtime(TRUE), 6, '+', '');
		@mkdir($this->logDir, 0777, TRUE);

		foreach (glob($this->logDir . '/*.log') as $logFile) {
			unlink($logFile);
		}

		$this->handler = new FallbackNetteHandler('kdyby', $this->logDir);

		$this->now = new DateTime();
	}

	/**
	 * @return array<int, array <int, string>>
	 */
	public function dataWriteStandardLevels(): array
	{
		return [
			[MonologLogger::DEBUG, 'debug'],
			[MonologLogger::INFO, 'info'],
			[MonologLogger::NOTICE, 'notice'],
			[MonologLogger::WARNING, 'warning'],
			[MonologLogger::ERROR, 'error'],
			[MonologLogger::CRITICAL, 'critical'],
			[MonologLogger::ALERT, 'alert'],
			[MonologLogger::EMERGENCY, 'emergency'],
		];
	}

	/**
	 * @dataProvider dataWriteStandardLevels
	 * @param int $level
	 * @param string $levelName
	 */
	public function testWriteStandardLevels(int $level, string $levelName): void
	{
		$this->handler->handle([
			'message' => 'test message',
			'context' => [],
			'level' => $level,
			'level_name' => strtoupper($levelName),
			'channel' => 'kdyby',
			'datetime' => $this->now,
			'extra' => [],
		]);

		Assert::match(
			'[%a%] test message [] []',
			file_get_contents($this->logDir . '/' . $levelName . '.log')
		);
	}

	public function testWriteCustomChannel(): void
	{
		$this->handler->handle([
			'message' => 'test message',
			'context' => [],
			'level' => MonologLogger::INFO,
			'level_name' => 'INFO',
			'channel' => 'nemam',
			'datetime' => $this->now,
			'extra' => [],
		]);

		$this->handler->handle([
			'message' => 'test message',
			'context' => [],
			'level' => MonologLogger::WARNING,
			'level_name' => 'WARNING',
			'channel' => 'nemam',
			'datetime' => $this->now,
			'extra' => [],
		]);

		Assert::match(
			'[%a%] INFO: test message [] []' . "\n" .
			'[%a%] WARNING: test message [] []',
			file_get_contents($this->logDir . '/nemam.log')
		);
	}

	public function testWriteContextAsJson(): void
	{
		$this->handler->handle([
			'message' => 'test message',
			'context' => ['at' => 'http://www.kdyby.org', 'tracy' => 'exception-2014-08-14-11-11-26-88167e58be9dc0dfd12a61b3d8d33838.html'],
			'level' => MonologLogger::INFO,
			'level_name' => 'INFO',
			'channel' => 'custom',
			'datetime' => $this->now,
			'extra' => [],
		]);

		Assert::match(
			'[%a%] INFO: test message {"at":"http://www.kdyby.org","tracy":"exception-2014-08-14-11-11-26-88167e58be9dc0dfd12a61b3d8d33838.html"} []',
			file_get_contents($this->logDir . '/custom.log')
		);
	}

	public function testWriteExtraAsJson(): void
	{
		$this->handler->handle([
			'message' => 'test message',
			'context' => [],
			'level' => MonologLogger::INFO,
			'level_name' => 'INFO',
			'channel' => 'custom',
			'datetime' => $this->now,
			'extra' => ['secret' => 'no animals were harmed during writing this test case'],
		]);

		Assert::match(
			'[%a%] INFO: test message [] {"secret":"no animals were harmed during writing this test case"}',
			file_get_contents($this->logDir . '/custom.log')
		);
	}

}

(new FallbackNetteHandlerTest())->run();
