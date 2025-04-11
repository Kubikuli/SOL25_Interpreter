<?php

namespace IPP\Student;


// Each instance represents user defined class
class ClassDefinition
{
    /**
     * @var array<string, self> List of all it's instances (= all class definitions associated by name)
     */
    static public array $instances = [];
    protected string $parent;      // parent class name
    /**
     * @var array<string, \DOMElement|callable> List of instance methods specific for the given class
     */
    protected array $methods = [];

    // Constructor, appends the new instance to the list of instances
    public function __construct(string $class_name, string $parent_name = "")
    {
        // Append reference of the instance to the $instances array
        self::$instances[$class_name] = $this;
        $this->parent = $parent_name;
    }

    // Returns the parent class name
    public function getParentName(): string
    {
        return $this->parent;
    }

    // Searches for the class in the list of instances
    // Returns the definition instance of the class if found, otherwise null
    static public function getClass(string $class_name): ?ClassDefinition
    {
        // Search for the class in the list of instances
        if (isset(self::$instances[$class_name])) {
            return self::$instances[$class_name];
        }

        // If not found, return null
        return null;
    }

    // Returns root DOMElement of the method or null if method doesnt exist in given class
    static public function getMethod(string $class_name, string $method_name): \DOMElement|callable|null
    {
        // Check if the class exists
        $instance = self::getClass($class_name);
        if ($instance === null) {
            return null;
            // Maybe throw an exception here, unknown class name? but shouldnt happen
        }

        // Search for the method
        if (isset($instance->methods[$method_name])) {
            return $instance->methods[$method_name];
        }

        // If not found and we have a parent, look there
        if ($instance->parent !== "" and $class_name !== "Object") {
            return self::getMethod($instance->parent, $method_name);
        }

        // If not found, return null
        return null;
    }

    // Adds a new method to the class
    public function addMethod(string $name, \DOMElement $method): void
    {
        $this->methods[$name] = $method;
    }

    // Adds a new built-in method to the class
    public function addBuiltinMethod(string $name, callable $implementation): void
    {
        $this->methods[$name] = $implementation;
    }

    public static function isInstanceOf(string $class_name, string $possible_parent): bool
    {
        // Check if the class exists
        $instance = self::getClass($class_name);
        if ($instance === null) {
            return false;
        }

        // Check if the class is the same as the possible parent
        if ($class_name === $possible_parent) {
            return true;
        }

        // Check if the class has a parent and check there
        if ($instance->parent !== "" and $class_name !== "Object") {
            return self::isInstanceOf($instance->parent, $possible_parent);
        }

        // If not found, return false
        return false;
    }
}
