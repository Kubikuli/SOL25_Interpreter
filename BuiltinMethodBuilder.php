<?php

/**
 * VUT FIT - IPP
 * @author Jakub Lůčný (xlucnyj00)
 * @date 2025-04-14
 * @project IPP project 2 - interpreter for SOL25 language
 * @brief BuiltinMethodBuilder class definition
 */

namespace IPP\Student;

use IPP\Student\Exception\IncorrectArgumentException;
use IPP\Student\Exception\MessageDNUException;

/**
 * Class building definitions for all the built-in methods
 */
class BuiltinMethodBuilder
{
    /**
     * Helper function, returns new instance of True class
     */
    private function trueInstance(): ClassInstance
    {
        $true = new ClassInstance("True");
        $true->setValue(true);
        return $true;
    }

    /**
     * Helper function, returns new instance of False class
     */
    private function falseInstance(): ClassInstance
    {
        $false = new ClassInstance("False");
        $false->setValue(false);
        return $false;
    }

    /**
     * Builds built-in instance methods for Object class
     *
     * @param ClassDefinition $class Class definition instance of the class to build methods for
     */
    public function buildObjectMethods(ClassDefinition $class): void
    {
        $class->addBuiltinMethod(
            "identicalTo:",
            function (ClassInstance $receiver, ClassInstance $object): ClassInstance {
                // Exact same class instance
                if ($receiver === $object) {
                    return $this->trueInstance();
                }

                return $this->falseInstance();
            }
        );

        $class->addBuiltinMethod("equalTo:", function (ClassInstance $receiver, ClassInstance $object): ClassInstance {
            // Same intern value
            if ($receiver->getValue() !== null && $object->getValue() !== null) {
                $equal = $receiver->getValue() === $object->getValue();
                if ($equal) {
                    return $this->trueInstance();
                }
            }

            // identicalTo: part
            if ($receiver === $object) {
                return $this->trueInstance();
            }

            return $this->falseInstance();
        });

        $class->addBuiltinMethod("asString", function (ClassInstance $receiver): ClassInstance {
            $string = new ClassInstance("String");
            $string->setValue("");
            return $string;
        });

        $class->addBuiltinMethod("isNumber", function (ClassInstance $receiver): ClassInstance {
            return $this->falseInstance();
        });

        $class->addBuiltinMethod("isString", function (ClassInstance $receiver): ClassInstance {
            return $this->falseInstance();
        });

        $class->addBuiltinMethod("isBlock", function (ClassInstance $receiver): ClassInstance {
            return $this->falseInstance();
        });

        $class->addBuiltinMethod("isNil", function (ClassInstance $receiver): ClassInstance {
            return $this->falseInstance();
        });
    }

    /**
     * Builds built-in instance methods for Nil class
     *
     * @param ClassDefinition $class Class definition instance of the class to build methods for
     */
    public function buildNilMethods(ClassDefinition $class): void
    {
        $class->addBuiltinMethod(
            "identicalTo:",
            function (ClassInstance $receiver, ClassInstance $object): ClassInstance {
                // Nil class instance is always the same instance
                if ($object->getClassName() === "Nil") {
                    return $this->trueInstance();
                }
                return $this->falseInstance();
            }
        );

        $class->addBuiltinMethod("equalTo:", function (ClassInstance $receiver, ClassInstance $object): ClassInstance {
            // No intern value, compare Class name
            if ($object->getClassName() === "Nil") {
                return $this->trueInstance();
            }
            return $this->falseInstance();
        });

        $class->addBuiltinMethod("asString", function (ClassInstance $receiver): ClassInstance {
            $string = new ClassInstance("String");
            $string->setValue("nil");
            return $string;
        });

        $class->addBuiltinMethod("isNil", function (ClassInstance $receiver): ClassInstance {
            return $this->trueInstance();
        });
    }

    /**
     * Builds built-in instance methods for Integer class
     *
     * @param ClassDefinition $class Class definition instance of the class to build methods for
     */
    public function buildIntegerMethods(ClassDefinition $class): void
    {
        $class->addBuiltinMethod("equalTo:", function (ClassInstance $receiver, ClassInstance $object): ClassInstance {
            // Compare intern values
            if ($receiver->getValue() !== null && $object->getValue() !== null) {
                $equal = $receiver->getValue() === $object->getValue();
                if ($equal) {
                    return $this->trueInstance();
                }
            }
            return $this->falseInstance();
        });

        $class->addBuiltinMethod(
            "greaterThan:",
            function (ClassInstance $receiver, ClassInstance $object): ClassInstance {
                // This doesn't allow implicit PHP conversions in SOL25
                if (!is_int($object->getValue())) {
                    throw new IncorrectArgumentException("Incorrect argument value/type");
                }

                if ($receiver->getValue() > $object->getValue()) {
                    return $this->trueInstance();
                }
                return $this->falseInstance();
            }
        );

        $class->addBuiltinMethod("plus:", function (ClassInstance $receiver, ClassInstance $object): ClassInstance {
            $a = $receiver->getValue();
            $b = $object->getValue();

            if (!is_int($b) || !is_int($a)) {
                throw new IncorrectArgumentException("Incorrect argument value/type");
            }

            $result = new ClassInstance("Integer");
            $result->setValue($a + $b);
            return $result;
        });

        $class->addBuiltinMethod("minus:", function (ClassInstance $receiver, ClassInstance $object): ClassInstance {
            $a = $receiver->getValue();
            $b = $object->getValue();

            // The second check is just for PHPstan, it will never actually possibly happen
            if (!is_int($b) || !is_int($a)) {
                throw new IncorrectArgumentException("Incorrect argument value/type");
            }

            $result = new ClassInstance("Integer");
            $result->setValue($a - $b);
            return $result;
        });

        $class->addBuiltinMethod(
            "multiplyBy:",
            function (ClassInstance $receiver, ClassInstance $object): ClassInstance {
                $a = $receiver->getValue();
                $b = $object->getValue();

                if (!is_int($b) || !is_int($a)) {
                    throw new IncorrectArgumentException("Incorrect argument value/type");
                }

                $result = new ClassInstance("Integer");
                $result->setValue($a * $b);
                return $result;
            }
        );

        $class->addBuiltinMethod("divBy:", function (ClassInstance $receiver, ClassInstance $object): ClassInstance {
            $a = $receiver->getValue();
            $b = $object->getValue();

            if (!is_int($b) || !is_int($a)) {
                throw new IncorrectArgumentException("Incorrect argument value/type");
            }

            $a = (int)$a;
            $b = (int)$b;

            if ($b !== 0) {
                $result = new ClassInstance("Integer");
                $result->setValue(intdiv($a, $b));
                return $result;
            } else {
                throw new IncorrectArgumentException("Division by zero");
            }
        });

        $class->addBuiltinMethod("asString", function (ClassInstance $receiver): ClassInstance {
            // Returns string representation of intern attribute value
            $string = new ClassInstance("String");
            $value = $receiver->getValue();
            if (is_numeric($value)) {
                $string->setValue((string)$value);
            } else {
                $string->setValue("0");
            }
            return $string;
        });

        $class->addBuiltinMethod("isNumber", function (ClassInstance $receiver): ClassInstance {
            return $this->trueInstance();
        });

        $class->addBuiltinMethod("asInteger", function (ClassInstance $receiver): ClassInstance {
            // Returns itself
            return $receiver;
        });

        $class->addBuiltinMethod(
            "timesRepeat:",
            function (BlockScope $block, ClassInstance $receiver, ClassInstance $object): ClassInstance {
                $repeat_times = $receiver->getValue();

                // Default return value if for loop doesn't run
                $return_value = new ClassInstance("Nil");
                $return_value->setValue(null);

                // Sends 'value:' message, either to block or (hopefully) to something that understands it
                $argument = new ClassInstance("Integer");
                $argument->setValue(0);

                $sender = new MessageSender($block->getVariable("self"));

                // Repeats given times
                if ($repeat_times > 0) {
                    for ($i = 1; $i <= $repeat_times; $i++) {
                        $argument->setValue($i);
                        $return_value = $sender->invokeInstanceMethod($object, "value:", [$argument]);
                    }
                }

                return $return_value;
            }
        );
    }

    /**
     * Builds built-in instance methods for String class
     *
     * @param ClassDefinition $class Class definition instance of the class to build methods for
     */
    public function buildStringMethods(ClassDefinition $class): void
    {
        $class->addBuiltinMethod("equalTo:", function (ClassInstance $receiver, ClassInstance $object): ClassInstance {
            // Compares intern value attribute
            if ($receiver->getValue() !== null && $object->getValue() !== null) {
                $equal = $receiver->getValue() === $object->getValue();
                if ($equal) {
                    return $this->trueInstance();
                }
            }
            return $this->falseInstance();
        });

        $class->addBuiltinMethod("isString", function (ClassInstance $receiver): ClassInstance {
            return $this->trueInstance();
        });

        $class->addBuiltinMethod("print", function (ClassInstance $receiver): ClassInstance {
            $stdout = Interpreter::getStdoutWriter();
            $msg = $receiver->getValue();

            if ($stdout && is_string($msg)) {
                $escaped_msg = stripcslashes($msg);
                $stdout->writeString($escaped_msg);
            } else {
                // this should never happen, but just in case
                throw new MessageDNUException("Cant call print on non-string object");
            }
            return $receiver;
        });

        $class->addBuiltinMethod("asString", function (ClassInstance $receiver): ClassInstance {
            return $receiver;
        });

        $class->addBuiltinMethod("asInteger", function (ClassInstance $receiver): ClassInstance {
            // If given string is easily convertible to integer, return its integer value
            if (!is_numeric($receiver->getValue())) {
                $nil = new ClassInstance("Nil");
                $nil->setValue(null);
                return $nil;
            } else {
                $integer = new ClassInstance("Integer");
                $integer->setValue((int)$receiver->getValue());
                return $integer;
            }
        });

        $class->addBuiltinMethod(
            "concatenateWith:",
            function (ClassInstance $receiver, ClassInstance $object): ClassInstance {
                $str2 = $object->getValue();
                if (!is_string($str2)) {
                    $nil = new ClassInstance("Nil");
                    $nil->setValue(null);
                    return $nil;
                }

                $str1 = $receiver->getValue();
                if (!is_string($str1)) {
                    // this should never happen, but just in case
                    throw new MessageDNUException("Cant call concatenateWith: on non-string object");
                }

                $string = new ClassInstance("String");
                $string->setValue($str1 . $str2);
                return $string;
            }
        );

        $class->addBuiltinMethod(
            "startsWith:endsBefore:",
            function (ClassInstance $receiver, ClassInstance $from, ClassInstance $to): ClassInstance {
                // Returns substring
                if ($receiver->getValue() !== null && $from->getValue() !== null && $to->getValue() !== null) {
                    $original_string = $receiver->getValue();

                    if (!is_string($original_string)) {
                        // this should never happen, but just in case
                        throw new MessageDNUException("Cant call startsWith:endsBefore: on non-string object");
                    }

                    $start = $from->getValue();
                    $end = $to->getValue();

                    // Check correct argument values
                    if (
                        !is_int($start) || !is_int($end) || $start < 1 || $end < 1
                        || $end > strlen($original_string) + 1
                    ) {
                        $nil = new ClassInstance("Nil");
                        $nil->setValue(null);
                        return $nil;
                    }

                    $string = new ClassInstance("String");

                    if ($start >= $end) {
                        $string->setValue("");
                        return $string;
                    }

                    // Create the substring
                    $substring = substr($original_string, (int)$start - 1, (int)$end - $start);
                    $string->setValue($substring);
                    return $string;
                }

                $nil = new ClassInstance("Nil");
                $nil->setValue(null);
                return $nil;
            }
        );
    }

    /**
     * Builds built-in instance methods for Block class
     *
     * @param ClassDefinition $class Class definition instance of the class to build methods for
     */
    public function buildBlockMethods(ClassDefinition $class): void
    {
        $class->addBuiltinMethod("isBlock", function (ClassInstance $receiver): ClassInstance {
            return $this->trueInstance();
        });

        $class->addBuiltinMethod("value", function (BlockScope $block, ClassInstance $receiver): ClassInstance {
            $block_node = $receiver->getValue();
            if (!($block_node instanceof \DOMElement)) {
                throw new IncorrectArgumentException("Incorrect argument value/type");
            }

            $block->processBlock($block_node, []);
            $result = $block->getReturnValue();
            return $result;
        });

        $class->addBuiltinMethod(
            "value:",
            function (BlockScope $block, ClassInstance $receiver, ClassInstance $arg1): ClassInstance {
                $block_node = $receiver->getValue();
                if (!($block_node instanceof \DOMElement)) {
                    throw new IncorrectArgumentException("Incorrect argument value/type");
                }

                $block->processBlock($block_node, [$arg1]);
                $result = $block->getReturnValue();
                return $result;
            }
        );

        $class->addBuiltinMethod(
            "value:value:",
            function (
                BlockScope $block,
                ClassInstance $receiver,
                ClassInstance $arg1,
                ClassInstance $arg2
            ): ClassInstance {
                $block_node = $receiver->getValue();
                if (!($block_node instanceof \DOMElement)) {
                    throw new IncorrectArgumentException("Incorrect argument value/type");
                }

                $block->processBlock($block_node, [$arg1, $arg2]);
                $result = $block->getReturnValue();
                return $result;
            }
        );

        $class->addBuiltinMethod(
            "value:value:value:",
            function (
                BlockScope $block,
                ClassInstance $receiver,
                ClassInstance $arg1,
                ClassInstance $arg2,
                ClassInstance $arg3
            ): ClassInstance {
                $block_node = $receiver->getValue();
                if (!($block_node instanceof \DOMElement)) {
                    throw new IncorrectArgumentException("Incorrect argument value/type");
                }

                $block->processBlock($block_node, [$arg1, $arg2, $arg3]);
                $result = $block->getReturnValue();
                return $result;
            }
        );

        $class->addBuiltinMethod(
            "whileTrue:",
            function (BlockScope $block, ClassInstance $receiver, ClassInstance $object): ClassInstance {
                // Default return value if while doesn't run
                $ret_val = new ClassInstance("Nil");
                $ret_val->setValue(null);

                // Creates new sender with 'self' variable set
                $sender = new MessageSender($block->getVariable("self"));

                while (true) {
                    // Check if condition is true
                    $result = $sender->invokeInstanceMethod($receiver, "value", []);

                    if ($result->getClassName() !== "True") {
                        break;
                    }

                    // Send value message if condition was true
                    $ret_val = $sender->invokeInstanceMethod($object, "value", []);
                }

                return $ret_val;
            }
        );
    }

    /**
     * Builds built-in instance methods for True class
     *
     * @param ClassDefinition $class Class definition instance of the class to build methods for
     */
    public function buildTrueMethods(ClassDefinition $class): void
    {
        $class->addBuiltinMethod(
            "identicalTo:",
            function (ClassInstance $receiver, ClassInstance $object): ClassInstance {
                // True class instance is always the same instance
                if ($object->getClassName() === "True") {
                    return $this->trueInstance();
                }
                return $this->falseInstance();
            }
        );

        $class->addBuiltinMethod("equalTo:", function (ClassInstance $receiver, ClassInstance $object): ClassInstance {
            // True class instance is always the same instance
            if ($object->getClassName() === "True") {
                return $this->trueInstance();
            }
            return $this->falseInstance();
        });

        $class->addBuiltinMethod("not", function (ClassInstance $receiver): ClassInstance {
            return $this->falseInstance();
        });

        $class->addBuiltinMethod(
            "and:",
            function (BlockScope $block, ClassInstance $receiver, ClassInstance $object): ClassInstance {
                $sender = new MessageSender($block->getVariable("self"));
                $ret_val = $sender->invokeInstanceMethod($object, "value", []);
                return $ret_val;
            }
        );

        $class->addBuiltinMethod(
            "or:",
            function (BlockScope $block, ClassInstance $receiver, ClassInstance $object): ClassInstance {
                return $receiver;
            }
        );

        $class->addBuiltinMethod(
            "ifTrue:ifFalse:",
            function (
                BlockScope $block,
                ClassInstance $receiver,
                ClassInstance $true_object,
                ClassInstance $false_object
            ): ClassInstance {
                $sender = new MessageSender($block->getVariable("self"));

                // Send 'value' message to the first argument
                $ret_val = $sender->invokeInstanceMethod($true_object, "value", []);
                return $ret_val;
            }
        );

        $class->addBuiltinMethod("asString", function (ClassInstance $receiver): ClassInstance {
            $string = new ClassInstance("String");
            $string->setValue("true");
            return $string;
        });
    }

    /**
     * Builds built-in instance methods for False class
     *
     * @param ClassDefinition $class Class definition instance of the class to build methods for
     */
    public function buildFalseMethods(ClassDefinition $class): void
    {
        $class->addBuiltinMethod(
            "identicalTo:",
            function (ClassInstance $receiver, ClassInstance $object): ClassInstance {
                // False class instance is always the same instance
                if ($object->getClassName() === "False") {
                    return $this->trueInstance();
                }
                return $this->falseInstance();
            }
        );

        $class->addBuiltinMethod("equalTo:", function (ClassInstance $receiver, ClassInstance $object): ClassInstance {
            // False class instance is always the same instance
            if ($object->getClassName() === "False") {
                return $this->trueInstance();
            }
            return $this->falseInstance();
        });

        $class->addBuiltinMethod("not", function (ClassInstance $receiver): ClassInstance {
            return $this->trueInstance();
        });

        $class->addBuiltinMethod(
            "and:",
            function (BlockScope $block, ClassInstance $receiver, ClassInstance $object): ClassInstance {
                return $receiver;
            }
        );

        $class->addBuiltinMethod(
            "or:",
            function (BlockScope $block, ClassInstance $receiver, ClassInstance $object): ClassInstance {
                $sender = new MessageSender($block->getVariable("self"));
                $ret_val = $sender->invokeInstanceMethod($object, "value", []);
                return $ret_val;
            }
        );

        $class->addBuiltinMethod(
            "ifTrue:ifFalse:",
            function (
                BlockScope $block,
                ClassInstance $receiver,
                ClassInstance $true_object,
                ClassInstance $false_object
            ): ClassInstance {
                $sender = new MessageSender($block->getVariable("self"));

                // Send 'value' message to the second argument
                $ret_val = $sender->invokeInstanceMethod($false_object, "value", []);
                return $ret_val;
            }
        );

        $class->addBuiltinMethod("asString", function (ClassInstance $receiver): ClassInstance {
            $string = new ClassInstance("String");
            $string->setValue("false");
            return $string;
        });
    }
}
