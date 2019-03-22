<?php

declare(strict_types = 1);

/**
 * Test: Kdyby\Monolog\PriorityProcessor.
 *
 * @testCase
 */

namespace KdybyTests\Monolog;

use Kdyby\Monolog\Processor\PriorityProcessor;
use Tester\Assert;
use function call_user_func;

require_once __DIR__ . '/../bootstrap.php';

class PriorityProcessorTest extends \Tester\TestCase
{

	public function dataFunctional(): array
	{
		return [
			[
				['channel' => 'kdyby', 'context' => []],
				['channel' => 'kdyby', 'context' => ['priority' => 'debug']],
			],
			[
				['channel' => 'kdyby', 'context' => []],
				['channel' => 'kdyby', 'context' => ['priority' => 'info']],
			],
			[
				['channel' => 'kdyby', 'context' => []],
				['channel' => 'kdyby', 'context' => ['priority' => 'notice']],
			],
			[
				['channel' => 'kdyby', 'context' => []],
				['channel' => 'kdyby', 'context' => ['priority' => 'warning']],
			],
			[
				['channel' => 'kdyby', 'context' => []],
				['channel' => 'kdyby', 'context' => ['priority' => 'error']],
			],
			[
				['channel' => 'kdyby', 'context' => []],
				['channel' => 'kdyby', 'context' => ['priority' => 'critical']],
			],
			[
				['channel' => 'kdyby', 'context' => []],
				['channel' => 'kdyby', 'context' => ['priority' => 'alert']],
			],
			[
				['channel' => 'kdyby', 'context' => []],
				['channel' => 'kdyby', 'context' => ['priority' => 'emergency']],
			],

			// when bluescreen is rendered Tracy
			[
				['channel' => 'exception', 'context' => []],
				['channel' => 'kdyby', 'context' => ['priority' => 'exception']],
			],

			// custom priority
			[
				['channel' => 'nemam', 'context' => []],
				['channel' => 'kdyby', 'context' => ['priority' => 'nemam']],
			],

			// custom channel provided in $context parameter when adding record
			[
				['channel' => 'emails', 'context' => []],
				['channel' => 'kdyby', 'context' => ['channel' => 'emails']],
			],
			[
				['channel' => 'smses', 'context' => []],
				['channel' => 'kdyby', 'context' => ['channel' => 'smses']],
			],
		];
	}

	/**
	 * @dataProvider dataFunctional
	 * @param array $expectedRecord
	 * @param array $providedRecord
	 */
	public function testFunctional(array $expectedRecord, array $providedRecord): void
	{
		Assert::same($expectedRecord, call_user_func(new PriorityProcessor(), $providedRecord));
	}

}

(new PriorityProcessorTest())->run();
