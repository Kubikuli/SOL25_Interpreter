<?php

namespace IPP\Student;


class Parser
{
    // Parses given DOMDocument and creates instances of 'ClassDefinition' for each user-defined class
    public function parse(\DOMDocument $dom): void
    {
        if ($dom->documentElement !== null) {
            $this->parseClasses($dom->documentElement);
        }
    }

    // Creates an instance for each user-defined class and sets all attributes
    private function parseClasses(\DOMElement $program_node): void
    {
        foreach ($program_node->getElementsByTagName("class") as $class_node) {
            $class_name = $class_node->getAttribute("name");
            $parent_name = $class_node->getAttribute("parent");

            $class = new ClassDefinition($class_name, $parent_name);

            // Extract methods
            $this->parseMethods($class_node, $class);
        }
    }

    // Gets all methods of the class and their parameters with correct order
    private function parseMethods(\DOMElement $class_node, ClassDefinition $class): void
    {
        foreach ($class_node->getElementsByTagName("method") as $method_node) {
            $method_name = $method_node->getAttribute("selector");
            $parameters = [];
    
            // Extract parameters if present
            foreach ($method_node->getElementsByTagName("parameter") as $param_node) {
                $parameters[(int)$param_node->getAttribute("order")-1] = $param_node->getAttribute("name");
            }
    
            // Store method with its definition
            $class->addMethod($method_name, $method_node);
        }
    }
}
