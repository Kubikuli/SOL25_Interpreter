<?php

namespace IPP\Student;

use IPP\Student\Exception\IncorrectArgumentException;
use IPP\Student\Exception\InterpretException;
use IPP\Student\Exception\MessageDNUException;
use IPP\Student\Exception\UnexpectedXMLFormatException;
use IPP\Student\Exception\UsingUndefinedException;

class Method_block
{
    protected array $variables = [];    // list of variables
    protected mixed $return_value = null;    // return value of the method

    public function set_return_value(Class_instance $value): void
    {
        $this->return_value = $value;
    }

    public function get_return_value(): mixed
    {
        return $this->return_value;
    }

    public function set_variable(string $name, Class_instance $value): void
    {
        $this->variables[$name] = $value;
    }

    public function get_variable(string $name): ?Class_instance
    {
        return $this->variables[$name] ?? null;
    }

    // ********************** BLOCK PROCESSING ***********************
    public function process_block(\DOMElement $block_node, array $args): void
    {
        // Assign argument values to parameters
        if ($block_node instanceof \DOMElement) {
            // For each child node
            foreach ($block_node->childNodes as $child_node) {
                if ($child_node instanceof \DOMElement) {
                    // Check if it's a parameter
                    if ($child_node->tagName === "parameter") {
                        // Assign it to the block attribute as variable with correct value
                        $order = (int)$child_node->getAttribute("order");
                        $par_name = $child_node->getAttribute("name");

                        $this->set_variable($par_name, $args[$order-1]);
                    }
                }
            }
        }

        $this->process_assignments($block_node);
    }

    // ************************* ASSIGN PROCESSING **************************
    private function process_assignments(\DOMElement $block_node): void
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
                                    }
                                    // Get value to be assigned
                                    else if($grand_child_node->tagName === "expr"){
                                        $result = $this->evaluate_expr($grand_child_node);
                                    }
                                }
                            }

                            if (is_string($result) || $result === null){
                                // This should never reach, only with malformed XML
                                throw new InterpretException("Malformed XML. If you got this error msg, something is REALLY wrong, sorry");
                            }

                            // Assign value to the variable
                            $this->set_variable($var_name, $result);
                            $this->set_return_value($result);
                        }
                    }
                }
            }
        }
    }

    // *************************** EXPR PROCESSING ****************************
    private function evaluate_expr(\DOMElement $expr_node): Class_instance|string
    {
        // Skip expr node
        $expression = $expr_node;

        // Find first child node thats not text
        foreach ($expr_node->childNodes as $child_node) {
            if ($child_node instanceof \DOMElement) {
                $expression = $child_node;
                break;
            }
        }

        if ($expression instanceof \DOMElement) {
            $elem_type = $expression->tagName;

            switch ($elem_type) {
                case "literal":
                    return $this->evaluateLiteral($expression);
                case "var":
                    return $this->evaluateVariable($expression);
                case "send":
                    return $this->evaluateMessageSend($expression);
                case "block":
                    $block = new Class_instance("Block");
                    $block->set_value($expression);
                    return $block;
                default:
                    throw new InterpretException("Unknown expression type: " . $elem_type);
            }
        }
        else {
            throw new UnexpectedXMLFormatException();
        }
    }

    // *************************** LITERAL PROCESSING ****************************
    private function evaluateLiteral(\DOMElement $literal_node): Class_instance|string
    {
        $value = $literal_node->getAttribute("value");
        $type = $literal_node->getAttribute("class");

        switch ($type) {
            case "Integer":
                $integer = new Class_instance("Integer");
                $integer->set_value((int)$value);
                return $integer;
            case "String":
                $string = new Class_instance("String");
                $string->set_value($value);
                return $string;
            case "True":
                $tru = new Class_instance("True");
                $tru->set_value(true);
                return $tru;
            case "False":
                $false = new Class_instance("False");
                $false->set_value(false);
                return $false;
            case "Nil":
                $nil = new Class_instance("Nil");
                $nil->set_value(null);
                return $nil;
            case "class":
                return $value;  // name of the class that's instance is to be created
            default:
                throw new InterpretException("Unknown literal type: " . $type);
        }
    }

    // *************************** VAR PROCESSING ****************************
    private function evaluateVariable(\DOMElement $var_node): Class_instance
    {
        $var_name = $var_node->getAttribute("name");

        $value = null;

        if ($var_name === "super") {
            // Those next 3 commands get name of parent of current class
            $self_instance = $this->get_variable("self");    // instance of self (= current class)
            $self_type = $self_instance->get_class_name();   // type of self instance
            $parent_class = Class_definition::get_class($self_type)->get_parent_name(); // name of self's parent class

            $value = new Class_instance($parent_class);
            $value->set_value("__SUPER__");
        }
        else{
            $value = $this->get_variable($var_name);
        }

        if ($value === null) {
            throw new UsingUndefinedException("Using undefined variable: " . $var_name);
        }

        return $value;
    }

    // *************************** SEND PROCESSING *****************************
    private function evaluateMessageSend(\DOMElement $send_node): Class_instance
    {
        // Get arguments for message send
        $args = $this->get_args($send_node);

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
                
        $receiver = $this->evaluate_expr($expr_node);

        // $receiver is string only when sending a class method meaning creating a new class instance
        if (is_string($receiver)){
            $result = $this->invoke_class_method($receiver, $selector, $args);
            return $result;
        }
        // Normal method call
        else{
            $result = $this->invoke_instance_method($receiver, $selector, $args);
            return $result;
        }
    }

    // **************************** ARG PROCESSING *****************************
    private function get_args(\DOMElement $send_node): array
    {
        $args = [];
        foreach ($send_node->childNodes as $arg_node) {
            if ($arg_node instanceof \DOMElement) {
                if ($arg_node->tagName === "arg") {
                    // Get the argument value
                    $order = (int)$arg_node->getAttribute("order");
                    $expr_node = $arg_node->getElementsByTagName("expr")->item(0);
                    $args[$order-1] = $this->evaluate_expr($expr_node);
                }
            }
        }
        return $args;
    }

    // ************************* INVOKING CLASS INSTANCE CONSTRUCTOR *************************
    private function invoke_class_method(string $receiver, string $selector, array $args): Class_instance
    {
        switch ($selector) {
            case "new":
                // Just creates a new pure class instance
                $new_class = new Class_instance($receiver);

                // Set default value
                if (Class_definition::is_instance_of($receiver, "Integer")) {
                    $new_class->set_value(0);
                }
                else if (Class_definition::is_instance_of($receiver, "String")) {
                    $new_class->set_value("");
                }
                else if (Class_definition::is_instance_of($receiver, "True")) {
                    $new_class->set_value(true);
                }
                else if (Class_definition::is_instance_of($receiver, "False")) {
                    $new_class->set_value(false);
                }
                else if (Class_definition::get_class($receiver) === null) {
                    throw new UsingUndefinedException("Trying to create instance of undefined class: " . $receiver);
                }
                // Rest of the classes dont use internal value so we are not setting anyhting
                return $new_class;

            case "from:":
                $new_class = new Class_instance($receiver);
                if (isset($args[0])) {
                    $arg = $args[0];
                    if ($arg instanceof Class_instance) {
                        // If they are the same class type or one is instance of the other
                        if ($arg->is_instance_of($receiver) || $new_class->is_instance_of($arg->get_class_name())) {
                            $arg->copy_values($new_class);
                        }
                        else {
                            throw new IncorrectArgumentException("Invalid class type for 'from:' " . $receiver);
                        }
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
                if (Class_definition::is_instance_of($receiver, "String") === false) {
                    throw new UsingUndefinedException("This class doesn't understand 'read' message: " . $receiver);
                }

                // Create a new instance and initialize it with value from input
                $string = new Class_instance($receiver);
                // Use the input reader
                $inputReader = Interpreter::get_input_reader();
                $value = $inputReader ? $inputReader->readString() : "";
                $string->set_value($value);
                return $string;
            default:
                throw new UsingUndefinedException("Do not understand this class method: " . $selector);
        }
    }

    // ************************** INVOKING CLASS INSTANCE METHODS **************************
    public function invoke_instance_method(Class_instance $receiver, string $selector, array $args): Class_instance
    {
        // Get the method from the class definition
        $method = Class_definition::get_method($receiver->get_class_name(), $selector);

        // ^^^ finds correct method but the receiver should be self instance (if __SUPER__ is used)
        if ($receiver->get_value() === "__SUPER__"){
            $receiver = $this->get_variable("self");
        }

        // Didn't find coresponding method in the class
        if ($method === null) {
            $num_of_args = count($args);

            // 0 arguments unknown message means getting value of attribute or error if no such attribute exists
            if ($num_of_args === 0){
                $result = $receiver->get_attribute($selector);
                if ($result === null) {
                    throw new MessageDNUException("Unknown attribute/method: " . $selector);
                }
                return $result;
            }
            // Creating/updating value of an attribute
            else if ($num_of_args === 1){

                $attrib_name = substr($selector, 0, -1);    // get rid of the trailing ':'
                $receiver->set_attribute($attrib_name, $args[0]);

                return $receiver;
            }
            
            throw new MessageDNUException("Unknown attribute: " . $selector);
        }

        // Method found
        // User defined method
        else if ($method instanceof \DOMElement) {
            $block = new Method_block();
            $block->set_variable("self", $receiver);

            // Process the block with the arguments
            $block_node = $method->getElementsByTagName("block")->item(0);

            $block->process_block($block_node, $args);

            $return_val = $block->get_return_value();

            if ($return_val === null) {
                $return_val = new Class_instance("Nil");
                $return_val->set_value(null);
            }

            return $return_val;
        }
        // Built-in method
        else if (is_callable($method)) {
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
                    $block = new Method_block();
                    $real_self = $this->get_variable("self");
                    $block->set_variable("self", $real_self);

                    $result = $method($block, $receiver, ...$args);
                    break;

                default:
                    $result = $method($receiver, ...$args);
                    break;
            }
            return $result;
        }
        else{
            // This never reaches, but just in case
            throw new InterpretException("Unknown method type. If you got this error msg, something is REALLY wrong");
        }
    }
}   // class Method_block
