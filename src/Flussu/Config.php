<?php
/* --------------------------------------------------------------------*
 * Flussu v4.1.0 - Mille Isole SRL - Released under Apache License 2.0
 * --------------------------------------------------------------------*
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * --------------------------------------------------------------------*
 * CLASS PATH:       App\Flussu
 * CLASS-NAME:       General.class
 * -------------------------------------------------------*
 * RELEASED DATE:    07.01:2022 - Aldus - Flussu v2.0
 * VERSION REL.:     4.1.0 20250113 
 * UPDATE DATE:      12.01:2025 
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - -*
 * Releases/Updates:
 * -------------------------------------------------------*/

/*
    La casse GENERAL è una sorta di contenitore di utilità, usata per diversi scopi in più parti di Flussu
    contiene una serie di routine di base, di utilità e generali.
*/

/**
 * The General class is responsible for providing general configuration and utility functions within the Flussu server.
 * 
 * This class manages various static properties and methods that are used throughout the Flussu server for configuration,
 * logging, and other general purposes. It serves as a central point for managing global settings and utilities.
 * 
 * Key responsibilities of the General class include:
 * - Managing global configuration settings such as document root, debug mode, password validity period, language, and development mode status.
 * - Initializing and managing the logging process, including starting the log and recording the start time.
 * - Providing utility functions that can be used across different components of the Flussu server.
 * 
 * The class is designed to be easily accessible and modifiable, allowing for quick adjustments to global settings and
 * the addition of new utility functions as needed.
 * 
 */

 namespace Flussu;
class Config
{
    private static $config = [];

    /**
     * Carica il file di configurazione JSON
     */
    public static function load($path = __DIR__ . '/services.json')
    {
        if (!file_exists($path)) {
            throw new \Exception("Configuration file not found: $path");
        }

        $json = file_get_contents($path);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON configuration file: ' . json_last_error_msg());
        }

        self::$config = $data;
    }

    /**
     * Ottiene una configurazione
     */
    public static function get($key, $default = null)
    {
        return self::$config[$key] ?? $default;
    }

    /**
     * Imposta dinamicamente una configurazione
     */
    public static function set($key, $value)
    {
        self::$config[$key] = $value;
    }

    /**
     * Restituisce tutte le configurazioni
     */
    public static function all()
    {
        return self::$config;
    }
}
?>
