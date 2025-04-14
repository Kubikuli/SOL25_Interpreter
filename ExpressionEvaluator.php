<?php

/**
 * VUT FIT - IPP
 * @author Jakub Lůčný (xlucnyj00)
 * @date 2025-04-14
 * @project IPP project 2 - interpreter for SOL25 language
 * @brief ExpressionEvaluator class definition
 */

namespace IPP\Student;

use IPP\Student\Exception\InterpretException;
use IPP\Student\Exception\UnexpectedXMLFormatException;

/**
 * ExpressionEvaluator class for evaluating all types of expressions
 */
class ExpressionEvaluator
{
    // Block scope in which the expression is evaluated, with possible necesarry variables
    private BlockScope $scope;

    /**
     * Constructor taking scope as argument
     */
    public function __construct(BlockScope $scope)
    {
        $this->scope = $scope;
    }

    // *************************** EXPR PROCESSING ****************************
    /**
     * Evaluates expression starting with the given expression node and returns the result value 
     * 
     * @param \DOMElement $expr_node Expr node to be processed
     * @return ClassInstance|string Result of the expression (string only in case of class instance creation)
     */
    public function evaluateExpr(\DOMElement $expr_node): ClassInstance|string
    {
        // Skip expr node
        $expression = $expr_node;
        // Find first child node that's not text (== always node where the expression starts)
        foreach ($expr_node->childNodes as $child_node) {
            if ($child_node instanceof \DOMElement) {
                $expression = $child_node;
                break;
            }
        }

        // Get the type of the expression
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

    // ************************* LITERAL PROCESSING **************************
    /**
     * Evaluates literal value starting with the given node and returns the result value
     * 
     * @param \DOMElement $literal_node Literal node to be processed
     * @return ClassInstance|string Value of the literal (string only in case of class literal)
     */
    private function evaluateLiteral(\DOMElement $literal_node): ClassInstance|string
    {
        // Get literal value and type
        $value = $literal_node->getAttribute("value");
        $type = $literal_node->getAttribute("class");

        // Based on the type, create a new instance of the corresponding class
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
    /**
     * Evaluates given variable node and returns the variable value
     *
     * @param \DOMElement $var_node Variable node to be processed
     * @return ClassInstance Value of the variable
     */
    private function evaluateVariable(\DOMElement $var_node): ClassInstance
    {
        $var_name = $var_node->getAttribute("name");

        $value = null;

        // Special case for 'super' pseudo-variable
        if ($var_name === "super") {
            // Those next 3 commands get name of parent of current class
            $self_instance = $this->getVariable("self");    // instance of self (= current class)
            $self_type = $self_instance->getClassName();    // type of self instance
            $parent_class = ClassDefinition::getClass($self_type)->getParentName(); // name of self's parent class

            // Returns instance of the parent of current class
            $value = new ClassInstance($parent_class);
            $value->setValue("__SUPER__");
        }
        else {
            $value = $this->getVariable($var_name);
        }

        return $value;
    }

    // *************************** SEND PROCESSING *****************************
    /**
     * Evaluates given send node, invokes class or instance method and returns the result of the call
     *
     * @param \DOMElement $send_node Send node to be processed
     * @return ClassInstance Result of the method call
     */
    private function evaluateMessageSend(\DOMElement $send_node): ClassInstance
    {
        // Get arguments for message send
        $args = $this->getArgs($send_node);

        // Get message selector
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

        // Evaluate the receiver expression
        $receiver = $this->evaluateExpr($expr_node);

        // Prepare for sending the message
        $sender = new MessageSender($this->getVariable("self"));

        // $receiver is string only when sending a class method meaning creating a new class instance
        if (is_string($receiver)) {
            $result = $sender->invokeClassMethod($receiver, $selector, $args);
            return $result;
        }
        // "Normal" (instance) method call
        else {
            $result = $sender->invokeInstanceMethod($receiver, $selector, $args);
            return $result;
        }
    }

    // **************************** ARG PROCESSING *****************************
    /**
     * Processes given send node and returns array of all arguments to be send with the message
     * 
     * @param \DOMElement $send_node Send node to be processed
     * @return array<ClassInstance> List of all arguments found
     */
    private function getArgs(\DOMElement $send_node): array
    {
        $args = [];
        // Check all the childs for arguments
        foreach ($send_node->childNodes as $arg_node) {
            if ($arg_node instanceof \DOMElement) {
                if ($arg_node->tagName === "arg") {
                    // Get the argument value
                    $order = (int)$arg_node->getAttribute("order");
                    $expr_node = $arg_node->getElementsByTagName("expr")->item(0);

                    if ($expr_node === null) {
                        throw new UnexpectedXMLFormatException("Malformed XML. Missing expr node.");
                    }

                    // Evaluate the argument expression
                    $arg = $this->evaluateExpr($expr_node);
                    if (is_string($arg)) {
                        throw new InterpretException("Invalid argument type: " . $arg);
                    }

                    // Save them in the correct order
                    $args[$order-1] = $arg;
                }
            }
        }
        return $args;
    }
    
    /**
     * Returns value of given variable from this scope
     *
     * @param string $name Variable name
     * @return ClassInstance
     */
    private function getVariable(string $name): ClassInstance
    {
        return $this->scope->getVariable($name);
    }
}
