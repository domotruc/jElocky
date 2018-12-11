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

/* ****************************** Includes ********************************* */
require_once __DIR__ . '/../../../../core/php/core.inc.php';
require_once __DIR__ . '/jElockyLog.class.php';

class jElockyUtil {

    const DATA_DIR = __DIR__ . '/../../data';
        
    /**
     * @return string data relative directory path (wrt to the jeedom root directory) 
     */
    public static function getRelativeDataDir() {
        return strstr(self::DATA_DIR, 'plugins');
    }
    
    /**
     * @param array $_arr
     * @param string $_ref
     * @return mixed|boolean Returns the key of the array containing $_ref, false if not found. 
     */
    public static function array_search_ref($_arr, $_ref) {
        foreach($_arr as $key => $arr_2) {
            if ($arr_2['ref'] == $_ref) {
                return $key;
            }
        }
        return false;
    }
       
    public static function searchConfiguration($_configuration, $_type = null) {
        if (!is_array($_configuration)) {
            $values = array(
                'configuration' => '%' . $_configuration . '%',
            );
            $sql = 'SELECT ' . DB::buildField('eqLogic') . '
			        FROM eqLogic
			        WHERE configuration LIKE :configuration';
        }
        else {
            $values = array(
                'configuration' => '%' . $_configuration[0] . '%',
            );
            $sql = 'SELECT ' . DB::buildField('eqLogic') . '
			        FROM eqLogic
			        WHERE (configuration LIKE :configuration';
            for ($i = 1; $i < count($_configuration); $i++) {
                $values['configuration' . $i] = '%' . $_configuration[$i] . '%';
                $sql .= ' OR configuration LIKE :configuration' . $i;
            }
            $sql = $sql . ')';
        }
        if ($_type !== null) {
            $values['eqType_name'] = $_type;
            $sql .= ' AND eqType_name=:eqType_name ';
        }
        $sql .= ' ORDER BY name';
        return eqLogic::cast(DB::Prepare($sql, $values, DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, eqLogic::class));
    }
}
