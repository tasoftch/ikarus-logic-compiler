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

namespace Ikarus\Logic\Compiler\Storage;


use Ikarus\Logic\Compiler\AbstractCompiler;
use Ikarus\Logic\Compiler\CompilerResult;
use Ikarus\Logic\Compiler\Executable\ExecutableCompiler;
use Ikarus\Logic\Compiler\Executable\ExposedSocketsCompiler;
use Ikarus\Logic\Model\Data\DataModelInterface;
use Ikarus\Logic\Model\Exception\InconsistentDataModelException;
use Ikarus\Logic\Model\Exception\LogicException;

class PHPFileStorageCompiler extends AbstractCompiler
{
    /** @var string */
    private $filename;



    public function compile(DataModelInterface $dataModel, CompilerResult $result)
    {
        if($result->isSuccess()) {
            try {
                $exec = $result->getAttribute( ExecutableCompiler::RESULT_ATTRIBUTE_EXECUTABLE );
                if(!$exec) {
                    throw new LogicException("No executable found", LogicException::CODE_SYMBOL_NOT_FOUND);
                }

                $exposed = $result->getAttribute( ExposedSocketsCompiler::RESULT_ATTRIBUTE_EXPOSED_SOCKETS );

                file_put_contents( $this->getFilename(), sprintf("<?php\nreturn unserialize(%s);", var_export( serialize(['x' => $exposed, 'X' => $exec]), true ) ));
            } catch (InconsistentDataModelException $exception) {
                $exception->setModel($dataModel);
                $result->setSuccess(false);
                throw $exception;
            }
        }
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     */
    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

}