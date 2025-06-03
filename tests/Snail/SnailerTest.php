<?php declare(strict_types=1);

namespace Tests\Torr\Snail\Snail;

use PHPUnit\Framework\TestCase;
use Torr\Snail\Exception\SnailGenerationFailedException;
use Torr\Snail\Snail\Snailer;

/**
 * @internal
 */
final class SnailerTest extends TestCase
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
		self::assertTrue(Snailer::isValidSnail($input));
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
		yield "special characters list" => ["a-._b"];
	}

	/**
	 * @dataProvider provideIsInvalid
	 */
	public function testIsInvalid (string $input) : void
	{
		self::assertFalse(Snailer::isValidSnail($input));
	}

	/**
	 *
	 */
	public static function provideGenerateValid () : iterable
	{
		yield "uppercase" => ["UPPERCASE-lower", "uppercase-lower"];
		yield "trailing and leading special characters" => ["-test-", "test"];
		yield "umlauts" => ["äöü", "aou"];
		yield "collapse special characters list to first" => ["a-._b", "a-b"];
	}

	/**
	 * @dataProvider provideGenerateValid
	 */
	public function testGenerateValid (string $input, string $expected) : void
	{
		$helper = new Snailer();

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
		$this->expectExceptionMessage(\sprintf(
			"Could not generate snail from text '%s', as the result would be empty.",
			$input,
		));

		$helper = new Snailer();
		$helper->generateSnail($input);
	}
}
