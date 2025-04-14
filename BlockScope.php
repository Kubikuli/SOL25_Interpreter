<?php

namespace IPP\Student;

use IPP\Student\Exception\InterpretException;
use IPP\Student\Exception\MessageDNUException;
use IPP\Student\Exception\UnexpectedXMLFormatException;
use IPP\Student\Exception\UsingUndefinedException;

class BlockScope
{
    /**
     * @var array<string, ClassInstance> List of variables of current block
     */
    protected array $variables = [];
    protected ClassInstance $return_value;    // return value of the method

    // Constructor
    public function __construct()
    {
        $this->return_value = new ClassInstance("Nil");
    }

    public function setReturnValue(ClassInstance $value): void
    {
        $this->return_value = $value;
    }

    public function getReturnValue(): ClassInstance
    {
        return $this->return_value;
    }

    public function setVariable(string $name, ClassInstance $value): void
    {
        $this->variables[$name] = $value;
    }

    public function getVariable(string $name): ClassInstance
    {
        if (!isset($this->variables[$name])) {
            throw new UsingUndefinedException("Using undefined variable: " . $name);
        }
        return $this->variables[$name];
    }

    // ********************** BLOCK PROCESSING ***********************
    /**
     * @param array<ClassInstance> $args Arguments given to the block
     */
    public function processBlock(?\DOMElement $block_node, array $args): void
    {
        if ($block_node === null) {
            throw new UnexpectedXMLFormatException("Malformed XML format.");
        }

        $num_of_args = count($args);
        $args_found = 0;

        // ********** PARAMETER PROCESSING ***********
        // Assign argument values to parameters
        foreach ($block_node->childNodes as $child_node) {
            if ($child_node instanceof \DOMElement) {
                // Check if it's a parameter
                if ($child_node->tagName === "parameter") {
                    // Assign it to the block attribute as variable with correct value
                    $order = (int)$child_node->getAttribute("order");
                    $par_name = $child_node->getAttribute("name");

                    if ($order > $num_of_args) {
                        throw new MessageDNUException("Block doen't understand message with this number of parameters.");
                    }

                    $this->setVariable($par_name, $args[$order-1]);
                    $args_found++;
                }
            }
        }

        if ($args_found !== $num_of_args) {
            throw new MessageDNUException("Block doesn't understand message with this number of parameters.");
        }

        $this->processAssignments($block_node);
    }

    // ************************* ASSIGN PROCESSING **************************
    private function processAssignments(\DOMElement $block_node): void
    {
        $order = 0;
        $new_order = 1;
        // Makes sure the assignments are processed in the correct order
        while($order !== $new_order) {
            $order = $new_order;
            // For each child node
            foreach ($block_node->childNodes as $child_node) {
                if ($child_node instanceof \DOMElement) {
                    // Check if it's an assignment
                    if ($child_node->tagName === "assign") {
                        // Make sure the order is correct
                        $assign_order = (int)$child_node->getAttribute("order");
                        if ($assign_order === $order) {
                            $new_order++;

                            // ************* ASSIGNMENT PROCESSING **************
                            $result = null;
                            $var_name = null;

                            foreach ($child_node->childNodes as $grand_child_node) {
                                if ($grand_child_node instanceof \DOMElement) {
                                    
                                    // Get variable name
                                    if ($grand_child_node->tagName === "var") {
                                        $var_name = $grand_child_node->getAttribute("name");

                                        if ($var_name === "") {
                                            throw new UnexpectedXMLFormatException("Malformed XML. Missing name attribute.");
                                        }
                                    }
                                    // Get value to be assigned
                                    else if($grand_child_node->tagName === "expr") {
                                        $evaluator = new ExpressionEvaluator($this);
                                        $result = $evaluator->evaluateExpr($grand_child_node);
                                    }
                                }
                            }

                            if (is_string($result) || $result === null || $var_name === null) {
                                // This should never reach, only with malformed XML
                                throw new InterpretException("Malformed XML. If you got this error msg, something is REALLY wrong, sorry.");
                            }

                            // Assign value to the variable
                            $this->setVariable($var_name, $result);
                            $this->setReturnValue($result);
                        }
                    }
                }
            }
        }
    }
}   // class BlockScope
