<?php
require_once(dirname(__FILE__) . '/class.db.php');
require_once(dirname(__FILE__) . '/class.tree.php');

define('AJAX_URL', $_SERVER['REQUEST_URI']);
define('APP_URL', str_replace('index.php', '', AJAX_URL));

class TreeController
{
    private $tree = null;
    private $action = null;
    // linker created for easy adding children to items by reference
    private $nodeLinker = [];
    private $nodeLinkerLost = [];

    public function __construct()
    {
        $this->tree = new tree(db::get('sqlite://sqlite.db?charset=UTF-8&timezone=Europe/Kiev'), array('structure_table' => 'tree_struct', 'data_table' => 'tree_data', 'data' => array('nm')));
    }

    /**
     * Analyze database
     *
     * @param bool $getErrors
     * @return array|bool
     */
    private function _analyze(bool $getErrors = true)
    {
        return $this->tree->analyze($getErrors);
    }

    private function _getNode(int $nodeId, bool $withChildren = false, bool $deepChildren = false)
    {
        $options = [
            'with_children' => $withChildren,
            'deep_children' => $deepChildren
        ];
        $node = $this->tree->get_node($nodeId, $options);

        // first node returns not in list, so we must to add it apart from all
        $rslt = [];
        $this->_childrenDistributor($this->_dbNodeOptimizer($node), $rslt);

        foreach($node['children'] as $child) {
            $this->_childrenDistributor($this->_dbNodeOptimizer($child), $rslt);
        }

        $this->_childrenDistributorCorrector($rslt);
        $this->_resetChildrenDistributor();

        return $rslt;
    }

    private function _getChildren(int $nodeId, bool $deepChildren = false)
    {
        if($nodeId == 0) {
            $temp = $this->tree->get_all();
        } else {
            $temp = $this->tree->get_children($nodeId, $deepChildren);
        }
        $rslt = [];

        foreach($temp as $item) {
            $this->_childrenDistributor($this->_dbNodeOptimizer($item), $rslt);
        }
        $this->_childrenDistributorCorrector($rslt);
        $this->_resetChildrenDistributor();

        return $rslt;
    }

    private function _createNode(int $nodeId, int $position = 0, string $text = 'New node')
    {
        $temp = $this->tree->mk($nodeId, $position, array('nm' => $text));
        return array('id' => $temp);
    }

    private function _renameNode(int $nodeId, string $text = 'Renamed node')
    {
        return $this->tree->rn($nodeId, array('nm' => $text));
    }

    private function _deleteNode(int $nodeId)
    {
        return $this->tree->rm($nodeId);
    }

    private function _moveNode(int $nodeId, int $parentId, int $position = 0)
    {
        return $this->tree->mv($nodeId, $parentId, $position);
    }

    private function _getNodeId($args, $key = 'id')
    {
        $notRoot = isset($args[$key]) && $args[$key] !== '#';
        if($notRoot && !is_numeric($args[$key])) throw new Exception('Unexpected "id" parameter');
        return $notRoot ? (int)$args[$key] : 0;
    }

    /**
     * Optimizes db item structure for user friendly structure
     * @param $node
     * @return array
     */
    private function _dbNodeOptimizer($node)
    {
        return array(
            'id'        => $node['id'],
            'text'      => $node['nm'],
            'parentId'  => $node['pid'],
            'children'  => $node['rgt'] - $node['lft'] > 1,
            'position'  => $node['pos'],
        );
    }

    /**
     * Places children in parents.
     *
     * NOTICE!
     * Need to use _childrenDistributorCorrector after using this method
     * because this logic requires two cycles for the correct placement of all.
     *
     * @param $node
     * @param $rsltArr
     */
    private function _childrenDistributor($node, &$rsltArr)
    {
        if(isset($this->nodeLinker[$node['parentId']]))
        {
            if(is_array($this->nodeLinker[$node['parentId']]['children']) == false)
            {
                $this->nodeLinker[$node['parentId']]['children'] = [];
            }
            // link node to children
            $this->nodeLinker[$node['parentId']]['children'][$node['position']] = &$node;
        }
        else
        {
            // to node to root
            if($node['parentId'] === 0)
            {
                $rsltArr[] = &$node;
            }
            else
            {
                $this->nodeLinkerLost[$node['parentId']] = &$node;
            }
        }
        $this->nodeLinker[$node['id']] = &$node;
    }
    /**
     * If some items was excepted during first cycle
     * we need little cycle for lost items
     * @param $rsltArr
     */
    private function _childrenDistributorCorrector(&$rsltArr) {
        foreach ($this->nodeLinkerLost as $key => $val) {
            $this->_childrenDistributor($val, $rsltArr);
        }
    }
    /**
     * Clear node linker array
     */
    private function _resetChildrenDistributor()
    {
        $this->nodeLinker = [];
    }

    /**
     * Main function for doing some action
     *
     * @param $action - getNode | getChildren | createNode | renameNode | deleteNode | moveNode
     * @param $args
     *  Args depends from $action, so:
     *  - getNode - gets node:
     *      'id'            - (int) required. Node id
     *      'withChildren'  - (bool) optional. Get node with single nested children. Default: false
     *      'deepChildren'  - (bool) optional. Get node with multiple nested children. Default: false
     * - getChildren - gets nested children:
     *      'id'            - (int) required. Node id
     *      'deepChildren'  - (bool) optional. Get node with multiple nested children. Default: false
     * - createNode - creates node:
     *      'id'        - (int) required. Node id
     *      'position'  - (int) optional. Node position. Default: 0
     *      'text'      - (int) optional. Node title. Default: 'New node'
     * - renameNode - renames node:
     *      'id'        - (int) required. Node id
     *      'text'      - (int) optional. Node title. Default: 'Renamed node'
     * - deleteNode - removes node:
     *      'id'        - (int) required. Node id
     * - moveNode - moves node:
     *      'id'        - (int) required. Current node id. What you move
     *      'parentId'  - (int) required. Parent node id. Where you move
     *      'position'  - (int) optional. Node position among parent's children. Default: 0
     *
     * @return array|bool|null
     */
    public function do($action, $args)
    {
        $result = null;
        $nodeId = $this->_getNodeId($args, 'id');

        if($action == 'getNode')
        {
            $withChildren = isset($args['withChildren']) ? (bool)$args['withChildren'] : false;
            $deepChildren = isset($args['deepChildren']) ? (bool)$args['deepChildren'] : false;
            $result = $this->_getNode($nodeId, $withChildren, $deepChildren);
        }
        else if($action == 'getChildren')
        {
            $deepChildren = isset($args['deepChildren']) ? (bool)$args['deepChildren'] : false;
            $result = $this->_getChildren($nodeId, $deepChildren);
        }
        else if ($action == 'createNode')
        {
            $position = isset($args['position']) ? $args['position'] : 0;
            $text = isset($args['text']) ? $args['text'] : 'New node';
            $result = $this->_createNode($nodeId, $position, $text);
        }
        else if ($action == 'renameNode')
        {
            $text = isset($args['text']) ? $args['text'] : 'Renamed node';
            $result = $this->_renameNode($nodeId, $text);
        }
        else if ($action == 'deleteNode')
        {
            $result = $this->_deleteNode($nodeId);
        }
        else if ($action == 'moveNode')
        {
            $parentId = $this->_getNodeId($args, 'parentId');
            $position = isset($args['position']) ? (int)$args['position'] : 0;
            $result = $this->{'_'.$action}($nodeId, $parentId, $position);
        }

        return $result;
    }

    /**
     * Render view
     */
    public function render()
    {
        include "view.php";
    }
}

$TreeController = new TreeController();
$response = '';

try {
    if(isset($_GET['action'])) {
        $response = array(
            'success'   => true,
            'message'   => 'All right',
            'data'      => $TreeController->do($_GET['action'], $_GET)
        );
    }
} catch (Exception $ex) {
    header($_SERVER["SERVER_PROTOCOL"] . ' 500 Server Error');
    header('Status:  500 Server Error');
    $response = array(
        'success'   => false,
        'message'   => $ex->getMessage()
    );
}

// if page used like API, return json-response and stop script
if(!empty($response)) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
    die();
}

$TreeController->render();