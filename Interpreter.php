<?php

namespace IPP\Student;

use IPP\Core\AbstractInterpreter;
use IPP\Core\Interface\InputReader;
use IPP\Core\Interface\OutputWriter;
use IPP\Core\ReturnCode;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\CSS\MissingColonSniff;

use IPP\Student\Exception\MissingMainRunException;

// Super implementation - Cant use __SUPER__ as string value
// TODO: other stuff shouuld understand value: messages other then Block class maybe

class Interpreter extends AbstractInterpreter
{
    static public Interpreter $instance;

    // Had to do this unfortunatelly this way, because I didn't want to change my whole 
    // design because I couldn't access this and I found out too late I needed it

    // Add a method to access the input reader
    public static function get_input_reader(): ?InputReader
    {
        if (isset(self::$instance)) {
            return self::$instance->input;
        }
        return null;
    }

    // Add a method to access the input reader
    public static function get_stdout_writer(): ?OutputWriter
    {
        if (isset(self::$instance)) {
            return self::$instance->stdout;
        }
        return null;
    }

    // TODO: maybe remove this
    // Add a method to access the input reader
    public static function get_stderr_writer(): ?OutputWriter
    {
        if (isset(self::$instance)) {
            return self::$instance->stderr;
        }
        return null;
    }
    

    public function execute(): int
    {
        // $val = $this->input->readString();
        // $this->stdout->writeString("stdout");
        // $this->stderr->writeString("stderr");

        self::$instance = $this;

        $dom = $this->source->getDOMDocument();

        // Parse program structure
        $parser = new Parser();
        $parser->parse($dom);

        // Insert built-in class definitions
        $this->define_builtin_classes();

        // Execute "run" method from "Main"
        $this->execute_method("Main", "run", []);

        return ReturnCode::OK;
    }

    private function execute_method(string $class_name, string $method_name, array $args): void
    {
        // Check if given method in given class exists
        $method = Class_definition::get_method($class_name, $method_name);
        if ($method === null){
            throw new MissingMainRunException("Class not found: " . $class_name . " or method not found: " . $method_name);
        }

        // Main class instance
        $main_class = new Class_instance($class_name);
        $block = new Method_block();

        $block->set_variable("self", $main_class);

        $child = $method->getElementsByTagName("block")->item(0);

        // Skip the method node and send block node as argument
        $block->process_block($child, $args);
    }

    // Defines built-in classes with their definitions
    private function define_builtin_classes(): void
    {
        $built_in_builder = new Method_builder();

        $class = new Class_definition("Object");
        $built_in_builder->build_object_methods($class);

        $class = new Class_definition("Nil", "Object");
        $built_in_builder->build_nil_methods($class);

        $class = new Class_definition("Integer", "Object");
        $built_in_builder->build_integer_methods($class);

        $class = new Class_definition("String", "Object");
        $built_in_builder->build_string_methods($class);

        $class = new Class_definition("Block", "Object");
        $built_in_builder->build_block_methods($class);

        $class = new Class_definition("True", "Object");
        $built_in_builder->build_true_methods($class);

        $class = new Class_definition("False", "Object");
        $built_in_builder->build_false_methods($class);
    }
}
