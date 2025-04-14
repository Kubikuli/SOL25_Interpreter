<?php

/**
 * VUT FIT - IPP
 * @author Jakub Lůčný (xlucnyj00)
 * @date 2025-04-14
 * @project IPP project 2 - interpreter for SOL25 language
 * @brief ClassDefinition class definition
 */

namespace IPP\Student;

use IPP\Student\Exception\UsingUndefinedException;

/**
 * Each ClassDefinition instance represents a defined class
 */
class ClassDefinition
{
    /**
     * @var array<string, self> List of all it's instances (== all class definitions, associated by name)
     */
    static public array $instances = [];

    // parent class name
    protected string $parent;

    /**
     * @var array<string, \DOMElement|callable> List of instance methods specific for the given class, associated by name
     */
    protected array $methods = [];

    /**
     * Constructor appends the newly created instance to the list of instances
     */
    public function __construct(string $class_name, string $parent_name = "")
    {
        self::$instances[$class_name] = $this;
        $this->parent = $parent_name;
    }

    /**
     * Returns parent name of the class
     *
     * @return string Name of the parent class
     */
    public function getParentName(): string
    {
        return $this->parent;
    }

    /**
     * Adds a new method definition to the class
     * 
     * @param string $name Name of the method
     * @param \DOMElement $method Starting XML node of the method
     */
    public function addMethod(string $name, \DOMElement $method): void
    {
        $this->methods[$name] = $method;
    }

    /**
     * Adds a new method definition to the class (same as addMethod, but for built-in classes)
     * 
     * @param string $name Name of the method
     * @param callable $implementation Implementation of the method
     */

    public function addBuiltinMethod(string $name, callable $implementation): void
    {
        $this->methods[$name] = $implementation;
    }

    /**
     * Searches for the given class in the list of instances
     * 
     * @param string $class_name Name of the class to be searched
     * @return ClassDefinition Instance of the class definition
     */
    static public function getClass(string $class_name): ClassDefinition
    {
        // Search for the class in the list of instances
        if (!isset(self::$instances[$class_name])) {
            throw new UsingUndefinedException("Undefined class: " . $class_name);
        }

        return self::$instances[$class_name];
    }

    /**
     * Searches for the given method in the given class and its parent classes
     * 
     * @param string $class_name Name of the class to be searched
     * @param string $method_name Name of the method to be searched
     * @return \DOMElement|callable|null Definition of given method or null if method isn't defined for given class
     */
    static public function getMethod(string $class_name, string $method_name): \DOMElement|callable|null
    {
        // Check if the class exists
        $instance = self::getClass($class_name);

        // Search for the method
        if (isset($instance->methods[$method_name])) {
            return $instance->methods[$method_name];
        }

        // If not found and current class has a parent, recursively look there
        if ($instance->parent !== "" and $class_name !== "Object") {
            return self::getMethod($instance->parent, $method_name);
        }

        // No method found
        return null;
    }

    /**
     * Checks if the given class is an instance of the given parent class
     * 
     * @param string $class_name Name of the class to be checked
     * @param string $possible_parent Name of the parent class to be checked
     * @return bool True if the class is an instance of the parent class, false otherwise
     */
    public static function isInstanceOf(string $class_name, string $possible_parent): bool
    {
        // Get class definition instance
        $instance = self::getClass($class_name);

        // Check if the class is the same as the possible parent
        if ($class_name === $possible_parent) {
            return true;
        }

        // Check if the class has a parent and check there
        if ($instance->parent !== "" and $class_name !== "Object") {
            return self::isInstanceOf($instance->parent, $possible_parent);
        }

        // Nothing found
        return false;
    }
}
