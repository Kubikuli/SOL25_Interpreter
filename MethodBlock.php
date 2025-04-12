<?php

namespace IPP\Student;

use IPP\Student\Exception\IncorrectArgumentException;
use IPP\Student\Exception\InterpretException;
use IPP\Student\Exception\MessageDNUException;
use IPP\Student\Exception\UnexpectedXMLFormatException;

class MethodBlock
{
    /**
     * @var array<string, ClassInstance> List of variables of current block
     */
    protected array $variables = [];    
    protected ClassInstance $return_value;    // return value of the method

    // Constructor
    public function __construct()
    {
        $this->return_value = new ClassInstance("Nil");
    }

    public function setReturnValue(ClassInstance $value): void
    {
        $this->return_value = $value;
    }

    public function getReturnValue(): ClassInstance
    {
        return $this->return_value;
    }

    public function setVariable(string $name, ClassInstance $value): void
    {
        $this->variables[$name] = $value;
    }

    public function getVariable(string $name): ClassInstance
    {
        if (!isset($this->variables[$name])) {
            throw new InterpretException("Using undefined variable: " . $name);
        }
        return $this->variables[$name];
        }

    // ********************** BLOCK PROCESSING ***********************
    /**
     * @param array<ClassInstance> $args Arguments given to the block
     */
    public function processBlock(?\DOMElement $block_node, array $args): void
    {
        if ($block_node === null){
            throw new UnexpectedXMLFormatException("Malformed XML format.");
        }

        // Assign argument values to parameters
        foreach ($block_node->childNodes as $child_node) {
            if ($child_node instanceof \DOMElement) {
                // Check if it's a parameter
                if ($child_node->tagName === "parameter") {
                    // Assign it to the block attribute as variable with correct value
                    $order = (int)$child_node->getAttribute("order");
                    $par_name = $child_node->getAttribute("name");

                    $this->setVariable($par_name, $args[$order-1]);
                }
            }
        }

        $this->processAssignments($block_node);
    }

    // ************************* ASSIGN PROCESSING **************************
    private function processAssignments(\DOMElement $block_node): void
    {
        $order = 0;
        $new_order = 1;
        // Makes sure the assignments are processed in the correct order
        while($order !== $new_order){
            $order = $new_order;
            // For each child node
            foreach ($block_node->childNodes as $child_node) {
                if ($child_node instanceof \DOMElement) {
                    // Check if it's an assignment
                    if ($child_node->tagName === "assign"){
                        // Make sure the order is correct
                        $assign_order = (int)$child_node->getAttribute("order");
                        if ($assign_order === $order) {
                            $new_order++;

                            // ******** ASSIGNMENT PROCESSING ********
                            $result = null;
                            $var_name = null;

                            foreach ($child_node->childNodes as $grand_child_node) {
                                if ($grand_child_node instanceof \DOMElement) {
                                    
                                    // Get variable name
                                    if ($grand_child_node->tagName === "var"){
                                        $var_name = $grand_child_node->getAttribute("name");

                                        if ($var_name === ""){
                                            throw new UnexpectedXMLFormatException("Malformed XML. Missing name attribute.");
                                        }
                                    }
                                    // Get value to be assigned
                                    else if($grand_child_node->tagName === "expr"){
                                        $result = $this->evaluateExpr($grand_child_node);
                                    }
                                }
                            }

                            if (is_string($result) || $result === null || $var_name === null){
                                // This should never reach, only with malformed XML
                                throw new InterpretException("Malformed XML. If you got this error msg, something is REALLY wrong, sorry.");
                            }

                            // Assign value to the variable
                            $this->setVariable($var_name, $result);
                            $this->setReturnValue($result);
                        }
                    }
                }
            }
        }
    }

    // *************************** EXPR PROCESSING ****************************
    private function evaluateExpr(\DOMElement $expr_node): ClassInstance|string
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
                return $value;  // name of the class that's instance is to be created
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
            $self_type = $self_instance->getClassName();   // type of self instance
            $parent_class = ClassDefinition::getClass($self_type)->getParentName(); // name of self's parent class

            $value = new ClassInstance($parent_class);
            $value->setValue("__SUPER__");
        }
        else{
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

        // $receiver is string only when sending a class method meaning creating a new class instance
        if (is_string($receiver)){
            $result = $this->invokeClassMethod($receiver, $selector, $args);
            return $result;
        }
        // Normal method call
        else{
            $result = $this->invokeInstanceMethod($receiver, $selector, $args);
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

    // ************************* INVOKING CLASS INSTANCE CONSTRUCTOR *************************
    /**
     * @param array<ClassInstance> $args Arguments given to the block
     */
    private function invokeClassMethod(string $receiver, string $selector, array $args): ClassInstance
    {
        switch ($selector) {
            case "new":
                // Just creates a new pure class instance
                $new_class = new ClassInstance($receiver);

                // Set default value
                if (ClassDefinition::isInstanceOf($receiver, "Integer")) {
                    $new_class->setValue(0);
                }
                else if (ClassDefinition::isInstanceOf($receiver, "String")) {
                    $new_class->setValue("");
                }
                else if (ClassDefinition::isInstanceOf($receiver, "True")) {
                    $new_class->setValue(true);
                }
                else if (ClassDefinition::isInstanceOf($receiver, "False")) {
                    $new_class->setValue(false);
                }
                // Rest of the classes dont use internal value so we are not setting anything
                return $new_class;

            case "from:":
                $new_class = new ClassInstance($receiver);
                if (isset($args[0])) {
                    $arg = $args[0];
                    // If they are the same class type or one is instance of the other
                    if ($arg->isInstanceOf($receiver) || $new_class->isInstanceOf($arg->getClassName())) {
                        $arg->copyValues($new_class);
                    }
                    else {
                        throw new IncorrectArgumentException("Invalid class type for 'from:' " . $receiver);
                    }
                }
                else{
                    // This should never happen
                    throw new IncorrectArgumentException("Missing argument for 'from:' ");
                }
                return $new_class;

            case "read":
                // Check if given class is instance of built-in String class 
                if (ClassDefinition::isInstanceOf($receiver, "String") === false) {
                    throw new MessageDNUException("This class doesn't understand 'read' message: " . $receiver);
                }

                // Create a new instance and initialize it with value from input
                $string = new ClassInstance($receiver);
                // Use the input reader
                $inputReader = Interpreter::getInputReader();
                $value = $inputReader ? $inputReader->readString() : "";
                $string->setValue($value);
                return $string;
            default:
                throw new MessageDNUException("Do not understand this class method: " . $selector);
        }
    }

    // ************************** INVOKING CLASS INSTANCE METHODS **************************
    /**
     * @param array<ClassInstance> $args Arguments to be send with message
     */
    public function invokeInstanceMethod(ClassInstance $receiver, string $selector, array $args): ClassInstance
    {
        // Get the method from the class definition
        $method = ClassDefinition::getMethod($receiver->getClassName(), $selector);

        // ^^^ finds correct method but the receiver should be self instance (if __SUPER__ is used)
        if ($receiver->getValue() === "__SUPER__"){
            $receiver = $this->getVariable("self");
        }

        // Didn't find coresponding method in the class
        if ($method === null) {
            $num_of_args = count($args);

            // 0 arguments unknown message means getting value of attribute or error if no such attribute exists
            if ($num_of_args === 0){
                $result = $receiver->getAttribute($selector);
                if ($result === null) {
                    throw new MessageDNUException("Unknown attribute/method: " . $selector);
                }
                return $result;
            }
            // Creating/updating value of an attribute
            else if ($num_of_args === 1){

                $attrib_name = substr($selector, 0, -1);    // get rid of the trailing ':'
                $receiver->setAttribute($attrib_name, $args[0]);

                return $receiver;
            }
            
            throw new MessageDNUException("Unknown attribute: " . $selector);
        }

        // Method found
        // User defined method
        else if ($method instanceof \DOMElement) {
            $block = new MethodBlock();
            $block->setVariable("self", $receiver);

            // Process the block with the arguments
            $block_node = $method->getElementsByTagName("block")->item(0);

            $block->processBlock($block_node, $args);

            return $block->getReturnValue();
        }
        // Built-in method
        // == is_callable() 
        else {
            $result = null;
            switch($selector) {
                case "value":
                case "value:":
                case "value:value:":
                case "value:value:value:":
                case "timesRepeat:":
                case "whileTrue:":
                case "ifTrue:ifFalse:":
                case "and:":
                case "or:":
                    // Those methods need new block instance with self variable set
                    $block = new MethodBlock();
                    $real_self = $this->getVariable("self");
                    $block->setVariable("self", $real_self);

                    $result = $method($block, $receiver, ...$args);
                    break;

                default:
                    $result = $method($receiver, ...$args);
                    break;
            }
            return $result;
        }
    }
}   // class MethodBlock
