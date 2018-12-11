<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once __DIR__ . '/../../../core/php/core.inc.php';
require_once __DIR__ . '/../core/class/jElockyUtil.class.php';

function patch_core() {
    exec('patch -r - -N -b -i ' . __DIR__ . '/plugin.template.js.diff ' . __DIR__ . '/../core/plugin.template.js');
}

function jElocky_install() {
    patch_core();
    
    // Creation of the data directory
    if (!file_exists(jElockyUtil::DATA_DIR)) {
        exec('mkdir ' . jElockyUtil::DATA_DIR . ' && chmod 775 -R ' . jElockyUtil::DATA_DIR . ' && chown -R www-data:www-data ' . jElockyUtil::DATA_DIR);
    }
}

function jElocky_update() {
    patch_core();
}

function jElocky_remove() {
}

?>
