<?php

/**
 * VUT FIT - IPP
 * @author Jakub Lůčný (xlucnyj00)
 * @date 2025-04-14
 * @project IPP project 2 - interpreter for SOL25 language
 * @brief MessageSender class definition
 */

namespace IPP\Student;

use IPP\Student\Exception\UnexpectedXMLFormatException;
use IPP\Student\Exception\UsingUndefinedException;
use IPP\Student\Exception\IncorrectArgumentException;
use IPP\Student\Exception\MessageDNUException;

/**
 * MessageSender class for sending messages, either instance methods, class methods or
 * setting/getting attributes
 */
class MessageSender
{
    // In scope of which class instance we are
    private ClassInstance $self_reference;

    /**
     * Constructor taking current self instance as argument
     */
    public function __construct(ClassInstance $self)
    {
        $this->self_reference = $self;
    }

    // ****************** INVOKING CLASS INSTANCE CONSTRUCTOR *******************
    /**
     * Creates new class instance based on received arguments
     *
     * @param string $receiver Class name of the instance to be created
     * @param string $selector Name of the class method to be called
     * @param array<ClassInstance> $args Argument used to create the instance from (if needed)
     */
    public function invokeClassMethod(string $receiver, string $selector, array $args): ClassInstance
    {
        // Based on the selector (type of class method)...
        switch ($selector) {
            case "new":
                // Just creates a new pure class instance
                $new_class = new ClassInstance($receiver);

                // Set default value
                if (ClassDefinition::isInstanceOf($receiver, "Integer")) {
                    $new_class->setValue(0);
                } elseif (ClassDefinition::isInstanceOf($receiver, "String")) {
                    $new_class->setValue("");
                } elseif (ClassDefinition::isInstanceOf($receiver, "True")) {
                    $new_class->setValue(true);
                } elseif (ClassDefinition::isInstanceOf($receiver, "False")) {
                    $new_class->setValue(false);
                }
                // Rest of the classes don't use internal value so we leave it 'null'
                return $new_class;

            case "from:":
                $new_class = new ClassInstance($receiver);
                if (isset($args[0])) {
                    $arg = $args[0];
                    // Check if both the classes are compatible
                    // If they are the same class type or one is instance of the other
                    if ($arg->isInstanceOf($receiver) || $new_class->isInstanceOf($arg->getClassName())) {
                        $arg->copyValues($new_class);
                    } else {
                        throw new IncorrectArgumentException("Invalid class type for 'from:' " . $receiver);
                    }
                } else {
                    // This should never happen if parser works correctly
                    throw new UnexpectedXMLFormatException("Missing argument for 'from:' ");
                }
                return $new_class;

            case "read":
                // Check if given class is instance of built-in String class, others don't understand 'read' message
                if (ClassDefinition::isInstanceOf($receiver, "String") === false) {
                    throw new UsingUndefinedException("This class doesn't understand 'read' message: " . $receiver);
                }

                // Create a new instance and initialize it with value from input
                $string = new ClassInstance($receiver);
                // Use the input reader
                $inputReader = Interpreter::getInputReader();
                $value = $inputReader ? $inputReader->readString() : "";
                $string->setValue($value);
                return $string;
            default:
                throw new UsingUndefinedException("Do not understand this class method: " . $selector);
        }   // case
    }

    // ******************** INVOKING CLASS INSTANCE METHODS ********************
    /**
     * Calls given instance method with given arguments
     *
     * @param ClassInstance $receiver Instance to be called
     * @param string $selector Name of the method to be called
     * @param array<ClassInstance> $args Arguments to be send with message
     */
    public function invokeInstanceMethod(ClassInstance $receiver, string $selector, array $args): ClassInstance
    {
        // Get the method from the class definition
        $method = ClassDefinition::getMethod($receiver->getClassName(), $selector);

        // ^^^ finds correct method but the receiver should be self instance (if __SUPER__ is used)
        if ($receiver->getValue() === "__SUPER__") {
            $receiver = $this->getSelf();
        }

        if ($method === null) {
        // Didn't find corresponding method in the class
            $num_of_args = count($args);

            // 0 arguments == unknown message means getting value of attribute or error if no such attribute exists
            if ($num_of_args === 0) {
                $result = $receiver->getAttribute($selector);
                if ($result === null) {
                    throw new MessageDNUException("Unknown attribute/method: " . $selector);
                }
                return $result;
            } elseif ($num_of_args === 1) {
                // Creating/updating value of an attribute
                $attrib_name = substr($selector, 0, -1);    // get rid of the trailing ':'
                $receiver->setAttribute($attrib_name, $args[0]);

                return $receiver;
            }

            throw new MessageDNUException("Unknown attribute: " . $selector);
        } elseif ($method instanceof \DOMElement) {
        // Method found (user-defined method)
            // Create new scope based on the receiver
            $new_scope = new BlockScope();
            $new_scope->setVariable("self", $receiver);

            // Process the method with given arguments
            $block_node = $method->getElementsByTagName("block")->item(0);
            $new_scope->processBlock($block_node, $args);

            return $new_scope->getReturnValue();
        } else {
        // Built-in method (== is_callable())
            $result = null;
            // Those methods need new scope -> block instance with self variable set
            switch ($selector) {
                case "value":
                case "value:":
                case "value:value:":
                case "value:value:value:":
                case "timesRepeat:":
                case "whileTrue:":
                case "ifTrue:ifFalse:":
                case "and:":
                case "or:":
                    $new_scope = new BlockScope();
                    $real_self = $this->getSelf();
                    $new_scope->setVariable("self", $real_self);

                    // Call the built-in method with new scope
                    $result = $method($new_scope, $receiver, ...$args);
                    break;
                // Other built-in methods don't need new scope
                default:
                    $result = $method($receiver, ...$args);
                    break;
            }
            return $result;
        }
    }

    /**
     * Returns the current self reference
     */
    private function getSelf(): ClassInstance
    {
        return $this->self_reference;
    }
}
