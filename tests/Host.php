<?php

class HostTest extends \PHPUnit_Framework_TestCase
{
	public static function setUpBeforeClass()
	{
		require_once(realpath(__DIR__.'/../Parser.php'));
	}

	/**
	 * @dataProvider generateDataForTest
	 *
	 * @param      $robotsTxtContent
	 * @param null $expectedHost
	 * @param null $message
	 */
	public function testHost($robotsTxtContent, $expectedHost = NULL, $message = NULL)
	{
		// init parser
		$parser = new Parser($robotsTxtContent);
		$rules = $parser->getRules();
		$this->assertInstanceOf('Parser', $parser);
		$this->assertArrayHasKey('*', $rules, $message);

		if ($expectedHost) {
			$this->assertArrayHasKey('host', $rules['*'], $message);
			$this->assertEquals($expectedHost, $rules['*']['host'], $message);
		}
		else {
			$this->assertEmpty(@$rules['*']['host'], $message);
		}
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
				',
				NULL,
			),
			array('
					User-Agent: *
					Host: www.example.com
				',
				'www.example.com',
			),
			array('
					Host: example.com
				',
				'example.com',
			),
			array('
				Host: example.com
				User-Agent: google
				Host: www.example.com
				',
				'example.com',
				'expected first host value'
			),
			array('
				User-Agent: google
				Host: example.com
				Host: www.example.com
				',
				'example.com',
				'expected assign host to * because host is cross-section directive'
			),
		);
	}
}
