<?php

namespace IPP\Student;

use IPP\Core\AbstractInterpreter;
use IPP\Core\Interface\InputReader;
use IPP\Core\Interface\OutputWriter;
use IPP\Core\ReturnCode;

use IPP\Student\Exception\MessageDNUException;
use IPP\Student\Exception\UnexpectedXMLFormatException;
use IPP\Student\Exception\UsingUndefinedException;

// Super implementation - Cant use __SUPER__ as string value
    // $val = $this->input->readString();
    // $this->stdout->writeString("stdout");
    // $this->stderr->writeString("stderr");


class Interpreter extends AbstractInterpreter
{
    static public Interpreter $instance;

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
        $this->executeRunMethod();

        return ReturnCode::OK;
    }

    private function executeRunMethod(): void
    {
        try {
            // Check if 'Main' and 'run' are defined
            $method = ClassDefinition::getMethod("Main", "run");
        }
        catch (UsingUndefinedException) {
            throw new UnexpectedXMLFormatException("Main class not found.");
        }

        // Main class instance
        $main_class = new ClassInstance("Main");
        $block = new BlockScope();

        // In this scope, self references to Main class
        $block->setVariable("self", $main_class);

        // Check if 'run' method is defined
        if ($method instanceof \DOMElement) {
            // Skip the method node and send block node as argument
            $block_node = $method->getElementsByTagName("block")->item(0);
            $block->processBlock($block_node, []);
        }
        else {
            // 'run' method is not defined
            throw new MessageDNUException("Run method not found.");
        }
    }

    // Defines built-in classes with their built-in methods
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
