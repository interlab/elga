<?php

namespace Interlab\NestedSets;

// use Interlab\NestedSets\Manager;

class Node
{
    public $id;
    public $left;
    public $right;
    public $level;

    private $_data;
    private $manager;

    // ??? Manager $manager
    public function __construct(array $row)
    {
        // $this->manager = $manager;

        $this->id = $row['id'];
        $this->left = $row['left']; 
        $this->right = $row['right'];
        $this->level = $row['level'];
        $this->_data = $row['_data'];
    }

    public function __toString()
    {
        return json_encode([
            'id' => $this->id,
            'left' => $this->left,
            'right' => $this->right,
            'level' => $this->level,
            '_data' => $this->_data
        ]);
    }

    public function __get($name)
    {
        return isset($this->_data[$name]) ?  $this->_data[$name] : '';
    }

    /**
     * Tests if object has an ancestor
     *
     * @return boolean
     */
    public function hasParent()
    {
        return $this->level > 0;
    }

    /**
     * Tests if node has children
     *
     * @return     bool
     */
    public function hasChildren()
    {
        return ($this->right - $this->left) > 1;
    }

    /**
     * Возвращает количество всех дочерних узлов
     * @return integer
     * @throws Exception
     */
    public function getCountChildren()
    {
        if ($this->isLeaf()) {
            return 0;
        }

        return ($this->right - $this->left - 1) / 2;
    }

    /**
     * Tests if node is a leaf
     *
     * @return     bool
     */
    public function isLeaf()
    {
        return ($this->right - $this->left) === 1;
    }
}
