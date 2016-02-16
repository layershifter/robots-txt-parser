<?php

class EmptyDisallowTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Load library
	 */
	public static function setUpBeforeClass()
	{
		require_once(realpath(__DIR__.'/../Parser.php'));
	}

	/**
	 * @dataProvider generateDataForTest
	 *
	 * @param $robotsTxtContent
	 */
	public function testEmptyDisallow($robotsTxtContent)
	{
		// init parser
		$parser = new Parser($robotsTxtContent);
		$this->assertInstanceOf('Parser', $parser);

		$rules = $parser->getRules('*');
		$this->assertNotEmpty($rules);
		$this->assertArrayHasKey('disallow', $rules);
		$this->assertEquals(2, count($rules['disallow']));
	}

	/**
	 * Generate test case data
	 * @return array
	 */
	public function generateDataForTest()
	{
		return array(
			array('
				User-Agent: *
				Disallow:
				Disallow: /foo
				Disallow: /bar
			')
		);
	}
}
