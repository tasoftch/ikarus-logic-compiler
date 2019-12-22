<?php
/**
 * Copyright (c) 2019 TASoft Applications, Th. Abplanalp <info@tasoft.ch>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Ikarus\Logic\Compiler\Test;


use Ikarus\Logic\Model\Component\Socket\InputComponent;
use Ikarus\Logic\Model\Component\Socket\OutputComponent;
use Ikarus\Logic\Model\Component\StaticNodeComponent;
use Ikarus\Logic\Model\Package\BasicTypesPackage;

class SimplePackage extends BasicTypesPackage
{
    protected function makeComponents(): array
    {
        $components = parent::makeComponents();

        $components[] = new StaticNodeComponent('simpleTest', [
            new InputComponent("input1"),
            new InputComponent("input2", 'Boolean')
        ]);
        $components[] = new StaticNodeComponent('otherTest', [
            new OutputComponent("output1", "String"),
            new OutputComponent("output2", "Number")
        ]);

        return $components;
    }

}