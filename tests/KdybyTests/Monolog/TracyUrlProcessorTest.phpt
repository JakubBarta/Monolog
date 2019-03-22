<?php

declare(strict_types = 1);

/**
 * Test: Kdyby\Monolog\Processor\TracyUrlProcessor.
 *
 * @testCase
 */

namespace KdybyTests\Monolog;

use Kdyby\Monolog\Processor\TracyUrlProcessor;
use Kdyby\Monolog\Tracy\BlueScreenRenderer;
use Tester\Assert;
use Tracy\BlueScreen;
use function call_user_func;

require_once __DIR__ . '/../bootstrap.php';

class TracyUrlProcessorTest extends \Tester\TestCase
{

	/**
	 * @var \Kdyby\Monolog\Tracy\BlueScreenRenderer
	 */
	private $blueScreenRenderer;

	/**
	 * @var \Kdyby\Monolog\Processor\TracyUrlProcessor
	 */
	private $processor;

	protected function setUp(): void
	{
		$this->blueScreenRenderer = new BlueScreenRenderer(TEMP_DIR, new BlueScreen());
		$this->processor = new TracyUrlProcessor('https://exceptions.kdyby.org', $this->blueScreenRenderer);
	}

	public function testProcessWithException(): void
	{
		$exception = new \RuntimeException(__FUNCTION__);
		$exceptionFile = basename($this->blueScreenRenderer->getExceptionFile($exception));

		$record = [
			'message' => 'Some error',
			'context' => [
				'exception' => $exception,
			],
		];
		$processed = call_user_func($this->processor, $record);
		Assert::same('https://exceptions.kdyby.org/' . $exceptionFile, $processed['context']['tracyUrl']);
	}

	public function testIgnoreProcessWithoutException(): void
	{
		$record = [
			'message' => 'Some error',
			'context' => [
				'tracy' => 'exception--2016-01-17--17-54--72aee7b518.html',
			],
		];
		$processed = call_user_func($this->processor, $record);
		Assert::false(isset($processed['context']['tracyUrl']));
	}

}

(new TracyUrlProcessorTest())->run();
