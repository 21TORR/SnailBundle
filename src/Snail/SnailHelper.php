<?php declare(strict_types=1);

namespace Torr\Snail\Snail;

use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\UnicodeString;
use Torr\Snail\Exception\SnailGenerationFailedException;
use function Symfony\Component\String\u;

/**
 * @final
 */
class SnailHelper
{
	private const LOCALE_TO_TRANSLITERATOR_ID = [
		'am' => 'Amharic-Latin',
		'ar' => 'Arabic-Latin',
		'az' => 'Azerbaijani-Latin',
		'be' => 'Belarusian-Latin',
		'bg' => 'Bulgarian-Latin',
		'bn' => 'Bengali-Latin',
		'de' => 'de-ASCII',
		'el' => 'Greek-Latin',
		'fa' => 'Persian-Latin',
		'he' => 'Hebrew-Latin',
		'hy' => 'Armenian-Latin',
		'ka' => 'Georgian-Latin',
		'kk' => 'Kazakh-Latin',
		'ky' => 'Kirghiz-Latin',
		'ko' => 'Korean-Latin',
		'mk' => 'Macedonian-Latin',
		'mn' => 'Mongolian-Latin',
		'or' => 'Oriya-Latin',
		'ps' => 'Pashto-Latin',
		'ru' => 'Russian-Latin',
		'sr' => 'Serbian-Latin',
		'sr_Cyrl' => 'Serbian-Latin',
		'th' => 'Thai-Latin',
		'tk' => 'Turkmen-Latin',
		'uk' => 'Ukrainian-Latin',
		'uz' => 'Uzbek-Latin',
		'zh' => 'Han-Latin',
	];

	/**
	 * Cache of transliterators per locale.
	 *
	 * @var \Transliterator[]
	 */
	private array $transliterators = [];

	/**
	 *
	 */
	public function isValidSnail (string $snail) : bool
	{
		return 0 !== preg_match('~^[a-z0-9]+([.\\-_][a-z0-9]+)*$~', $snail);
	}

	/**
	 *
	 */
	public function generateSnail (string $text, ?string $locale = null) : string
	{
		$string = u($text);

		$string = null !== $locale
			? $string->localeLower($locale)
			: $string->lower();

		$transliterator = [];
		if ($locale && ('de' === $locale || str_starts_with($locale, 'de_')))
		{
			// Use the shortcut for German in UnicodeString::ascii() if possible (faster and no requirement on intl)
			$transliterator = ['de-ASCII'];
		}
		elseif (\function_exists('transliterator_transliterate') && $locale)
		{
			$transliterator = (array) $this->createTransliterator($locale);
		}

		$transformed = $string
			->ascii($transliterator)
			->replaceMatches('~[^a-z0-9._-]+~', '-')
			->replaceMatches('~--+~', '-')
			->trim("._-")
			->toString();

		if ("" === $transformed)
		{
			throw new SnailGenerationFailedException(
				sprintf(
					"Could not generate snail from text '%s', as the result would be empty.",
					$text,
				),
			);
		}

		return $transformed;
	}

	/**
	 * Helper to create a transliterator.
	 *
	 * {@see AsciiSlugger::createTransliterator()}
	 */
	private function createTransliterator (string $locale) : ?\Transliterator
	{
		if (\array_key_exists($locale, $this->transliterators))
		{
			return $this->transliterators[$locale];
		}

		// Exact locale supported, cache and return
		if ($id = self::LOCALE_TO_TRANSLITERATOR_ID[$locale] ?? null)
		{
			return $this->transliterators[$locale] =
				\Transliterator::create($id . '/BGN') ?? \Transliterator::create($id);
		}

		// Locale is not supported and there is no parent, fallback to any-latin
		if (!$parent = self::getParentLocale($locale))
		{
			return $this->transliterators[$locale] = null;
		}

		// Try to use the parent locale (ie. try "de" for "de_AT") and cache both locales
		if ($id = self::LOCALE_TO_TRANSLITERATOR_ID[$parent] ?? null)
		{
			$transliterator = \Transliterator::create($id . '/BGN') ?? \Transliterator::create($id);
		}

		return $this->transliterators[$locale] = $this->transliterators[$parent] = $transliterator ?? null;
	}

	/**
	 *  Helper to create a parent locale.
	 *
	 *  {@see AsciiSlugger::getParentLocale()}
	 */
	private static function getParentLocale (?string $locale) : ?string
	{
		if (!$locale)
		{
			return null;
		}

		if (false === $str = strrchr($locale, '_'))
		{
			// no parent locale
			return null;
		}

		return substr($locale, 0, -\strlen($str));
	}
}
