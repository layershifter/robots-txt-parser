<?php

class WhitespacesTest extends \PHPUnit_Framework_TestCase
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
	public function testWhitespaces($robotsTxtContent)
	{
		// init parser
		$parser = new Parser($robotsTxtContent);
		$this->assertInstanceOf('Parser', $parser);

		$rules = $parser->getRules('*');

		$this->assertNotEmpty($rules, 'expected rules for *');
		$this->assertArrayHasKey('disallow', $rules);
		$this->assertNotEmpty($rules['disallow'], 'disallow failed');
		$this->assertArrayHasKey('allow', $rules);
		$this->assertNotEmpty($rules['allow'], 'allow failed');
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
				Disallow : /admin
				Allow    :   /admin/front
			')
		);
	}
}
