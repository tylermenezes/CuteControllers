<?php

namespace CuteControllers;

class ControllerFileTrie
{
    protected $path = '';
    protected $trie = NULL;

    public function __construct($path)
    {
        $last_node = ['left' => ['name' => 'index'], 'right'=>['name' => 'index']];
        $root_node = &$last_node;

        while (strlen($path) > 0) {
            // Remove leading slashes
            $path = ltrim($path, '/');

            // Get the next path part
            $file = NULL;
            while(strlen($path) > 0 && $path[0] !== '/') {
                $file .= $path[0];
                $path = substr($path, 1);
            }

            // Remove everything after the dot
            if (strrpos($file, '.')) {
                $file = substr($file, 0, strrpos($file, '.'));
            }

            $this->path .= '/' . $file;

            // Build the node
            $current_node = [
                // When we compare this with the file system, we'll prefer left-to-right
                'left' => [
                    'name' => $file,
                    'value' => NULL
                ],
                'right' => [
                    'name' => 'index'
                ]
            ];

            // Add the node
            $last_node['left']['value'] = &$current_node;
            $last_node = &$current_node;

            unset($current_node); // Break the reference
        }

        $last_node['left']['value'] = [
            'left' => [
                'name' => 'index',
                'value' => NULL
            ],
            'right' => [
                'name' => 'index'
            ]
        ];

        $this->trie = $root_node;
    }

    /**
     * Gets the full path to the node in the filesystem which is the most deep common node.
     * @param  string $base_path    The base of the filesystem search trie.
     * @return object               Match details, containing 'path', 'matched_path', and 'unmatched_path'
     */
    public function find_closest_filesystem_match($directory)
    {
        $directory = trim($directory, '/');
        return $this->get_closest_match($directory, $this->trie['left']['value']);
    }

    /**
     * Gets the full path to the node in the filesystem which is the most deep common node.
     * @param  string $base_path    The base of the filesystem search trie.
     * @param  array  $current_node Node to start at.
     * @param  array  $built_path   Current search path. Used internally.
     * @return object               Match details, containing 'path', 'matched_path', and 'unmatched_path'
     */
    private function get_closest_match($base_path, $current_node, $built_path = [])
    {
        $current_path = implode('/', array_merge([$base_path], $built_path));

        // If there's a directory which matches this node, visit that first:
        if ($current_node['left']['value'] !== NULL &&
            is_dir(implode('/', [$current_path, $current_node['left']['name']]))) {

            // Add the directory onto the state of the search path
            $new_built_path = array_merge($built_path, [$current_node['left']['name']]);


            // Get the value of the match
            $ret = $this->get_closest_match($base_path, $current_node['left']['value'], $new_built_path);

            // If the value is null, nothing in the folder matched. Otherwise, we have the most specific match, so
            // cascade return up the stack
            if ($ret !== NULL) {
                return $ret;
            }
        }

        // At this point, either we reached the end of our route trie, or there was nothing matching in the deeper
        // levels of the route, so we'll check the current leaves.

        // First, check for a file which matches:
        if (is_file(implode('/', ['', $current_path, $current_node['left']['name'] . '.php']))) {
            // Found a file with that name!
            return (object)[
                'path' => implode('/', ['', $current_path, $current_node['left']['name'] . '.php']),
                'matched_path' => implode('/', array_merge($built_path, [$current_node['left']['name'] . '.php'])),
                'unmatched_path' => self::get_unmatched_path($current_node['left']['value'])
            ];

        // Check for an index.php file at the current directory level
        } else if(is_file(implode('/', ['', $current_path, 'index.php']))) {
            // Found an index.php at the current directory
            return (object)[
                'path' => implode('/', ['', $current_path, 'index.php']),
                'matched_path' => implode('/', array_merge($built_path, ['index.php'])),
                'unmatched_path' => self::get_unmatched_path($current_node)
            ];
        // Nothing matched at this level, or in deeper levels. Return NULL.
        } else {
            return NULL;
        }
    }

    /**
     * Gets the subnodes of a node, represented as a path
     * @param  array  $node Node to find subnodes of
     * @return string       Path-ified subnodes
     */
    private static function get_unmatched_path($node)
    {
        if ($node['left']['value'] === NULL) {
            return NULL; // Last node will always be "index"
        } else {
            return rtrim(implode('/', [$node['left']['name'], self::get_unmatched_path($node['left']['value'])]), '/');
        }
    }
}
