<?php

namespace IPP\Student;

use IPP\Student\Exception\IncorrectArgumentException;
use IPP\Student\Exception\MessageDNUException;

class BuiltinMethodBuilder
{
    // Returns new instance of True class
    private function trueInstance(): ClassInstance
    {
        $true = new ClassInstance("True");
        $true->setValue(true);
        return $true;
    }

    // Returns new instance of False class
    private function falseInstance(): ClassInstance
    {
        $false = new ClassInstance("False");
        $false->setValue(false);
        return $false;
    }

    // Build in insatnce methods for Object class
    public function buildObjectMethods(ClassDefinition $class): void
    {
        $class->addBuiltinMethod("identicalTo:", function(ClassInstance $receiver, ClassInstance $object): ClassInstance
        {
            // Exact same class instance
            if ($receiver === $object) {
                return $this->trueInstance();
            }

            return $this->falseInstance();
        });

        $class->addBuiltinMethod("equalTo:", function(ClassInstance $receiver, ClassInstance $object): ClassInstance
        {
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

        $class->addBuiltinMethod("asString", function(ClassInstance $receiver): ClassInstance
        {
            $string = new ClassInstance("String");
            $string->setValue("");
            return $string;
        });

        $class->addBuiltinMethod("isNumber", function(ClassInstance $receiver): ClassInstance
        {
            return $this->falseInstance();
        });

        $class->addBuiltinMethod("isString", function(ClassInstance $receiver): ClassInstance
        {
            return $this->falseInstance();
        });

        $class->addBuiltinMethod("isBlock", function(ClassInstance $receiver): ClassInstance
        {
            return $this->falseInstance();
        });

        $class->addBuiltinMethod("isNil", function(ClassInstance $receiver): ClassInstance
        {
            return $this->falseInstance();
        });
    }

    public function buildNilMethods(ClassDefinition $class): void
    {
        $class->addBuiltinMethod("identicalTo:", function(ClassInstance $receiver, ClassInstance $object): ClassInstance
        {
            if ($object->getClassName() === "Nil") {
                return $this->trueInstance();
            }
            return $this->falseInstance();
        });

        $class->addBuiltinMethod("equalTo:", function(ClassInstance $receiver, ClassInstance $object): ClassInstance
        {
            if ($object->getClassName() === "Nil") {
                return $this->trueInstance();
            }
            return $this->falseInstance();
        });

        $class->addBuiltinMethod("asString", function(ClassInstance $receiver): ClassInstance
        {
            $string = new ClassInstance("String");
            $string->setValue("nil");
            return $string;
        });

        $class->addBuiltinMethod("isNil", function(ClassInstance $receiver): ClassInstance
        {
            return $this->trueInstance();
        });
    }

    public function buildIntegerMethods(ClassDefinition $class): void
    {
        $class->addBuiltinMethod("equalTo:", function(ClassInstance $receiver, ClassInstance $object): ClassInstance
        {
            if ($receiver->getValue() !== null && $object->getValue() !== null) {
                $equal = $receiver->getValue() === $object->getValue();
                if ($equal) {
                    return $this->trueInstance();
                }
            }
            return $this->falseInstance();
        });

        $class->addBuiltinMethod("greaterThan:", function(ClassInstance $receiver, ClassInstance $object): ClassInstance
        {
            // This doesn't allow implicit PHP conversions in SOL25
            if (!is_int($object->getValue())){
                throw new IncorrectArgumentException("Incorrect argument value/type");
            }

            if ($receiver->getValue() > $object->getValue()) {
                return $this->trueInstance();
            }
            return $this->falseInstance();
        });

        $class->addBuiltinMethod("plus:", function(ClassInstance $receiver, ClassInstance $object): ClassInstance
        {
            $a = $receiver->getValue();
            $b = $object->getValue();

            if (!is_int($b) || !is_int($a)){
                throw new IncorrectArgumentException("Incorrect argument value/type");
            }
        
            $result = new ClassInstance("Integer");
            $result->setValue($a + $b);
            return $result;
        });

        $class->addBuiltinMethod("minus:", function(ClassInstance $receiver, ClassInstance $object): ClassInstance
        {
            $a = $receiver->getValue();
            $b = $object->getValue();

            // The second check is just for PHPstan, it will never actually possibly happend
            if (!is_int($b) || !is_int($a)){
                throw new IncorrectArgumentException("Incorrect argument value/type");
            }

            $result = new ClassInstance("Integer");
            $result->setValue($a - $b);
            return $result;
        });

        $class->addBuiltinMethod("multiplyBy:", function(ClassInstance $receiver, ClassInstance $object): ClassInstance
        {
            $a = $receiver->getValue();
            $b = $object->getValue();

            if (!is_int($b) || !is_int($a)){
                throw new IncorrectArgumentException("Incorrect argument value/type");
            }

            $result = new ClassInstance("Integer");
            $result->setValue($a * $b);
            return $result;
        });

        $class->addBuiltinMethod("divBy:", function(ClassInstance $receiver, ClassInstance $object): ClassInstance
        {
            $a = $receiver->getValue();
            $b = $object->getValue();

            if (!is_int($b) || !is_int($a)){
                throw new IncorrectArgumentException("Incorrect argument value/type");
            }

            $a = (int)$a;
            $b = (int)$b;

            if ($b !== 0){
                $result = new ClassInstance("Integer");
                $result->setValue(intdiv($a, $b));
                return $result;
            }
            else{
                throw new IncorrectArgumentException("Division by zero");
            }
        });

        $class->addBuiltinMethod("asString", function(ClassInstance $receiver): ClassInstance
        {
            $string = new ClassInstance("String");
            $value = $receiver->getValue();
            if (is_numeric($value)){
                $string->setValue((string)$value);
            }
            else{
                $string->setValue("0");
            }
            return $string;
        });

        $class->addBuiltinMethod("isNumber", function(ClassInstance $receiver): ClassInstance
        {
            return $this->trueInstance();
        });

        $class->addBuiltinMethod("asInteger", function(ClassInstance $receiver): ClassInstance
        {
            return $receiver;
        });

        $class->addBuiltinMethod("timesRepeat:", function(MethodBlock $block, ClassInstance $receiver, ClassInstance $object): ClassInstance
        {
            $repeat_times = $receiver->getValue();

            // Default return value if for doesn't run
            $retrn_value = new ClassInstance("Nil");
            $retrn_value->setValue(null);

            $obj_type = $object->getClassName();

            // Not a block, but something that understand 'value:' message (hopefully)
            if ($obj_type !== "Object"){
                $argument = new ClassInstance("Integer");
                $argument->setValue(0);

                if ($repeat_times > 0) {
                    for ($i = 1; $i <= $repeat_times; $i++) {
                        $argument->setValue($i);
                        $retrn_value = $block->invokeInstanceMethod($object, "value:", [$argument]);
                    }
                    return $retrn_value;
                }
            }

            if ($repeat_times > 0) {
                $block_node = $object->getValue();
                if (!($block_node instanceof \DOMElement)){
                    throw new IncorrectArgumentException("Incorrect argument value/type");
                }    

                $argument = new ClassInstance("Integer");
                $argument->setValue(0);

                for ($i = 1; $i <= $repeat_times; $i++) {
                    $argument->setValue($i);
                    $block->processBlock($block_node, [$argument]);

                    $retrn_value = $block->getReturnValue();
                }
            }
            return $retrn_value;
        });
    }

    public function buildStringMethods(ClassDefinition $class): void
    {
        $class->addBuiltinMethod("equalTo:", function(ClassInstance $receiver, ClassInstance $object): ClassInstance
        {
            if ($receiver->getValue() !== null && $object->getValue() !== null) {
                $equal = $receiver->getValue() === $object->getValue();
                if ($equal) {
                    return $this->trueInstance();
                }
            }
            return $this->falseInstance();
        });

        $class->addBuiltinMethod("isString", function(ClassInstance $receiver): ClassInstance
        {
            return $this->trueInstance();
        });

        $class->addBuiltinMethod("print", function(ClassInstance $receiver): ClassInstance
        {
            $stdout = Interpreter::getStdoutWriter();
            $msg = $receiver->getValue();

            if ($stdout && is_string($msg)){
                $stdout->writeString($msg);
            }
            else{
                // this should never happen, but just in case
                throw new MessageDNUException("Cant call print on non-string object");
            }
            return $receiver;
        });

        $class->addBuiltinMethod("asString", function(ClassInstance $receiver): ClassInstance
        {
            return $receiver;
        });

        $class->addBuiltinMethod("asInteger", function(ClassInstance $receiver): ClassInstance
        {
            if (!is_numeric($receiver->getValue())){
                $nil = new ClassInstance("Nil");
                $nil->setValue(null);
                return $nil;    
            }
            else{
                $integer = new ClassInstance("Integer");
                $integer->setValue((int)$receiver->getValue());
                return $integer;
            }
        });

        $class->addBuiltinMethod("concatenateWith:", function(ClassInstance $receiver, ClassInstance $object): ClassInstance
        {
            $str2 = $object->getValue();
            if (!is_string($str2)){
                $nil = new ClassInstance("Nil");
                $nil->setValue(null);
                return $nil;
            }

            $str1 = $receiver->getValue();
            if (!is_string($str1)){
                // this should never happen, but just in case
                throw new MessageDNUException("Cant call concatenateWith: on non-string object");
            }

            $string = new ClassInstance("String");
            $string->setValue($str1 . $str2);
            return $string;
        });

        $class->addBuiltinMethod("startsWith:endsBefore:", function(ClassInstance $receiver, ClassInstance $from, ClassInstance $to): ClassInstance
        {
            if ($receiver->getValue() !== null && $from->getValue() !== null && $to->getValue() !== null) {
                $original_string = $receiver->getValue();

                if (!is_string($original_string)){
                    // this should never happen, but just in case
                    throw new MessageDNUException("Cant call startsWith:endsBefore: on non-string object");
                }

                $start = $from->getValue();
                $end = $to->getValue();

                if (!is_int($start) || !is_int($end) || $start < 1 || $end < 1 || $end > strlen($original_string)+1) {
                    $nil = new ClassInstance("Nil");
                    $nil->setValue(null);
                    return $nil;
                }

                $string = new ClassInstance("String");

                if ($start >= $end) {
                    $string->setValue("");
                    return $string;
                }

                $substring = substr($original_string, (int)$start-1, (int)$end-$start);
                $string->setValue($substring);
                return $string;
            }

            $nil = new ClassInstance("Nil");
            $nil->setValue(null);
            return $nil;
        });
    }

    public function buildBlockMethods(ClassDefinition $class): void
    {
        $class->addBuiltinMethod("isBlock", function(ClassInstance $receiver): ClassInstance
        {
            return $this->trueInstance();
        });

        $class->addBuiltinMethod("value", function(MethodBlock $block, ClassInstance $receiver): ClassInstance
        {
            $block_node = $receiver->getValue();
            if (!($block_node instanceof \DOMElement)){
                throw new IncorrectArgumentException("Incorrect argument value/type");
            }

            $block->processBlock($block_node, []);
            $result = $block->getReturnValue();
            return $result;
        });

        $class->addBuiltinMethod("value:", function(MethodBlock $block, ClassInstance $receiver, ClassInstance $arg1): ClassInstance
        {
            $block_node = $receiver->getValue();
            if (!($block_node instanceof \DOMElement)){
                throw new IncorrectArgumentException("Incorrect argument value/type");
            }

            $block->processBlock($block_node, [$arg1]);
            $result = $block->getReturnValue();
            return $result;
        });

        $class->addBuiltinMethod("value:value:", function(MethodBlock $block, ClassInstance $receiver, ClassInstance $arg1, ClassInstance $arg2): ClassInstance
        {
            $block_node = $receiver->getValue();
            if (!($block_node instanceof \DOMElement)){
                throw new IncorrectArgumentException("Incorrect argument value/type");
            }

            $block->processBlock($block_node, [$arg1, $arg2]);
            $result = $block->getReturnValue();
            return $result;
        });

        $class->addBuiltinMethod("value:value:value:", function(MethodBlock $block, ClassInstance $receiver, ClassInstance $arg1, ClassInstance $arg2, ClassInstance $arg3): ClassInstance
        {
            $block_node = $receiver->getValue();
            if (!($block_node instanceof \DOMElement)){
                throw new IncorrectArgumentException("Incorrect argument value/type");
            }

            $block->processBlock($block_node, [$arg1, $arg2, $arg3]);
            $result = $block->getReturnValue();
            return $result;
        });

        $class->addBuiltinMethod("whileTrue:", function(MethodBlock $block, ClassInstance $receiver, ClassInstance $object): ClassInstance
        {
            // Default return value if for doesn't run
            $ret_val = new ClassInstance("Nil");
            $ret_val->setValue(null);

            // Get the block node from the XML that represents the condition
            $condition_node = $receiver->getValue();
            if (!($condition_node instanceof \DOMElement)){
                // this should never happen, but just in case
                throw new MessageDNUException("Cant call whileTrue: on non-block object");
            }

            $obj_type = $object->getClassName();
            // Sends 'value:' message instead
            if ($obj_type !== "Object") {
                while(true) {
                    // Check if condition is true
                    $block->processBlock($condition_node, []);
                    $result = $block->getReturnValue();
    
                    if ($result->getClassName() !== "True") {
                        break;
                    }

                    // Send value message if condition was true
                    $ret_val = $block->invokeInstanceMethod($object, "value", []);
                }
                return $ret_val;
            }

            $execute_node = $object->getValue();
            if (!($execute_node instanceof \DOMElement)){
                throw new IncorrectArgumentException("Incorrect argument value/type");
            }

            while(true) {
                // Check if condition is true
                $block->processBlock($condition_node, []);
                $result = $block->getReturnValue();

                if ($result->getClassName() !== "True") {
                    break;
                }
                // Execute the block if condition was true
                $block->processBlock($execute_node, []);
                $ret_val = $block->getReturnValue();
            }

            return $ret_val;
        });
    }

    public function buildTrueMethods(ClassDefinition $class): void
    {
        $class->addBuiltinMethod("identicalTo:", function(ClassInstance $receiver, ClassInstance $object): ClassInstance
        {
            if ($object->getClassName() === "True") {
                return $this->trueInstance();
            }
            return $this->falseInstance();
        });

        $class->addBuiltinMethod("equalTo:", function(ClassInstance $receiver, ClassInstance $object): ClassInstance
        {
            if ($object->getClassName() === "True") {
                return $this->trueInstance();
            }
            return $this->falseInstance();
        });

        $class->addBuiltinMethod("not", function(ClassInstance $receiver): ClassInstance
        {
            return $this->falseInstance();
        });

        $class->addBuiltinMethod("and:", function(MethodBlock $block, ClassInstance $receiver, ClassInstance $object): ClassInstance
        {
            $ret_val= $block->invokeInstanceMethod($object, "value", []);
            return $ret_val;
        });

        $class->addBuiltinMethod("or:", function(MethodBlock $block, ClassInstance $receiver, ClassInstance $object): ClassInstance
        {
            return $receiver;
        });

        $class->addBuiltinMethod("ifTrue:ifFalse:", function(MethodBlock $block, ClassInstance $receiver, ClassInstance $true_object, ClassInstance $false_object): ClassInstance
        {
            // Get the block node from the XML that is to be processed
            $block_node = $true_object->getValue();
            if (!($block_node instanceof \DOMElement)){
                throw new IncorrectArgumentException("Incorrect argument value/type");
            }

            // Process the block
            $block->processBlock($block_node, []);
            $ret_val = $block->getReturnValue();
            return $ret_val;
        });

        // TODO: not in spec
        // $class->addBuiltinMethod("asString", function(ClassInstance $receiver): ClassInstance
        // {
        //     $string = new ClassInstance("String");
        //     $string->setValue("true");
        //     return $string;
        // });
    }

    public function buildFalseMethods(ClassDefinition $class): void
    {
        $class->addBuiltinMethod("identicalTo:", function(ClassInstance $receiver, ClassInstance $object): ClassInstance
        {
            if ($object->getClassName() === "False") {
                return $this->trueInstance();
            }
            return $this->falseInstance();
        });

        $class->addBuiltinMethod("equalTo:", function(ClassInstance $receiver, ClassInstance $object): ClassInstance
        {
            if ($object->getClassName() === "False") {
                return $this->trueInstance();
            }
            return $this->falseInstance();
        });

        $class->addBuiltinMethod("not", function(ClassInstance $receiver): ClassInstance
        {
            return $this->trueInstance();
        });

        $class->addBuiltinMethod("and:", function(MethodBlock $block, ClassInstance $receiver, ClassInstance $object): ClassInstance
        {
            return $receiver;
        });

        $class->addBuiltinMethod("or:", function(MethodBlock $block, ClassInstance $receiver, ClassInstance $object): ClassInstance
        {
            $ret_val = $block->invokeInstanceMethod($object, "value", []);
            return $ret_val;
        });

        $class->addBuiltinMethod("ifTrue:ifFalse:", function(MethodBlock $block, ClassInstance $receiver, ClassInstance $true_object, ClassInstance $false_object): ClassInstance
        {
            // Get the block node from the XML that is to be processed
            $block_node = $false_object->getValue();
            if (!($block_node instanceof \DOMElement)){
                throw new IncorrectArgumentException("Incorrect argument value/type");
            }

            // Process the block
            $block->processBlock($block_node, []);
            $ret_val = $block->getReturnValue();
            return $ret_val;
        });

        // TODO: not in spec
        // $class->addBuiltinMethod("asString", function(ClassInstance $receiver): ClassInstance
        // {
        //     $string = new ClassInstance("String");
        //     $string->setValue("false");
        //     return $string;
        // });
    }
}
