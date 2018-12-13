<?php

/*
 * This file is part of Jeedom.
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

require_once __DIR__ . '/../core/class/jElockyLog.class.php';
require_once __DIR__ . '/../core/class/jElockyUtil.class.php';


/**
 * Patch plugin.template.js if required (core version < 3.3.7)
 */
function jElocky_patch_core() {
    $ver = jeedom::version();
    if (version_compare($ver, '3.3.7', '<')) {
        jElockyLog::add('info', 'le core ' . $ver . ' va être patché pour que le plugin jElocky fonctionne');
        $f = __DIR__ . '/../../../core/js/plugin.template.js';
        exec('patch -r - -N -b -i ' . __DIR__ . '/plugin.template.js.diff ' . $f);
        passthru('grep -q "prePrintEqLogic(\$(this).attr(\'data-eqLogic_id\'));" ' . $f, $err);
        if ($err == 0)
            jElockyLog::add('info', 'patch du core correctement effectué');
        else
            jElockyLog::add('error', 'patch du core non effectué: le plugin ne fonctionnera pas');
    }
    else
        jElockyLog::add('debug', 'core version >= 3.3.7: no need for patch');
    
    log::add('plugin', 'debug', 'End ' . __METHOD__);
}

/**
 * Called on plugin activation
 */
function jElocky_install() {
    jElockyLog::startStep(__METHOD__);
    
    // Patch the core if needed
    jElocky_patch_core();

    // Creation of the data directory
    if (! file_exists(jElockyUtil::DATA_DIR)) {
        jElockyLog::add('info', 'création du répertoire ' . jElockyUtil::DATA_DIR);
        exec(
            'mkdir -p ' . jElockyUtil::DATA_DIR . ' && chmod 775 -R ' . jElockyUtil::DATA_DIR .
            ' && chown -R www-data:www-data ' . jElockyUtil::DATA_DIR);
    }
    jElockyLog::endStep();
}

/**
 * Called on plugin reactivation after reinstallation or update
 */
function jElocky_update() {
    jElockyLog::startStep(__METHOD__);
    jElocky_patch_core();
    jElockyLog::endStep();
}

function jElocky_remove() {}


