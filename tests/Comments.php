<?php

class CommentsTest extends \PHPUnit_Framework_TestCase
{
	public static function setUpBeforeClass()
	{
		require_once(realpath(__DIR__.'/../Parser.php'));
	}

	/**
	 * @dataProvider generateDataForTest
	 *
	 * @param $robotsTxtContent
	 */
	public function testRemoveComments($robotsTxtContent)
	{
		$parser = new Parser($robotsTxtContent);
		$this->assertInstanceOf('Parser', $parser);

		$rules = $parser->getRules('*');

		$this->assertEmpty($rules, 'expected remove comments');
	}

	/**
	 * @dataProvider generateDataFor2Test
	 *
	 * @param $robotsTxtContent
	 * @param $expectedDisallowValue
	 */
	public function testRemoveCommentsFromValue($robotsTxtContent, $expectedDisallowValue)
	{
		$parser = new Parser($robotsTxtContent);
		$this->assertInstanceOf('Parser', $parser);

		$rules = $parser->getRules('*');

		$this->assertNotEmpty($rules, 'expected data');
		$this->assertArrayHasKey('disallow', $rules);
		$this->assertNotEmpty($rules['disallow'], 'disallow expected');
		$this->assertEquals($expectedDisallowValue, $rules['disallow'][0]);
	}

	/**
	 * Generate test case data
	 * @return array
	 */
	public function generateDataForTest()
	{
		return array(
			array('
				User-agent: *
				#Disallow: /tech
			'),
			array('
				User-agent: *
				Disallow: #/tech
			'),
			array('
				User-agent: *
				Disal # low: /tech
			'),
			array('
				User-agent: *
				Disallow#: /tech # ds
			'),
		);
	}

	/**
	 * Generate test case data
	 * @return array
	 */
	public function generateDataFor2Test()
	{
		return array(
			array(
				'User-agent: *
					Disallow: /tech #comment',
				'disallowValue' => '/tech',
			),
		);
	}
}
