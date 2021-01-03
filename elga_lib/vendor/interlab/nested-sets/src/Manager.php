<?php

/**
 *
 * @author: inter <pmrtorrents@gmail.com>
 *
 * rgt < last rgt => increment the hierarchy level (move one level down)
 * lft - last lft > 2 => decrement the hierarchy level (move one level up)
 * (rgt - lft - 1) / 2 = number of children nodes
 *
 * Rules:
 *      1. Левый ключ ВСЕГДА меньше правого
 *      2. Наименьший левый ключ ВСЕГДА равен 1
 *      3. Наибольший правый ключ ВСЕГДА равен двойному числу узлов
 *      4. Разница между правым и левым ключом ВСЕГДА нечетное число
 *      5. Если уровень узла нечетное число то тогда левый ключ ВСЕГДА нечетное число, то же самое и для четных чисел
 *      6. Ключи ВСЕГДА уникальны, вне зависимости от того правый он или левый
 *
 */

namespace Interlab\NestedSets;

use \PDO;
use \Exception;
use \Interlab\NestedSets\Node;

class Manager
{
    protected $db_table = 'categories';
    protected $id_column = 'id';
    protected $left_column = 'lft';
    protected $right_column = 'rgt';
    protected $level_column = 'depth';

    private $db;

    # public function __construct(){}

    /**
     * DB connect
     * @throws Exception
     */
    public function setDb(
        $db_type = 'mysql',
        $db_host = '127.0.0.1',
        $db_port = 3306,
        $db_name = 'categories',
        $db_user = 'root',
        $db_password = '',
        $charset = 'UTF8'
    ) {
        try {
            if ('mysql' === $db_type) {
                if (is_null($db_host)) {
                    $db_host = '127.0.0.1';
                }
                $this->db = new PDO($db_type . ':host=' . $db_host . ';dbname=' . $db_name, $db_user, $db_password,
                    array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $charset)
                );
            }
            elseif ('sqlite' === $db_type) {
                $this->db = new PDO($db_type . ':' . $db_host);
            }
            elseif ('pgsql' === $db_type) {
                $this->db = new PDO($db_type . ':host=' . $db_host . ';port=' . $db_port . ';dbname=' . $db_name, $db_user, $db_password);
            } else {
                throw new Exception('Your db type not support in setDb() method. Use setDbAdapter() method.');
            }
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            # $this->db->setAttribute(PDO::ATTR_AUTOCOMMIT, false);
        } catch (PDOException $e) {
            die('Houston, we have a problem.');
        }
    }

    public function setDbAdapter(PDO $dbAdapter)
    {
        $this->db = $dbAdapter;
    }

    /**
     * @return PDO object
     */
    public function getDbAdapter()
    {
        return $this->db;
    }

    /**
     * Проверка: узел в дереве?
     * @return boolean
     * @throws Exception
     */
    public function inTree($node, $parent)
    {
        $node = $this->normalizeNode($node);
        $parent = $this->normalizeNode($parent);

        return $node->left > $parent->left && $node->right < $parent->right;
    }

    /**
     * Проверка: узел $n1 родитель по отношению к узлу $n2?
     * @return boolean
     * @throws Exception
     */
    public function isParent($n1, $n2)
    {
        $node = $this->normalizeNode($n1);
        $node2 = $this->normalizeNode($n2);

        return $node->left < $node2->left &&
            $node->right > $node2->right;
    }

    /**
     * Tests if node is a leaf
     *
     * @return     bool
     */
    public function isLeaf($n)
    {
        $node = $this->normalizeNode($n);

        return $node->isLeaf();
    }

    /**
     * @return boolean
     * @throws Exception
     */
    public function issetNode($id)
    {
        $node = $this->getNode($id);

        return (null !== $node);
    }

    /**
     * Возвращает узел по ID
     * @return Node|null
     * @throws Exception
     */
    public function getNode($id)
    {
        if ( ! is_numeric($id) ) {
            throw new Exception('ID NODE Only INTEGER TYPE!');
        }

        $id = (int) $id;
        $node = $this->getTree('node.' . $this->id_column . ' = ' . $id);

        return ( isset($node[$id]) ? $node[$id] : null );
    }

    /**
     * Return more nodes
     * @return array<int:Node>|null
     * @throws Exception
     */
    public function getTree($where = '', $having = '')
    {
        $sql = '
            SELECT node.*, (COUNT(parent.' . $this->id_column . ') - 1) AS ' . $this->level_column . '
            FROM ' . $this->db_table . ' AS node, ' . $this->db_table . ' AS parent
            WHERE node.' . $this->left_column . ' BETWEEN parent.' . $this->left_column . ' AND parent.' .
            $this->right_column . (empty($where) ? '' : '
                AND ' . $where) . '
            GROUP BY node.' . $this->id_column . (empty($having) ? '' : '
            HAVING ' . $having) . '
            ORDER BY node.' . $this->left_column;

        $tree = array();
        try {
            $q = $this->db->query($sql);
            while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
                $tree[$row[$this->id_column]] = new Node([
                    'id' => $row[$this->id_column],
                    'left' => $row[$this->left_column],
                    'right' => $row[$this->right_column],
                    'level' => $row[$this->level_column],
                    '_data' => array_diff_key($row, [
                        $this->id_column => 1, $this->left_column => 1, $this->right_column => 1, $this->level_column => 1,
                    ]),
                ]);
            }
            $q->closeCursor();
        } catch (Exception $e) {
            throw $e;
        }

        return (empty($tree) ? null : $tree);
    }

    /**
     * Gets descendants for the given node, plus the current node
     *
     * @return array<int:Node>|null
     * @throws Exception
     */
    public function getBranch($n)
    {
        $node = $this->normalizeNode($n);
        $where = 'node.' . $this->left_column . ' <= ' . $node->right . '
                AND node.' . $this->right_column . ' >= ' . $node->left;
        /*
        // or
        $where = 'node.' . $this->left_column . ' >= ' . $node->left . '
                AND node.' . $this->left_column . ' <= ' . $node->right;
        */
        return $this->getTree($where);
    }

    /**
     * Tests if object has an ancestor
     *
     * @return boolean
     */
    public function hasParent($n)
    {
        $node = $this->normalizeNode($n);

        return $node->hasParent();
    }

    /**
     * Возвращает родителя
     * @return Node|null
     * @throws Exception
     */
    public function getParent($n)
    {
        $node = $this->normalizeNode($n);

        if ( ! $node->level ) {
            return null;
        }

        $where = 'node.' . $this->left_column . ' < ' . $node->left . '
                AND node.' . $this->right_column . ' > ' . $node->right;
        $having = $this->level_column . ' = ' . ($node->level - 1);

        $arr = $this->getTree($where, $having);

        return (empty($arr) ? null : array_shift($arr));
    }

   /**
     * Gets the parents of the node
     * @param integer|null $depth the depth
     * @return array<int:Node>|null
     */
    public function getParents($n, $depth = null)
    {
        $node = $this->normalizeNode($n);

        if ( ! $node->level ) {
            return null;
        }

        $where = 'node.' . $this->left_column . ' < ' . $node->left . '
                AND node.' . $this->right_column . ' > ' . $node->right;

        if ($depth !== null) {
            $having = $this->level_column . ' >= ' . ($node->level - $depth);
        }

        return $this->getTree($where, $having);
    }

    /**
     * Возвращает предыдущий узел по отношению к $node
     * @return Node|null
     */
    public function getPrevSibling($node)
    {
        $node = $this->normalizeNode($node);
        $where = 'node.' . $this->right_column . ' = ' . $node->left . ' - 1';

        $arr = $this->getTree($where);

        return (empty($arr) ? null : array_shift($arr));
    }

    /**
     * Возвращает следущий узел по отношению к $node
     * @return Node|null
     */
    public function getNextSibling($n)
    {
        $node = $this->normalizeNode($n);
        $where = 'node.' . $this->left_column . ' = ' . $node->right . ' + 1';

        $arr = $this->getTree($where);
        return (empty($arr) ? null : array_shift($arr));
    }

    /**
     * Tests if node has children
     *
     * @return     bool
     */
    public function hasChildren($n)
    {
        $node = $this->normalizeNode($n);

        return $node->hasChildren();
    }

    /**
     * Возвращает детей БЕЗ родителя
     * @return array<int:Node>|null
     * @throws Exception
     */
    public function getChildren($n)
    {
        $node = $this->normalizeNode($n);

        $where = 'node.' . $this->left_column . ' > ' . $node->left . '
                AND node.' . $this->right_column . ' < ' . $node->right;

        return $this->getTree($where);
    }

    /**
     * Возвращает количество всех дочерних узлов
     * @return integer
     * @throws Exception
     */
    public function getCountChildren($n)
    {
        $node = $this->normalizeNode($n);

        return $node->getCountChildren();
    }

    /**
     * @return Node|null
     */
    public function getFirstChild($n)
    {
        $node = $this->normalizeNode($n);
        if ($node->isLeaf()) {
            return null;
        }

        $where = 'node.' . $this->left_column . ' = ' . $node->left . ' + 1';
        $arr = $this->getTree($where);

        return (empty($arr) ? null : array_shift($arr));
    }

    /**
     * @return Node|null
     */
    public function getLastChild($n)
    {
        $node = $this->normalizeNode($n);
        if ($node->isLeaf()) {
            return null;
        }

        $where = 'node.' . $this->right_column . ' = ' . $node->right . ' - 1';
        $arr = $this->getTree($where);

        return (empty($arr) ? null : array_shift($arr));
    }

    /**
     * Создать первый узел или корневой за последним корневым узлом
     * @return boolean
     * @throws Exception
     */
    public function createRoot(array $params)
    {
        $tree = $this->getTree();
        if (!empty($tree)) {
            # Get Last Parent Info
            $last_parent = $this->getLastParent();
            $this->insertAsNextSiblingOf($last_parent, $params);

            return true;
        }

        try {
            $this->db->beginTransaction();

            $params = $this->_safeParams($params);

            $params[$this->left_column] = 1;
            $params[$this->right_column] = 2;

            $sql = '
            INSERT INTO ' . $this->db_table . ' (' . implode(', ', array_keys($params)) . ')
            VALUES (' . implode(', ', array_fill(0, count($params), '?')) . ')';

            $q = $this->db->prepare($sql);
            $q->execute(array_values($params));

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * @return Node|null
     * @throws Exception
     */
    public function getLastParent()
    {
        $sql = '
            SELECT node.*, (COUNT(parent.' . $this->id_column . ') - 1) AS ' . $this->level_column . '
            FROM ' . $this->db_table . ' AS node, ' . $this->db_table . ' AS parent
            WHERE node.' . $this->left_column . ' BETWEEN parent.' . $this->left_column . ' AND parent.' . $this->right_column . '
            GROUP BY node.' . $this->id_column . '
            HAVING ' . $this->level_column . ' = 0
            ORDER BY node.' . $this->left_column . ' DESC
            LIMIT 1';

        try {
            $q = $this->db->query($sql);
            $row = $q->fetch(PDO::FETCH_ASSOC);
            $node = null;
            if ($row) {
                $node = new Node([
                    'id' => $row[$this->id_column],
                    'left' => $row[$this->left_column],
                    'right' => $row[$this->right_column],
                    'level' => $row[$this->level_column],
                    '_data' => array_diff_key($row, [
                        $this->id_column => 1, $this->left_column => 1, $this->right_column => 1, $this->level_column => 1,
                    ]),
                ]);
            }
            $q->closeCursor();
        } catch (Exception $e) {
            throw $e;
        }

        return $node;
    }

    /**
     * Добавляем категорию ПЕРЕД выбранным узлом
     * @return bool
     * @throws Exception
     */
    public function insertAsPrevSiblingOf($n, array $params)
    {
        $node = $this->normalizeNode($n);
        $params = $this->_safeParams($params);

        try {
            $this->db->beginTransaction();

            // update left
            $sql = '
            UPDATE ' . $this->db_table . '
                SET ' . $this->left_column . ' = ' . $this->left_column . ' + 2
            WHERE ' . $this->left_column . ' >= ' . $node->left;

            $q = $this->db->prepare($sql);
            $q->execute();

            // update right
            $sql = '
            UPDATE ' . $this->db_table . '
                SET ' . $this->right_column . ' = ' . $this->right_column . ' + 2
            WHERE ' . $this->left_column . ' >= ' . $node->left . '
                OR ' . $this->right_column . ' >= ' . $node->right;

            $q = $this->db->prepare($sql);
            $q->execute();

            $params[$this->left_column] = $node->left;
            $params[$this->right_column] = $node->left + 1;

            $sql = '
            INSERT INTO ' . $this->db_table . ' (' . implode(', ', array_keys($params)) . ')
            VALUES (' . implode(', ', array_fill(0, count($params), '?')) . ')';

            $q = $this->db->prepare($sql);
            $q->execute(array_values($params));

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Добавляем категорию ПОСЛЕ выбранного узла
     * @return boolean
     * @throws Exception
     */
    public function insertAsNextSiblingOf($n, array $params)
    {
        $node = $this->normalizeNode($n);

        $params = $this->_safeParams($params);

        try {
            $this->db->beginTransaction();

            // update left
            $sql = '
            UPDATE ' . $this->db_table . '
                SET ' . $this->left_column . ' = ' . $this->left_column . ' + 2
            WHERE ' . $this->left_column . ' > ' . $node->right;

            $q = $this->db->prepare($sql);
            $q->execute();

            // update right
            $sql = '
            UPDATE ' . $this->db_table . '
                SET ' . $this->right_column . ' = ' . $this->right_column . ' + 2
            WHERE ' . $this->right_column . ' > ' . $node->right;

            $q = $this->db->prepare($sql);
            $q->execute();

            $params[$this->left_column] = $node->right + 1;
            $params[$this->right_column] = $node->right + 2;

            $sql = '
            INSERT INTO ' . $this->db_table . ' (' . implode(', ', array_keys($params)) . ')
            VALUES (' . implode(', ', array_fill(0, count($params), '?')) . ')';

            $q = $this->db->prepare($sql);
            $q->execute(array_values($params));

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Добавляем дочернюю категорию в конец
     * @return type
     */
    public function addChild($node, array $params)
    {
        return $this->insertAsLastChildOf($node, $params);
    }

    /**
     * Добавляем дочернюю категорию в начало
     * @return boolean
     * @throws Exception
     */
    public function insertAsFirstChildOf($n, array $params)
    {
        $node = $this->normalizeNode($n);

        try {
            $this->db->beginTransaction();

            // update left
            $sql = '
            UPDATE ' . $this->db_table . '
                SET ' . $this->left_column . ' = ' . $this->left_column . ' + 2
            WHERE ' . $this->left_column . ' > ' . $node->left;

            $q = $this->db->prepare($sql);
            $q->execute();

            // update right
            $sql = '
            UPDATE ' . $this->db_table . '
                SET ' . $this->right_column . ' = ' . $this->right_column . ' + 2
            WHERE ' . $this->left_column . ' > ' . $node->left . '
                OR ' . $this->right_column . ' >= ' . $node->right;

            $q = $this->db->prepare($sql);
            $q->execute();

            // create child
            $params[$this->left_column] = $node->left + 1;
            $params[$this->right_column] = $node->left + 2;

            $sql = '
            INSERT INTO ' . $this->db_table . ' (' . implode(', ', array_keys($params)) . ')
            VALUES (' . implode(', ', array_fill(0, count($params), '?')) . ')';

            $q = $this->db->prepare($sql);
            $q->execute(array_values($params));

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Добавляем дочернюю категорию в конец
     * @return boolean
     * @throws Exception
     */
    public function insertAsLastChildOf($node, array $params)
    {
        $node = $this->normalizeNode($node);

        try {
            $this->db->beginTransaction();

            // update left
            $sql = '
            UPDATE ' . $this->db_table . '
                SET ' . $this->left_column . ' = ' . $this->left_column . ' + 2
            WHERE ' . $this->left_column . ' > ' . $node->right;

            $q = $this->db->prepare($sql);
            $q->execute();

            // update right
            $sql = '
            UPDATE ' . $this->db_table . '
                SET ' . $this->right_column . ' = ' . $this->right_column . ' + 2
            WHERE ' . $this->right_column . ' >= ' . $node->right;

            $q = $this->db->prepare($sql);
            $q->execute();

            // create child
            $params[$this->left_column] = $node->right;
            $params[$this->right_column] = $node->right + 1;

            $sql = '
                INSERT INTO ' . $this->db_table . ' (' . implode(', ', array_keys($params)) . ')
                VALUES (' . implode(', ', array_fill(0, count($params), '?')) . ')';

            $q = $this->db->prepare($sql);
            $q->execute(array_values($params));

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Перемещает узел $node ПЕРЕД выбранным узлом $parent
     * @return boolean
     * @throws Exception
     */
    public function moveToPrevSiblingOf($node, $parent, array $params = array())
    {
        $node = $this->normalizeNode($node);
        $parent = $this->normalizeNode($parent);

        if ($node == $parent) {
            return false;
        }

        if ($this->isParent($node, $parent)) {
            //throw new Exception('Node Is Parent!');
            $this->moveToNextSiblingOf($parent, $node);
            $this->updateNode($node, $params);

            return true;
        }

        if ($parent->left - 1 == $node->right) {
            # throw new Exception('Node on Position!');
            $this->updateNode($node, $params);

            return true;
        }

        $params = $this->_safeParams($params);
        $count_nodes = $node->getCountChildren() + 1;

        try {
            $this->db->beginTransaction();

            # Расширить новое место
            $sql = '
            UPDATE ' . $this->db_table . '
                SET ' . $this->left_column . ' =
                        CASE WHEN ' . $this->left_column . ' >= ' . $parent->left . '
                            THEN ' . $this->left_column . ' + (2 * ' . $count_nodes . ')
                            ELSE ' . $this->left_column . '
                        END,
                    ' . $this->right_column . ' = ' . $this->right_column . ' + (2 * ' . $count_nodes . ')
            WHERE ' . $this->left_column . ' >= ' . $parent->left . '
                OR ' . $this->right_column . ' >= ' . $parent->right;

            $q = $this->db->prepare($sql);
            $q->execute();

            $node_left = $node->left;
            $node_right = $node->right;

            # В БД ещё нет изменений
            if ($this->inTree($node, $parent) || $parent->right < $node->left) {
                $node_left = $node->left + (2 * $count_nodes);
                $node_right = $node->right + (2 * $count_nodes);
            }

            $difference = $parent->left - $node_left;

            # Перенести на новое место
            $sql = '
            UPDATE ' . $this->db_table . '
                SET ' . ($params ? implode(' = ?, ', array_keys($params)) . ' = ?,' : '') . '
                    ' . $this->left_column . ' = ' . $this->left_column . ' + ' . $difference . ',
                    ' . $this->right_column . ' = ' . $this->right_column . ' + ' . $difference . '
                WHERE ' . $this->left_column . ' >= ' . $node_left . '
                    AND ' . $this->right_column . ' <= ' . $node_right;

            $q = $this->db->prepare($sql);
            $q->execute(array_values($params));

            $this->_clean($node);

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Перемещает узел $node ПОСЛЕ выбранного узла $parent
     * @return boolean
     * @throws Exception
     */
    public function moveToNextSiblingOf($node, $parent, array $params = array())
    {
        $node = $this->normalizeNode($node);
        $parent = $this->normalizeNode($parent);

        if ($node == $parent) {
            return false;
        }

        if ($this->isParent($node, $parent)) {
            //throw new Exception('Node Is Parent!');
            $this->moveToPrevSiblingOf($parent, $node);
            $this->updateNode($node, $params);

            return true;
        }

        # Уже на позиции, обновляем только параметры без ключей
        if ($parent->right + 1 == $node->left) {
            # throw new Exception('Node on Position!');
            $this->updateNode($node, $params);

            return true;
        }

        $params = $this->_safeParams($params);
        $count_nodes = $node->getCountChildren() + 1;

        try {
            $this->db->beginTransaction();

            # Расширить новое место
            $sql = '
            UPDATE ' . $this->db_table . '
                SET ' . $this->left_column . ' =
                        CASE WHEN ' . $this->left_column . ' > ' . $parent->right . '
                            THEN ' . $this->left_column . ' + (2 * ' . $count_nodes . ')
                            ELSE ' . $this->left_column . '
                        END,
                    ' . $this->right_column . ' = ' . $this->right_column . ' + (2 * ' . $count_nodes . ')
            WHERE ' . $this->right_column . ' > ' . $parent->right;

            $q = $this->db->prepare($sql);
            $q->execute();

            $node_left = $node->left;
            $node_right = $node->right;

            # В бд ещё нет изменений
            if ($parent->right < $node->right) {
                $node_left = $node->left + (2 * $count_nodes);
                $node_right = $node->right + (2 * $count_nodes);
            }

            $difference = $parent->right - $node_left + 1;

            # Перенести на новое место
            $sql = '
            UPDATE ' . $this->db_table . '
            SET ' . ($params ? implode(' = ?, ', array_keys($params)) . ' = ?,' : '') . '
                ' . $this->left_column . ' = ' . $this->left_column . ' + ' . $difference . ',
                ' . $this->right_column . ' = ' . $this->right_column . ' + ' . $difference . '
            WHERE ' . $this->left_column . ' >= ' . $node_left . '
                AND ' . $this->right_column . ' <= ' . $node_right;

            $q = $this->db->prepare($sql);
            $q->execute(array_values($params));

            $this->_clean($node);

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Перемещает узел $node в НАЧАЛО дочерних узлов выбранного узла $parent
     * @return boolean
     * @throws Exception
     */
    public function moveToFirstChildOf($node, $parent, array $params = array())
    {
        $node = $this->normalizeNode($node);
        $parent = $this->normalizeNode($parent);

        if ($node == $parent) {
            return false;
        }

        if ($this->isParent($node, $parent)) {
            # throw new Exception('Node Is Parent!');
            $this->moveToNextSiblingOf($node, $parent);
            $this->moveToFirstChildOf($node, $parent, $params);

            return true;
        }

        if ($parent->left + 1 == $node->left) {
            # throw new Exception('Node on Position!');
            $this->updateNode($node, $params);

            return true;
        }

        $params = $this->_safeParams($params);
        $count_nodes = $this->getCountChildren($node) + 1;

        try {
            $this->db->beginTransaction();

            # Расширить новое место
            $sql = '
            UPDATE ' . $this->db_table . '
                SET ' . $this->left_column . ' =
                        CASE WHEN ' . $this->left_column . ' > ' . $parent->left . '
                            THEN ' . $this->left_column . ' + (2 * ' . $count_nodes . ')
                            ELSE ' . $this->left_column . '
                        END,
                    ' . $this->right_column . ' = ' . $this->right_column . ' + (2 * ' . $count_nodes . ')
            WHERE ' . $this->left_column . ' > ' . $parent->left . '
                OR ' . $this->right_column . ' >= ' . $parent->right;

            $q = $this->db->prepare($sql);
            $q->execute();

            # В бд ещё нет изменений
            if ($parent->left < $node->left) {
                $node->left = $node->left + (2 * $count_nodes);
                $node->right = $node->right + (2 * $count_nodes);
            }

            $difference = $parent->left - $node->left + 1;

            # Перенести на новое место
            $sql = '
            UPDATE ' . $this->db_table . '
                SET ' . ($params ? implode(' = ?, ', array_keys($params)) . ' = ?,' : '') . '
                    ' . $this->left_column . ' = ' . $this->left_column . ' + ' . $difference . ',
                    ' . $this->right_column . ' = ' . $this->right_column . ' + ' . $difference . '
            WHERE ' . $this->left_column . ' >= ' . $node->left . '
                AND ' . $this->right_column . ' <= ' . $node->right;

            $q = $this->db->prepare($sql);
            $q->execute(array_values($params));

            $this->_clean($node);

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Перемещает узел $node в КОНЕЦ дочерних узлов выбранного узла $parent
     * @return boolean
     * @throws Exception
     */
    public function moveToLastChildOf($node, $parent, array $params = array())
    {
        $node = $this->normalizeNode($node);
        $parent = $this->normalizeNode($parent);

        if ($node == $parent) {
            return false;
        }

        if ($this->isParent($node, $parent)) {
            # throw new Exception('Node Is Parent!');
            $this->moveToNextSiblingOf($node, $parent);
            $this->moveToLastChildOf($node, $parent, $params);

            return true;
        }

        $params = $this->_safeParams($params);
        $count_nodes = $node->getCountChildren() + 1;

        try {
            $this->db->beginTransaction();

            $sql = '
            UPDATE ' . $this->db_table . '
                SET ' . $this->left_column . ' =
                        CASE WHEN ' . $this->left_column . ' > ' . $parent->right . '
                            THEN ' . $this->left_column . ' + (2 * ' . $count_nodes . ')
                            ELSE ' . $this->left_column . '
                        END,
                    ' . $this->right_column . ' = ' . $this->right_column . ' + (2 * ' . $count_nodes . ')
            WHERE ' . $this->right_column . ' >= ' . $parent->right;

            $q = $this->db->prepare($sql);
            $q->execute();

            $node_left = $node->left;
            $node_right = $node->right;

            # В бд ещё нет изменений
            if ($parent->right < $node->left) {
                $node_left = $node->left + (2 * $count_nodes);
                $node_right = $node->right + (2 * $count_nodes);
            }

            $difference = $parent->right - $node_left;

            # Перенести на новое место
            $sql = '
            UPDATE ' . $this->db_table . '
                SET ' . ($params ? implode(' = ?, ', array_keys($params)) . ' = ?,' : '') . '
                    ' . $this->left_column . ' = ' . $this->left_column . ' + ' . $difference . ',
                    ' . $this->right_column . ' = ' . $this->right_column . ' + ' . $difference . '
            WHERE ' . $this->left_column . ' >= ' . $node_left . '
                AND ' . $this->right_column . ' <= ' . $node_right;

            $q = $this->db->prepare($sql);
            $q->execute(array_values($params));

            $this->_clean($node);

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Простое обновление информации
     * Simple update information for node
     * @return boolean
     * @throws Exception
     */
    public function updateNode($n, array $params)
    {
        $node = $this->normalizeNode($n);
        $params = $this->_safeParams($params);
        if (empty($params)) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $sql = '
            UPDATE ' . $this->db_table . '
                SET ' . implode(' = ?, ', array_keys($params)) . ' = ?' . '
            WHERE ' . $this->left_column . ' = ' . $node->left . '
                AND ' . $this->right_column . ' = ' . $node->right;

            $q = $this->db->prepare($sql);
            $q->execute(array_values($params));

            $this->db->commit();

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Удаляет узел и его потомков(опционально)
     * @return boolean
     * @throws Exception
     */
    public function deleteNode($n, $full = false)
    {
        $node = $this->normalizeNode($n);

        // Delete only 1 node
        if ( ! $full ) {
            try {
                $this->db->beginTransaction();

                $sql = '
            DELETE FROM ' . $this->db_table . '
            WHERE ' . $this->id_column . ' = ' . $node->id;

                $q = $this->db->prepare($sql);
                $q->execute();

                // update childs
                $sql = '
            UPDATE ' . $this->db_table . '
                SET ' . $this->left_column . ' = ' . $this->left_column . ' - 1,
                      ' . $this->right_column . ' = ' . $this->right_column . ' - 1
            WHERE ' . $this->left_column . ' > ' . $node->left . '
                AND ' . $this->right_column . ' < ' . $node->right;

                $q = $this->db->prepare($sql);
                $q->execute();

                // update others
                // update left
                $sql = '
            UPDATE ' . $this->db_table . '
                SET ' . $this->left_column . ' = ' . $this->left_column . ' - 2
            WHERE ' . $this->left_column . ' > ' . $node->right;

                $q = $this->db->prepare($sql);
                $q->execute();

                // update right
                $sql = '
            UPDATE ' . $this->db_table . '
                SET ' . $this->right_column . ' = ' . $this->right_column . ' - 2
            WHERE ' . $this->right_column . ' > ' . $node->right;

                $q = $this->db->prepare($sql);
                $q->execute();

                $this->db->commit();

                return true;
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
        }
        // Delete FULL branch
        else {
            try {
                $this->db->beginTransaction();

                $sql = '
            DELETE FROM ' . $this->db_table . '
            WHERE ' . $this->left_column . ' >= ' . $node->left . '
                AND ' . $this->right_column . ' <= ' . $node->right;

                $q = $this->db->prepare($sql);
                $q->execute();

                $this->_clean($node);

                $this->db->commit();

                return true;
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
        }

        return true;
    }

    /**
     * @return array
     */
    protected function _safeParams(array $params)
    {
        if (empty($params)) {
            return $params;
        }

        foreach (array($this->id_column, $this->left_column, $this->right_column) as $key) {
            if (isset($params[$key])) {
                unset($params[$key]);
            }
        }

        return $params;
    }

    /**
     * "Замести следы"
     * Сжать расстояние соседей узла
     * @return boolean
     * @throws Exception
     */
    protected function _clean($n)
    {
        $node = $this->normalizeNode($n);

        $sql = '
            UPDATE ' . $this->db_table . '
                SET ' . $this->left_column . ' =
                    CASE WHEN ' . $this->left_column . ' > ' . $node->left . '
                        THEN ' . $this->left_column . ' - (' . $node->right . ' - ' . $node->left . ' + 1)
                        ELSE ' . $this->left_column . '
                    END,
                    ' . $this->right_column . ' = ' . $this->right_column . ' - (' . $node->right . ' - ' .
                    $node->left . ' + 1)
            WHERE ' . $this->right_column . ' > ' . $node->right;

        $q = $this->db->prepare($sql);
        $q->execute();

        return true;
    }

    protected function normalizeNode($node)
    {
        if ($node instanceof Node) {
            return $node;
        }

        if (!is_array($node)) {
            $node = $this->getNode($node);
        }

        if (empty($node)) {
            throw new Exception('Node Not Found!');
        }

        return $node;
    }

    public function __set($index, $value)
    {
        if (empty($index) or empty($value)) {
            throw new Exception('Пусто, блеать!');
        } elseif (!in_array($index, array('db_table', 'id_column', 'left_column', 'right_column', 'level'))) {
            throw new Exception('Переменная не зарезервирована для замены!');
        }

        $this->$index = $value;
    }

    public function __destruct()
    {
        $this->db = null;
    }
}
