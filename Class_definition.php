<?php

namespace IPP\Student;


// Each instance represents user defined class
class Class_definition
{
    static public array $instances = [];    // list of all it's instances (= all class definitions)
    protected string $parent;      // parent class name
    protected array $methods = [];      // list of instance methods in the class

    // Constructor, appends the new instance to the list of instances
    public function __construct(string $class_name, string $parent_name = "")
    {
        // Append reference of the instance to the $instances array
        self::$instances[$class_name] = $this;
        $this->parent = $parent_name;
    }

    // Sets new parent class name
    public function set_parent(string $parent_name): void
    {
        $this->parent = $parent_name;
    }

    // Returns the parent class name
    public function get_parent_name(): string
    {
        return $this->parent;
    }

    // Searches for the class in the list of instances
    // Returns the definition instance of the class if found, otherwise null
    static public function get_class(string $class_name): ?Class_definition
    {
        // Search for the class in the list of instances
        if (isset(self::$instances[$class_name])) {
            return self::$instances[$class_name];
        }

        // If not found, return null
        return null;
    }

    // Returns root DOMElement of the method or null if method doesnt exist in given class
    static public function get_method(string $class_name, string $method_name): \DOMElement|callable|null
    {
        // Check if the class exists
        $instance = self::get_class($class_name);
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
            return self::get_method($instance->parent, $method_name);
        }

        // If not found, return null
        return null;
    }

    // Adds a new method to the class
    public function add_method(string $name, \DOMElement $method): void
    {
        $this->methods[$name] = $method;
    }

    // Adds a new built-in method to the class
    public function add_builtIn_method(string $name, callable $implementation): void
    {
        $this->methods[$name] = $implementation;
    }

    public static function is_instance_of(string $class_name, string $possible_parent): bool
    {
        // Check if the class exists
        $instance = self::get_class($class_name);
        if ($instance === null) {
            return false;
        }

        // Check if the class is the same as the possible parent
        if ($class_name === $possible_parent) {
            return true;
        }

        // Check if the class has a parent and check there
        if ($instance->parent !== "" and $class_name !== "Object") {
            return self::is_instance_of($instance->parent, $possible_parent);
        }

        // If not found, return false
        return false;
    }
}
