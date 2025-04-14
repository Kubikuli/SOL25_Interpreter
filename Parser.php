<?php

/**
 * VUT FIT - IPP
 * @author Jakub Lůčný (xlucnyj00)
 * @date 2025-04-14
 * @project IPP project 2 - interpreter for SOL25 language
 * @brief Parser class definition
 */

namespace IPP\Student;

/**
 * Parser class for extracting class definitions from XML
 */
class Parser
{
    /**
     * Main Parser entry point
     * Parses given DOMDocument and creates instances of 'ClassDefinition' for each user-defined class
     */
    public function parse(\DOMDocument $dom): void
    {
        if ($dom->documentElement !== null) {
            $this->parseClasses($dom->documentElement);
        }
    }

    /**
     * Creates an instance of ClassDefinition for each user-defined class
     */
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

    /**
     * Gets all methods with their definitions of the given class node
     */
    private function parseMethods(\DOMElement $class_node, ClassDefinition $class): void
    {
        foreach ($class_node->getElementsByTagName("method") as $method_node) {
            $method_name = $method_node->getAttribute("selector");
    
            // Store method with its definition
            $class->addMethod($method_name, $method_node);
        }
    }
}
