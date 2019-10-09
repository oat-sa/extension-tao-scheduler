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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoScheduler\test\model\action;

use oat\taoScheduler\model\action\Action;
use oat\oatbox\extension\AbstractAction;
use oat\oatbox\service\ServiceManager;
use oat\oatbox\action\Action as TaoAction;
use oat\generis\test\TestCase;

/**
 * Class ActionTest
 * @package oat\taoScheduler
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class ActionTest extends TestCase
{

    public function testInvoke()
    {
        $action = new Action(ActionMock::class);
        $this->assertTrue($action());

        $action = new Action(ActionMockSWAware::class);
        $action->setServiceLocator(new ServiceManager([]));
        $this->assertTrue($action());
    }
}

class ActionMock implements TaoAction
{
    public function __invoke($params)
    {
        return true;
    }
}

class ActionMockSWAware extends AbstractAction
{
    public function __invoke($params)
    {
        return $this->getServiceLocator() !== null;
    }
}