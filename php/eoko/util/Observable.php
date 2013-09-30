<?php

namespace eoko\util;

/**
 * Base class for observable object, mimicking ExtJS Observable API.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 12 juil. 2012
 */
abstract class Observable {
    
    private $listeners;
    
    /**
     * Adds an event class.
     * @param string $name
     * @return Observable 
     */
    protected function addEvent($name) {
        $this->listeners[$name] = array();
        return $this;
    }
    
    private function getListenerList($eventName) {
        if (isset($this->listeners[$name])) {
            return $this->listeners[$name];
        } else {
            throw new RuntimeException(
                "Class " . get_class($this) . " has not declared an event '$eventName'."
            );
        }
    }

    /**
     * Fires the event with the specified name and the given arguments. If
     * any of the event listener returns `false` then the event will be
     * stopped and the method will return `false`, else it will return `true`.
     * @param string $name
     * @param array $arguments
     * @return bool
     */
    protected function fireEvent($name, $arguments = array()) {
        foreach ($this->getListenerList($name) as $listener) {
            if (call_user_func_array($listener, $arguments) === false) {
                return false;
            }
        }
        return true;
    }
    
    public function on($name, $listener) {
        $list = $this->getListenerList($name);
        $list[] = $listener;
    }
    
    public function addListener($name, $listener) {
        return $this->on($name, $listener);
    }
    
    public function un($name, $listener) {
        foreach ($this->getListenerList($name) as $i => $l) {
            if ($listener === $l) {
                unset($this->listeners[$name][$i]);
                return true;
            }
        }
        return false;
    }
    
    public function removeListener($name, $listener) {
        return $this->un($name, $listener);
    }
}
