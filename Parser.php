<?php

namespace IPP\Student;


class Parser
{
    // Constructor
    public function __construct(){}

    // Parses given DOMDocument and creates instances of 'User_defined_cls'
    public function parse(\DOMDocument $dom): void
    {
        if ($dom->documentElement !== null) {
            $this->parse_classes($dom->documentElement);
        }
    }

    // Creates an instance for each user-defined class and sets all attributes
    private function parse_classes(\DOMElement $program_node): void {
        foreach ($program_node->getElementsByTagName("class") as $class_node) {
            $class_name = $class_node->getAttribute("name");
            $parent_name = $class_node->getAttribute("parent");

            $class = new Class_definition($class_name, $parent_name);

            // Extract methods
            $this->parse_methods($class_node, $class);
        }
    }

    // Gets all methods of the class and their parameters with correct order
    private function parse_methods(\DOMElement $class_node, Class_definition $class): void {
        foreach ($class_node->getElementsByTagName("method") as $method_node) {
            $method_name = $method_node->getAttribute("selector");
            $parameters = [];
    
            // Extract parameters if present
            foreach ($method_node->getElementsByTagName("parameter") as $param_node) {
                $parameters[(int)$param_node->getAttribute("order")-1] = $param_node->getAttribute("name");
            }
    
            // Store method with its instructions
            $class->add_method($method_name, $method_node);
        }
    }
}
