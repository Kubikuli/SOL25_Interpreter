<?php

namespace IPP\Student;

use IPP\Student\Exception\InterpretException;
use IPP\Student\Exception\UnexpectedXMLFormatException;


class ExpressionEvaluator
{
    private BlockScope $scope;

    // Constructor
    public function __construct(BlockScope $scope)
    {
        $this->scope = $scope;
    }

    private function getVariable(string $name): ClassInstance
    {
        return $this->scope->getVariable($name);
    }

    // *************************** EXPR PROCESSING ****************************
    public function evaluateExpr(\DOMElement $expr_node): ClassInstance|string
    {
        // Skip expr node
        $expression = $expr_node;

        // Find first child node thats not text = always the node starting expression
        foreach ($expr_node->childNodes as $child_node) {
            if ($child_node instanceof \DOMElement) {
                $expression = $child_node;
                break;
            }
        }

        $elem_type = $expression->tagName;

        switch ($elem_type) {
            case "literal":
                return $this->evaluateLiteral($expression);
            case "var":
                return $this->evaluateVariable($expression);
            case "send":
                return $this->evaluateMessageSend($expression);
            case "block":
                $block = new ClassInstance("Block");
                $block->setValue($expression);
                return $block;
            default:
                throw new InterpretException("Unknown expression type: " . $elem_type);
        }
    }

    // *************************** LITERAL PROCESSING ****************************
    private function evaluateLiteral(\DOMElement $literal_node): ClassInstance|string
    {
        $value = $literal_node->getAttribute("value");
        $type = $literal_node->getAttribute("class");

        switch ($type) {
            case "Integer":
                $integer = new ClassInstance("Integer");
                $integer->setValue((int)$value);
                return $integer;
            case "String":
                $string = new ClassInstance("String");
                $string->setValue($value);
                return $string;
            case "True":
                $tru = new ClassInstance("True");
                $tru->setValue(true);
                return $tru;
            case "False":
                $false = new ClassInstance("False");
                $false->setValue(false);
                return $false;
            case "Nil":
                $nil = new ClassInstance("Nil");
                $nil->setValue(null);
                return $nil;
            case "class":
                return $value;  // name of the class whose instance is to be created
            default:
                throw new InterpretException("Unknown literal type: " . $type);
        }
    }

    // *************************** VAR PROCESSING ****************************
    private function evaluateVariable(\DOMElement $var_node): ClassInstance
    {
        $var_name = $var_node->getAttribute("name");

        $value = null;

        if ($var_name === "super") {
            // Those next 3 commands get name of parent of current class
            $self_instance = $this->getVariable("self");    // instance of self (= current class)
            $self_type = $self_instance->getClassName();    // type of self instance
            $parent_class = ClassDefinition::getClass($self_type)->getParentName(); // name of self's parent class

            $value = new ClassInstance($parent_class);
            $value->setValue("__SUPER__");
        }
        else {
            $value = $this->getVariable($var_name);
        }

        return $value;
    }

    // *************************** SEND PROCESSING *****************************
    private function evaluateMessageSend(\DOMElement $send_node): ClassInstance
    {
        // Get arguments for message send
        $args = $this->getArgs($send_node);

        // Message selector
        $selector = $send_node->getAttribute("selector");

        // Get expr node to evaluate the receiver
        $expr_node = null;
        foreach ($send_node->childNodes as $child_node) {
            if ($child_node instanceof \DOMElement) {
                if ($child_node->tagName === "expr") {
                    $expr_node = $child_node;
                    break;
                }
            }
        }

        if ($expr_node === null) {
            throw new UnexpectedXMLFormatException("Malformed XML. Missing expr node.");
        }

        $receiver = $this->evaluateExpr($expr_node);

        $sender = new MessageSender($this->getVariable("self"));

        // $receiver is string only when sending a class method meaning creating a new class instance
        if (is_string($receiver)) {
            $result = $sender->invokeClassMethod($receiver, $selector, $args);
            return $result;
        }
        // Normal method call
        else {
            $result = $sender->invokeInstanceMethod($receiver, $selector, $args);
            return $result;
        }
    }

    // **************************** ARG PROCESSING *****************************
    /**
     * @return array<ClassInstance> List of all arguments found
     */
    private function getArgs(\DOMElement $send_node): array
    {
        $args = [];
        foreach ($send_node->childNodes as $arg_node) {
            if ($arg_node instanceof \DOMElement) {
                if ($arg_node->tagName === "arg") {
                    // Get the argument value
                    $order = (int)$arg_node->getAttribute("order");
                    $expr_node = $arg_node->getElementsByTagName("expr")->item(0);

                    if ($expr_node === null) {
                        throw new UnexpectedXMLFormatException("Malformed XML. Missing expr node.");
                    }

                    $arg = $this->evaluateExpr($expr_node);
                    if (is_string($arg)) {
                        throw new InterpretException("Invalid argument type: " . $arg);
                    }

                    $args[$order-1] = $arg;
                }
            }
        }
        return $args;
    }
}
