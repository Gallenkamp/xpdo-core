<?php
/*
 * Copyright 2010-2015 by MODX, LLC.
 *
 * This file is part of xPDO.
 *
 * xPDO is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * xPDO is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * xPDO; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 */

/**
 * Provides an APCu-powered xPDOCache implementation.
 *
 * This requires the APCu extension for PHP, version xxx or later.
 *
 * @package xpdo
 * @subpackage cache
 */
class xPDOAPCuCache extends xPDOCache {
    public function __construct(& $xpdo, $options = array()) {
        parent :: __construct($xpdo, $options);
        if (function_exists('apcu_exists')) {
            $this->initialized = true;
        } else {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "xPDOAPCuCache[{$this->key}]: Error creating APCu cache provider; xPDOAPCuCache requires the APC extension for PHP, version 2.0.0 or later.");
        }
    }

    public function add($key, $var, $expire= 0, $options= array()) {
        $added= apcu_add(
            $this->getCacheKey($key),
            $var,
            $expire
        );
        return $added;
    }

    public function set($key, $var, $expire= 0, $options= array()) {
        $set= apcu_store(
            $this->getCacheKey($key),
            $var,
            $expire
        );
        return $set;
    }

    public function replace($key, $var, $expire= 0, $options= array()) {
        $replaced = false;
        if (apcu_exists($key)) {
            $replaced= apcu_store(
                $this->getCacheKey($key),
                $var,
                $expire
            );
        }
        return $replaced;
    }

    public function delete($key, $options= array()) {
        $deleted = false;
        if (!isset($options['multiple_object_delete']) || empty($options['multiple_object_delete'])) {
            $deleted= apcu_delete($this->getCacheKey($key));
        } elseif (class_exists('APCuIterator', true)) {
            $iterator = new APCuIterator('user', '/^' . str_replace('/', '\/', $this->getCacheKey($key)) . '/', APCU_ITER_KEY);
            if ($iterator) {
                $deleted = apcu_delete($iterator);
            }
        }
        return $deleted;
    }

    public function get($key, $options= array()) {
        $value= apcu_fetch($this->getCacheKey($key));
        return $value;
    }

    public function flush($options= array()) {
        $flushed = false;
        if (class_exists('APCuIterator', true) && $this->getOption('flush_by_key', $options, true) && !empty($this->key)) {
            $iterator = new APCuIterator('user', '/^' . str_replace('/', '\/', $this->key) . '\//', APCU_ITER_KEY);
            if ($iterator) {
                $flushed = apcu_delete($iterator);
            }
        } else {
            $flushed = apcu_clear_cache('user');
        }
        return $flushed;
    }
}
