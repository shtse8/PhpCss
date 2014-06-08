<?php
/**
* An ast visitor that compiles a dom document explaining the selector
*
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
* @copyright Copyright 2010-2014 PhpCss Team
*/
namespace PhpCss\Ast\Visitor  {

  use PhpCss\Ast;

  /**
  * An ast visitor that compiles a dom document explaining the selector
  */
  class Explain extends Overload {

    private $_xmlns = 'urn:carica-phpcss-explain-2014';

    /**
     * @var \DOMDocument
     */
    private $_dom = NULL;

    /**
     * @var \DOMElement
     */
    private $_current = NULL;

    public function __construct() {
      $this->clear();
    }

    /**
    * Clear the visitor object to visit another selector group
    */
    public function clear() {
      $this->_current = $this->_dom = new \DOMDocument();
    }

    /**
    * Return the collected selector string
    */
    public function __toString() {
      return $this->_dom->saveXml();
    }

    private function appendElement($name, $content = '', array $attributes = array()) {
      $result = $this->_current->appendChild(
        $this->_dom->createElementNs($this->_xmlns, $name)
      );
      if (!empty($content)) {
        $result
          ->appendChild(
            $this->_dom->createElementNs($this->_xmlns, 'text')
          )
          ->appendChild(
            $this->_dom->createTextNode($content)
          );
      }
      foreach ($attributes as $attribute => $value) {
        $result->setAttribute($attribute, $value);
      }
      return $result;
    }

    private function appendText($content) {
      return $this->_current
        ->appendChild(
          $this->_dom->createElementNs($this->_xmlns, 'text')
        )
        ->appendChild(
          $this->_dom->createTextNode($content)
        );
    }

    private function start($node) {
      $this->_current = $node;
      return TRUE;
    }

    private function end() {
      $this->_current = $this->_current->parentNode;
      return TRUE;
    }

    /**
    * Validate the buffer before vistiting a Ast\Selector\Group.
    * If the buffer already contains data, throw an exception.
    *
    * @throws \LogicException
    * @param Ast\Selector\Group $group
    * @return boolean
    */
    public function visitEnterSelectorGroup(Ast\Selector\Group $group) {
      $this->start($this->appendElement('selector-group'));
      return TRUE;
    }

    /**
    * If here is already data in the buffer, add a separator before starting the next.
    *
    * @return boolean
    */
    public function visitEnterSelectorSequence() {
      if (
        $this->_current === $this->_dom->documentElement &&
        $this->_current->hasChildNodes()
      ) {
        $this
          ->_current
          ->appendChild(
            $this->_dom->createElementNs($this->_xmlns, 'text')
          )
          ->appendChild(
            $this->_dom->createTextNode(', ')
          );
      }
      return $this->start($this->appendElement('selector'));
    }

    /**
     * @return bool
     */
    public function visitLeaveSelectorSequence() {
      return $this->end();
    }

    /**
    * @param Ast\Selector\Simple\Universal $universal
    * @return boolean
    */
    public function visitSelectorSimpleUniversal(Ast\Selector\Simple\Universal $universal) {
      if (!empty($universal->namespacePrefix) && $universal->namespacePrefix != '*') {
        $css = $universal->namespacePrefix.'|*';
      } else {
        $css = '*';
      }
      $this->appendElement('universal', $css);
      return TRUE;
    }

    /**
     * @param Ast\Selector\Simple\Type $type
     * @return bool
     */
    public function visitSelectorSimpleType(Ast\Selector\Simple\Type $type) {
      if (!empty($type->namespacePrefix) && $type->namespacePrefix != '*') {
        $css = $type->namespacePrefix.'|'.$type->elementName;
      } else {
        $css = $type->elementName;
      }
      $this->appendElement('type', $css);
      return TRUE;
    }

    /**
    * @param Ast\Selector\Simple\Id $id
    * @return boolean
    */
    public function visitSelectorSimpleId(Ast\Selector\Simple\Id $id) {
      $this->appendElement('id', '#'.$id->id);
      return TRUE;
    }

    /**
    * @param Ast\Selector\Simple\ClassName $class
    * @return boolean
    */
    public function visitSelectorSimpleClassName(Ast\Selector\Simple\ClassName $class) {
      $this->appendElement('class', '.'.$class->className);
      return TRUE;
    }

    public function visitEnterSelectorCombinatorDescendant() {
      return $this->start($this->appendElement('descendant', ' '));
    }

    public function visitLeaveSelectorCombinatorDescendant() {
      return $this->end();
    }

    public function visitEnterSelectorCombinatorChild() {
      return $this->start($this->appendElement('child', ' > '));
    }

    public function visitLeaveSelectorCombinatorChild() {
      return $this->end();
    }

    public function visitEnterSelectorCombinatorFollower() {
      return $this->start($this->appendElement('follower', ' ~ '));
    }

    public function visitLeaveSelectorCombinatorFollower() {
      return $this->end();
    }

    public function visitEnterSelectorCombinatorNext() {
      return $this->start($this->appendElement('child', ' + '));
    }

    public function visitLeaveSelectorCombinatorNext() {
      return $this->end();
    }

    public function visitSelectorSimpleAttribute(
      Ast\Selector\Simple\Attribute $attribute
    ) {
      $operators = array(
        Ast\Selector\Simple\Attribute::MATCH_EXISTS => 'exists',
        Ast\Selector\Simple\Attribute::MATCH_PREFIX => 'prefix',
        Ast\Selector\Simple\Attribute::MATCH_SUFFIX => 'suffix',
        Ast\Selector\Simple\Attribute::MATCH_SUBSTRING => 'substring',
        Ast\Selector\Simple\Attribute::MATCH_EQUALS => 'equals',
        Ast\Selector\Simple\Attribute::MATCH_INCLUDES => 'includes',
        Ast\Selector\Simple\Attribute::MATCH_DASHMATCH => 'dashmatch'
      );
      $this->start(
        $this->appendElement(
          'attribute', '', array('operator' => $operators[$attribute->match])
        )
      );
      $this->appendText('[');
      $this->appendElement('name', $attribute->name);
      if ($attribute->match !== Ast\Selector\Simple\Attribute::MATCH_EXISTS) {
        $operatorStrings = array(
          Ast\Selector\Simple\Attribute::MATCH_PREFIX => '^=',
          Ast\Selector\Simple\Attribute::MATCH_SUFFIX => '$=',
          Ast\Selector\Simple\Attribute::MATCH_SUBSTRING => '*=',
          Ast\Selector\Simple\Attribute::MATCH_EQUALS => '=',
          Ast\Selector\Simple\Attribute::MATCH_INCLUDES => '~=',
          Ast\Selector\Simple\Attribute::MATCH_DASHMATCH => '|='
        );
        $this->appendElement('operator', $operatorStrings[$attribute->match]);
        $this->appendText('"');
        $this->appendElement(
          'value',
          str_replace(array('\\', '"'), array('\\\\', '\\"'), $attribute->literal)
        );
        $this->appendText('"');
      }
      $this->appendText(']');
      $this->end();
      return TRUE;
    }
  }
}