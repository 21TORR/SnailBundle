<?php declare(strict_types=1);

namespace Tests\Torr\Snail\Snail;

use Torr\Snail\Exception\SnailGenerationFailedException;
use Torr\Snail\Snail\SnailHelper;
use PHPUnit\Framework\TestCase;

class SnailHelperTest extends TestCase
{
	/**
	 *
	 */
	public static function provideIsValid () : iterable
 	{
		yield "plain" => ["test"];
		yield "with dash" => ["a-b"];
		yield "with underscore" => ["a-b_c"];
		yield "all characters" => ["a-b_c.d"];
		yield "numbers" => ["5"];
		yield "numbers longer" => ["1-2-3-4"];
	}

	/**
	 * @dataProvider provideIsValid
	 */
	public function testIsValid (string $input) : void
	{
		self::assertTrue(SnailHelper::isValidSnail($input));
	}

	/**
	 *
	 */
	public static function provideIsInvalid () : iterable
 	{
		yield "empty" => [""];
		yield "dash at the end" => ["test-"];
		yield "dot at the end" => ["test."];
		yield "underscore at the end" => ["test_"];
		yield "dash at the beginning" => ["-test"];
		yield "dot at the beginning" => ["_test"];
		yield "underscore at the beginning" => [".test"];
		yield "double dash" => ["a--b"];
		yield "special characters" => ["a@b"];
		yield "upper case characters" => ["aBc"];
	}

	/**
	 * @dataProvider provideIsInvalid
	 */
	public function testIsInvalid (string $input) : void
	{
		self::assertFalse(SnailHelper::isValidSnail($input));
	}



	/**
	 *
	 */
	public static function provideGenerateValid () : iterable
	{
		yield "uppercase" => ["UPPERCASE-lower", "uppercase-lower"];
		yield "umlauts" => ["äöü", "aou"];
	}


	/**
	 * @dataProvider provideGenerateValid
	 */
	public function testGenerateValid (string $input, string $expected) : void
	{
		$helper = new SnailHelper();

		self::assertSame(
			$expected,
			$helper->generateSnail($input),
		);
	}

	/**
	 *
	 */
	public static function provideGenerateInvalid () : iterable
	{
		yield "empty" => [""];
		yield "invalid-chars" => ["@"];
	}


	/**
	 * @dataProvider provideGenerateInvalid
	 */
	public function testGenerateInvalid (string $input) : void
	{
		$this->expectException(SnailGenerationFailedException::class);
		$this->expectExceptionMessage(sprintf(
			"Could not generate snail from text '%s', as the result would be empty.",
			$input,
		));

		$helper = new SnailHelper();
		$helper->generateSnail($input);
	}
}
