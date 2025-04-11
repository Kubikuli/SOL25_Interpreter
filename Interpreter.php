<?php

namespace IPP\Student;

use IPP\Core\AbstractInterpreter;
use IPP\Core\Interface\InputReader;
use IPP\Core\Interface\OutputWriter;
use IPP\Core\ReturnCode;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\CSS\MissingColonSniff;

use IPP\Student\Exception\MissingMainRunException;
use IPP\Student\Exception\UsingUndefinedException;

// Super implementation - Cant use __SUPER__ as string value
    // $val = $this->input->readString();
    // $this->stdout->writeString("stdout");
    // $this->stderr->writeString("stderr");


class Interpreter extends AbstractInterpreter
{
    static public Interpreter $instance;

    // Had to do this unfortunatelly this way, because I didn't want to change my whole 
    // design because I couldn't access this and I found out too late I needed it

    // Add a method to access the input reader
    public static function getInputReader(): ?InputReader
    {
        if (isset(self::$instance)) {
            return self::$instance->input;
        }
        return null;
    }

    // Add a method to access the input reader
    public static function getStdoutWriter(): ?OutputWriter
    {
        if (isset(self::$instance)) {
            return self::$instance->stdout;
        }
        return null;
    }

    // TODO: maybe remove this
    // Add a method to access the input reader
    public static function getStderrWriter(): ?OutputWriter
    {
        if (isset(self::$instance)) {
            return self::$instance->stderr;
        }
        return null;
    }
    
    public function execute(): int
    {
        self::$instance = $this;

        $dom = $this->source->getDOMDocument();

        // Parse program structure
        $parser = new Parser();
        $parser->parse($dom);

        // Insert built-in class definitions
        $this->defineBuiltinClasses();

        // Execute "run" method from "Main"
        $this->executeRunMethod("Main", "run", []);

        return ReturnCode::OK;
    }

    /**
     * @param array<ClassInstance> $args Arguments for the method call
     */
    private function executeRunMethod(string $class_name, string $method_name, array $args): void
    {
        try {
            // Check if class exists
            $method = ClassDefinition::getMethod($class_name, $method_name);
        } 
        catch (UsingUndefinedException) {
            throw new MissingMainRunException("Class not found: " . $class_name);
        }

        // Main class instance
        $main_class = new ClassInstance($class_name);
        $block = new MethodBlock();

        $block->setVariable("self", $main_class);

        // Check if given class understands given message
        if ($method instanceof \DOMElement){
            $child = $method->getElementsByTagName("block")->item(0);
            // Skip the method node and send block node as argument
            $block->processBlock($child, $args);
        }
        else{
            // If method is not defined in given class
            throw new MissingMainRunException("Class not found: " . $class_name . " or method not found: " . $method_name);
        }
    }

    // Defines built-in classes with their definitions
    private function defineBuiltinClasses(): void
    {
        $built_in_builder = new BuiltinMethodBuilder();

        $class = new ClassDefinition("Object");
        $built_in_builder->buildObjectMethods($class);

        $class = new ClassDefinition("Nil", "Object");
        $built_in_builder->buildNilMethods($class);

        $class = new ClassDefinition("Integer", "Object");
        $built_in_builder->buildIntegerMethods($class);

        $class = new ClassDefinition("String", "Object");
        $built_in_builder->buildStringMethods($class);

        $class = new ClassDefinition("Block", "Object");
        $built_in_builder->buildBlockMethods($class);

        $class = new ClassDefinition("True", "Object");
        $built_in_builder->buildTrueMethods($class);

        $class = new ClassDefinition("False", "Object");
        $built_in_builder->buildFalseMethods($class);
    }
}
