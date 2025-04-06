<?php

namespace IPP\Student;

use IPP\Student\Exception\IncorrectArgumentException;

class Method_builder
{
    // Build in insatnce methods for Object class
    public function build_object_methods(Class_definition $class): void
    {
        $class->add_builtIn_method("identicalTo:", function(Class_instance $receiver, Class_instance $object): Class_instance
        {
            // Exact same class instance
            if ($receiver === $object) {
                $result = new Class_instance("True");
                $result->set_value(true);
                return $result;
            }

            $result = new Class_instance("False");
            $result->set_value(false);
            return $result;
        });

        $class->add_builtIn_method("equalTo:", function(Class_instance $receiver, Class_instance $object): Class_instance
        {
            if ($receiver->get_value() !== null && $object->get_value() !== null) {
                $equal = $receiver->get_value() === $object->get_value();
                if ($equal) {
                    $result = new Class_instance("True");
                    $result->set_value(true);
                    return $result;
                }
            }
            $result = new Class_instance("False");
            $result->set_value(false);
            return $result;
        });

        $class->add_builtIn_method("asString", function(Class_instance $receiver): Class_instance
        {
            $string = new Class_instance("String");
            $string->set_value("");
            return $string;
        });

        $class->add_builtIn_method("isNumber", function(Class_instance $receiver): Class_instance
        {
            $result = new Class_instance("False");
            $result->set_value(false);
            return $result;
        });

        $class->add_builtIn_method("isString", function(Class_instance $receiver): Class_instance
        {
            $result = new Class_instance("False");
            $result->set_value(false);
            return $result;
        });

        $class->add_builtIn_method("isBlock", function(Class_instance $receiver): Class_instance
        {
            $result = new Class_instance("False");
            $result->set_value(false);
            return $result;
        });

        $class->add_builtIn_method("isNil", function(Class_instance $receiver): Class_instance
        {
            $result = new Class_instance("False");
            $result->set_value(false);
            return $result;
        });
    }

    public function build_nil_methods(Class_definition $class): void
    {
        $class->add_builtIn_method("identicalTo:", function(Class_instance $receiver, Class_instance $object): Class_instance
        {
            if ($object->get_class_name() === "Nil") {
                $result = new Class_instance("True");
                $result->set_value(true);
                return $result;
            }
            $result = new Class_instance("False");
            $result->set_value(false);
            return $result;
        });

        $class->add_builtIn_method("equalTo:", function(Class_instance $receiver, Class_instance $object): Class_instance
        {
            if ($object->get_class_name() === "Nil") {
                $result = new Class_instance("True");
                $result->set_value(true);
                return $result;
            }
            $result = new Class_instance("False");
            $result->set_value(false);
            return $result;
        });

        $class->add_builtIn_method("asString", function(Class_instance $receiver): Class_instance
        {
            $string = new Class_instance("String");
            $string->set_value("nil");
            return $string;
        });

        $class->add_builtIn_method("isNil", function(Class_instance $receiver): Class_instance
        {
            $result = new Class_instance("True");
            $result->set_value(true);
            return $result;
        });
    }

    // TODO: what if the second object is not a number?

    public function build_integer_methods(Class_definition $class): void
    {
        $class->add_builtIn_method("identicalTo:", function(Class_instance $receiver, Class_instance $object): Class_instance
        {
            $result = new Class_instance("False");
            $result->set_value(false);
            return $result;
        });

        $class->add_builtIn_method("equalTo:", function(Class_instance $receiver, Class_instance $object): Class_instance
        {
            if ($receiver->get_value() !== null && $object->get_value() !== null) {
                $equal = $receiver->get_value() === $object->get_value();
                if ($equal) {
                    $result = new Class_instance("True");
                    $result->set_value(true);
                    return $result;
                }
            }
            $result = new Class_instance("False");
            $result->set_value(false);
            return $result;
        });

        $class->add_builtIn_method("greaterThan:", function(Class_instance $receiver, Class_instance $object): Class_instance
        {
            if ($receiver->get_value() !== null && $object->get_value() !== null) {
                if ($receiver->get_value() > $object->get_value()) {
                    $result = new Class_instance("True");
                    $result->set_value(true);
                    return $result;
                }
            }
            $result = new Class_instance("False");
            $result->set_value(false);
            return $result;
        });

        $class->add_builtIn_method("plus:", function(Class_instance $receiver, Class_instance $object): Class_instance
        {
            if ($receiver->get_value() !== null && $object->get_value() !== null) {
                $receiver->set_value($receiver->get_value() + $object->get_value());
            }
            return $receiver;
        });

        $class->add_builtIn_method("minus:", function(Class_instance $receiver, Class_instance $object): Class_instance
        {
            if ($receiver->get_value() !== null && $object->get_value() !== null) {
                $receiver->set_value($receiver->get_value() - $object->get_value());
            }
            return $receiver;
        });

        $class->add_builtIn_method("multiplyBy:", function(Class_instance $receiver, Class_instance $object): Class_instance
        {
            if ($receiver->get_value() !== null && $object->get_value() !== null) {
                $receiver->set_value($receiver->get_value() * $object->get_value());
            }
            return $receiver;
        });

        $class->add_builtIn_method("divBy:", function(Class_instance $receiver, Class_instance $object): Class_instance
        {
            if ($receiver->get_value() !== null && $object->get_value() !== null) {
                if ($object->get_value() !== 0) {
                    $receiver->set_value(intdiv($receiver->get_value(), $object->get_value()));
                }
                else{
                    throw new IncorrectArgumentException("Division by zero");
                }
            }
            return $receiver;
        });

        $class->add_builtIn_method("asString", function(Class_instance $receiver): Class_instance
        {
            $string = new Class_instance("String");
            if ($receiver->get_value() !== null) {
                $string->set_value((string)$receiver->get_value());
            } else {
                $string->set_value("0");
            }
            return $string;
        });

        $class->add_builtIn_method("isNumber", function(Class_instance $receiver): Class_instance
        {
            $result = new Class_instance("True");
            $result->set_value(true);
            return $result;
        });

        $class->add_builtIn_method("asInteger", function(Class_instance $receiver): Class_instance
        {
            return $receiver;
        });

        // TODO: check if this works correctly
        $class->add_builtIn_method("timesRepeat:", function(Method_block $block, Class_instance $receiver, Class_instance $object): Class_instance
        {
            $repeat_times = $receiver->get_value();

            // Default return value if for doesn't run
            $retrn_value = new Class_instance("Nil");
            $retrn_value->set_value(null);

            if ($repeat_times > 0) {
                $block_node = $object->get_value();

                $argument = new Class_instance("Integer");
                $argument->set_value(0);

                for ($i = 1; $i <= $repeat_times; $i++) {
                    $argument->set_value($i);
                    $block->process_block($block_node, [$argument]);
                }
            }
            return $retrn_value;
        });
    }

    public function build_string_methods(Class_definition $class): void
    {
        $class->add_builtIn_method("identicalTo:", function(Class_instance $receiver, Class_instance $object): Class_instance
        {
            $result = new Class_instance("False");
            $result->set_value(false);
            return $result;
        });

        $class->add_builtIn_method("equalTo:", function(Class_instance $receiver, Class_instance $object): Class_instance
        {
            if ($receiver->get_value() !== null && $object->get_value() !== null) {
                $equal = $receiver->get_value() === $object->get_value();
                if ($equal) {
                    $result = new Class_instance("True");
                    $result->set_value(true);
                    return $result;
                }
            }
            $result = new Class_instance("False");
            $result->set_value(false);
            return $result;
        });

        $class->add_builtIn_method("isString", function(Class_instance $receiver): Class_instance
        {
            $result = new Class_instance("True");
            $result->set_value(true);
            return $result;
        });

        $class->add_builtIn_method("print", function(Class_instance $receiver): Class_instance
        {
            $stdout = Interpreter::get_stdout_writer();
            if ($stdout && $receiver->get_value() !== null) {
                $stdout->writeString($receiver->get_value());
            }
            return $receiver;
        });

        $class->add_builtIn_method("asString", function(Class_instance $receiver): Class_instance
        {
            return $receiver;
        });

        $class->add_builtIn_method("asInteger", function(Class_instance $receiver): Class_instance
        {
            if ($receiver->get_value() !== null) {
                $integer = new Class_instance("Integer");
                $integer->set_value((int)$receiver->get_value());
                return $integer;
            }
            $nil = new Class_instance("Nil");
            $nil->set_value(null);
            return $nil;
        });

        $class->add_builtIn_method("concatenateWith:", function(Class_instance $receiver, Class_instance $object): Class_instance
        {
            $string = new Class_instance("String");
            if ($receiver->get_value() !== null && $object->get_value() !== null) {
                $string->set_value($receiver->get_value() . $object->get_value());
            }
            else if ($receiver->get_value() !== null) {
                $string->set_value($receiver->get_value());
            }
            else if ($object->get_value() !== null) {
                $string->set_value($object->get_value());
            }
            else {
                $string->set_value("");
            }
            return $string;
        });

        $class->add_builtIn_method("startsWith:endsBefore:", function(Class_instance $receiver, Class_instance $from, Class_instance $to): Class_instance
        {
            if ($receiver->get_value() !== null && $from->get_value() !== null && $to->get_value() !== null) {
                $start = $from->get_value();
                $end = $to->get_value();

                if ($start < 1 || $end < 1 || $end > strlen($receiver->get_value())+1) {
                    $nil = new Class_instance("Nil");
                    $nil->set_value(null);
                    return $nil;
                }

                $string = new Class_instance("String");

                if ($start >= $end) {
                    $string->set_value("");
                    return $string;
                }

                $substring = substr($receiver->get_value(), $start-1, $end-$start);
                $string->set_value($substring);
                return $string;
            }

            $nil = new Class_instance("Nil");
            $nil->set_value(null);
            return $nil;
        });
    }

    public function build_block_methods(Class_definition $class): void
    {
        $class->add_builtIn_method("isBlock", function(Class_instance $receiver): Class_instance
        {
            $result = new Class_instance("True");
            $result->set_value(true);
            return $result;
        });

        // TODO: check if this works correctly
        $class->add_builtIn_method("value", function(Method_block $block, Class_instance $receiver): Class_instance
        {
            $block_node = $receiver->get_value();
            $block->process_block($block_node, []);
            $result = $block->get_return_value();
            return $result;
        });

        $class->add_builtIn_method("value:", function(Method_block $block, Class_instance $receiver, Class_instance $arg1): Class_instance
        {
            $block_node = $receiver->get_value();
            $block->process_block($block_node, [$arg1]);
            $result = $block->get_return_value();
            return $result;
        });

        $class->add_builtIn_method("value:value:", function(Method_block $block, Class_instance $receiver, Class_instance $arg1, Class_instance $arg2): Class_instance
        {
            $block_node = $receiver->get_value();
            $block->process_block($block_node, [$arg1, $arg2]);
            $result = $block->get_return_value();
            return $result;
        });

        $class->add_builtIn_method("value:value:value:", function(Method_block $block, Class_instance $receiver, Class_instance $arg1, Class_instance $arg2, Class_instance $arg3): Class_instance
        {
            $block_node = $receiver->get_value();
            $block->process_block($block_node, [$arg1, $arg2, $arg3]);
            $result = $block->get_return_value();
            return $result;
        });

        // TODO: check if this works correctly
        $class->add_builtIn_method("whileTrue:", function(Method_block $block, Class_instance $receiver, Class_instance $object): Class_instance
        {
            // Default return value if for doesn't run
            $ret_val = new Class_instance("Nil");
            $ret_val->set_value(null);

            // Get the block node from the XML that represents the condition
            $condition_node = $receiver->get_value();

            $obj_type = $object->get_class_name();
            if ($obj_type !== "Object") {
                // TODO: send 'value' message
            }

            $execute_node = $object->get_value();

            while(true) {
                // Check if condition is true
                $block->process_block($condition_node, []);
                $result = $block->get_return_value();
                if ($result === null || $result->get_class_name() !== "True") {
                    break;
                }
                // Execute the block if condition was true
                $block->process_block($execute_node, []);
                $ret_val = $block->get_return_value();
            }

            return $ret_val;
        });
    }

    public function build_true_methods(Class_definition $class): void
    {
        $class->add_builtIn_method("identicalTo:", function(Class_instance $receiver, Class_instance $object): Class_instance
        {
            if ($object->get_class_name() === "True") {
                $result = new Class_instance("True");
                $result->set_value(true);
                return $result;
            }
            $result = new Class_instance("False");
            $result->set_value(false);
            return $result;
        });

        $class->add_builtIn_method("equalTo:", function(Class_instance $receiver, Class_instance $object): Class_instance
        {
            if ($object->get_class_name() === "True") {
                $result = new Class_instance("True");
                $result->set_value(true);
                return $result;
            }
            $result = new Class_instance("False");
            $result->set_value(false);
            return $result;
        });

        $class->add_builtIn_method("not", function(Class_instance $receiver): Class_instance
        {
            $false = new Class_instance("False");
            $false->set_value(false);
            return $false;
        });

        $class->add_builtIn_method("and:", function(Class_instance $receiver, Class_instance $object): Class_instance
        {
            return $object;
        });

        $class->add_builtIn_method("or:", function(Class_instance $receiver, Class_instance $object): Class_instance
        {
            return $receiver;
        });

        // TODO: check it if it works - code goes without errors.... but functionality?
        $class->add_builtIn_method("ifTrue:ifFalse:", function(Method_block $block, Class_instance $receiver, Class_instance $true_object, Class_instance $false_object): Class_instance
        {
            // Get the block node from the XML that is to be processed
            $block_node = $true_object->get_value();

            if ($receiver->get_value() !== true) {
                // This shoul never execute, but just in case
                // Get the block node from the XML that is to be processed
                $block_node = $false_object->get_value();
            }

            // Process the block
            $block->process_block($block_node, []);
            $ret_val = $block->get_return_value();
            return $ret_val;
        });

        // TODO: not in spec
        $class->add_builtIn_method("asString", function(Class_instance $receiver): Class_instance
        {
            $string = new Class_instance("String");
            $string->set_value("true");
            return $string;
        });
    }

    public function build_false_methods(Class_definition $class): void
    {
        $class->add_builtIn_method("identicalTo:", function(Class_instance $receiver, Class_instance $object): Class_instance
        {
            if ($object->get_class_name() === "False") {
                $result = new Class_instance("True");
                $result->set_value(true);
                return $result;
            }
            $result = new Class_instance("False");
            $result->set_value(false);
            return $result;
        });

        $class->add_builtIn_method("equalTo:", function(Class_instance $receiver, Class_instance $object): Class_instance
        {
            if ($object->get_class_name() === "False") {
                $result = new Class_instance("True");
                $result->set_value(true);
                return $result;
            }
            $result = new Class_instance("False");
            $result->set_value(false);
            return $result;
        });

        $class->add_builtIn_method("not", function(Class_instance $receiver): Class_instance
        {
            $true = new Class_instance("True");
            $true->set_value(true);
            return $true;
        });

        $class->add_builtIn_method("and:", function(Class_instance $receiver, Class_instance $object): Class_instance
        {
            return $receiver;
        });

        $class->add_builtIn_method("or:", function(Class_instance $receiver, Class_instance $object): Class_instance
        {
            return $object;
        });

        $class->add_builtIn_method("ifTrue:ifFalse:", function(Method_block $block, Class_instance $receiver, Class_instance $true_object, Class_instance $false_object): Class_instance
        {
            // Get the block node from the XML that is to be processed
            $block_node = $false_object->get_value();

            if ($receiver->get_value() !== false) {
                // This shoul never execute, but just in case
                // Get the block node from the XML that is to be processed
                $block_node = $true_object->get_value();
            }

            // Process the block
            $block->process_block($block_node, []);
            $ret_val = $block->get_return_value();
            return $ret_val;
        });

        // TODO: not in spec
        $class->add_builtIn_method("asString", function(Class_instance $receiver): Class_instance
        {
            $string = new Class_instance("String");
            $string->set_value("false");
            return $string;
        });
    }
}
