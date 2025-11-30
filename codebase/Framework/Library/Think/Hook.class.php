<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2013 http://topthink.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: support@codono.com
// +----------------------------------------------------------------------
namespace Think;
/**
 * ThinkPHPHook achieve
 */
class Hook
{

    static private $tags = array();

    /**
     * dynamicAdd toPlug-in to alabel
     * @param string $tag Label name
     * @param mixed $name Plugin name
     * @return void
     */
    static public function add($tag, $name)
    {
        if (!isset(self::$tags[$tag])) {
            self::$tags[$tag] = array();
        }
        if (is_array($name)) {
            self::$tags[$tag] = array_merge(self::$tags[$tag], $name);
        } else {
            self::$tags[$tag][] = $name;
        }
    }

    /**
     * Batch Import plug-in
     * @param array $data Plug-in information
     * @param boolean $recursive Whether recursive merge
     * @return void
     */
    static public function import($data, $recursive = true)
    {
        if (!$recursive) { // Import coverage
            self::$tags = array_merge(self::$tags, $data);
        } else { // The combined import
            foreach ($data as $tag => $val) {
                if (!isset(self::$tags[$tag]))
                    self::$tags[$tag] = array();
                if (!empty($val['_overlay'])) {
                    // canagainstAlabelDesignationcovermode
                    unset($val['_overlay']);
                    self::$tags[$tag] = $val;
                } else {
                    // Merge mode
                    self::$tags[$tag] = array_merge(self::$tags[$tag], $val);
                }
            }
        }
    }

    /**
     * Get Plugin information
     * @param string $tag Plug-in location Get All blank
     * @return array
     */
    static public function get($tag = '')
    {
        if (empty($tag)) {
            // Get all of the plug-in information
            return self::$tags;
        } else {
            return self::$tags[$tag];
        }
    }

    /**
     * Listener tag plugin
     * @param string $tag Label name
     * @param mixed $params Incoming parameters
     * @return void
     */
    static public function listen($tag, &$params = NULL)
    {
        if (isset(self::$tags[$tag])) {
            if (APP_DEBUG) {
                G($tag . 'Start');
                trace('[ ' . $tag . ' ] --START--', '', 'INFO');
            }
            foreach (self::$tags[$tag] as $name) {
                APP_DEBUG && G($name . '_start');
                $result = self::exec($name, $tag, $params);
                if (APP_DEBUG) {
                    G($name . '_end');
                    trace('Run ' . $name . ' [ RunTime:' . G($name . '_start', $name . '_end', 6) . 's ]', '', 'INFO');
                }
                if (false === $result) {
                    // If the returnfalse Plug execution is interrupted
                    return;
                }
            }
            if (APP_DEBUG) { // recordingbehaviorofcarried outJournal
                trace('[ ' . $tag . ' ] --END-- [ RunTime:' . G($tag . 'Start', $tag . 'End', 6) . 's ]', '', 'INFO');
            }
        }
        return;
    }

    /**
     * The implementation of a plugin
     * @param string $name Plugin name
     * @param string $tag Method name (labelname)
     * @param Mixed $params Incoming parameters
     * @return void
     */
    static public function exec($name, $tag, &$params = NULL)
    {
        if ('Behavior' == substr($name, -8)) {
            // Behavior extension must be usedrunEntrance method
            $tag = 'run';
        }
        $addon = new $name();
        return $addon->$tag($params);
    }
}
