<?php
/**
* Collection of tests for the ParserSequence class
*
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
* @copyright Copyright (c) 2010 Bastian Feder, Thomas Weinert
*
* @package PhpCss
* @subpackage Tests
*/

/**
* Load necessary files
*/
require_once(dirname(dirname(__FILE__)).'/TestCase.php');

/**
* Test class for PhpCssParserSequence.
*
* @package PhpCss
* @subpackage Tests
*/
class PhpCssParserSequenceTest extends PhpCssTestCase {

  /**
  * @covers PhpCssParserSequence::parse
  * @dataProvider provideParseData
  */
  public function testParse($expected, $tokens) {
    $parser = new PhpCssParserSequence($tokens);
    $this->assertEquals(
      $expected, $parser->parse()
    );
  }

  public static function provideParseData() {
    return array(
      'element' => array(
        new PhpCssAstSelectorSequence(
          array(new PhpCssAstSelectorSimpleType('element'))
        ),
        array(
          new PhpCssScannerToken(
            PhpCssScannerToken::TYPE_SELECTOR,
            'element',
            0
          )
        )
      ),
      'element with prefix' => array(
        new PhpCssAstSelectorSequence(
          array(new PhpCssAstSelectorSimpleType('element', 'prefix'))
        ),
        array(
          new PhpCssScannerToken(
            PhpCssScannerToken::TYPE_SELECTOR,
            'prefix:element',
            0
          )
        )
      ),
      'class' => array(
        new PhpCssAstSelectorSequence(
          array(new PhpCssAstSelectorSimpleClass('classname'))
        ),
        array(
          new PhpCssScannerToken(
            PhpCssScannerToken::CLASS_SELECTOR,
            '.classname',
            0
          )
        )
      ),
      'id' => array(
        new PhpCssAstSelectorSequence(
          array(new PhpCssAstSelectorSimpleId('id'))
        ),
        array(
          new PhpCssScannerToken(
            PhpCssScannerToken::ID_SELECTOR,
            '#id',
            0
          )
        )
      ),
      'element.class' => array(
        new PhpCssAstSelectorSequence(
          array(
            new PhpCssAstSelectorSimpleType('element'),
            new PhpCssAstSelectorSimpleClass('classname')
          )
        ),
        array(
          new PhpCssScannerToken(
            PhpCssScannerToken::TYPE_SELECTOR,
            'element',
            0
          ),
          new PhpCssScannerToken(
            PhpCssScannerToken::CLASS_SELECTOR,
            '.classname',
            7
          )
        )
      )
    );
  }
}