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

namespace Ikarus\Logic\Compiler;

use Ikarus\Logic\Model\Component\ComponentModelInterface;
use Ikarus\Logic\Model\Data\DataModelInterface;

/**
 * The compiler translates an Ikarus Logic data model into an executable structure.
 * This structure can be performed using the Ikarus Engine Package or stored on disk for later usage.
 *
 * @package Ikarus\Logic\Compiler
 */
interface CompilerInterface
{
    /**
     * Creating a compiler always need knowing of the component model that will be used.
     * NOTE: The component model is always asked for components and socket types, they are not cached.
     *
     * @param ComponentModelInterface $componentModel
     */
    public function __construct(ComponentModelInterface $componentModel);

    /**
     * @param DataModelInterface $dataModel
     * @param CompilerResult $result
     */
    public function compile(DataModelInterface $dataModel, CompilerResult $result);
}