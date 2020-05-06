<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 *
 */
return [
    'name' => 'taoScheduler',
    'label' => 'Job Scheduler',
    'description' => 'TAO job scheduler',
    'license' => 'GPL-2.0',
    'version' => '2.2.0',
    'author' => 'Open Assessment Technologies SA',
    'requires' => [
        'generis' => '>=12.15.0',
        'tao' => '>=15.10.0',
        'taoTaskQueue' => '>=1.2.0'
    ],
    'managementRole' => 'http://www.tao.lu/Ontologies/generis.rdf#taoSchedulerManager',
    'acl' => [
        ['grant', 'http://www.tao.lu/Ontologies/generis.rdf#taoSchedulerManager', ['ext'=>'taoScheduler']],
    ],
    'install' => [
        'php' => [
            oat\taoScheduler\scripts\install\RegisterRdsStorage::class,
            oat\taoScheduler\scripts\install\RegisterJobs::class,
        ]
    ],
    'uninstall' => [

    ],
    'update' => oat\taoScheduler\scripts\update\Updater::class,
    'routes' => [
        '/taoScheduler' => 'oat\\taoScheduler\\controller'
    ],
    'constants' => [
        # views directory
        "DIR_VIEWS" => __DIR__.DIRECTORY_SEPARATOR."views".DIRECTORY_SEPARATOR,

        #BASE URL (usually the domain root)
        'BASE_URL' => ROOT_URL.'taoScheduler/',
    ],
    'extra' => [
        'structures' => __DIR__.DIRECTORY_SEPARATOR.'controller'.DIRECTORY_SEPARATOR.'structures.xml',
    ],
];
