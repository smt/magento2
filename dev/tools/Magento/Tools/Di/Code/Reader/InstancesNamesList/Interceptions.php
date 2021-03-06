<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Tools\Di\Code\Reader\InstancesNamesList;

use Magento\Tools\Di\Compiler\Log\Log;

/**
 * Class Interceptions
 *
 * @package Magento\Tools\Di\Code\Reader\InstancesNamesList
 */
class Interceptions implements \Magento\Tools\Di\Code\Reader\InstancesNamesListInterface
{
    /**
     * @var \Magento\Tools\Di\Code\Reader\ClassReaderDecorator
     */
    private $classReader;

    /**
     * @var \Magento\Tools\Di\Code\Reader\ClassesScanner
     */
    private $classesScanner;

    /**
     * @var \Magento\Tools\Di\Compiler\Log\Log
     */
    private $log;

    /**
     * @var \Magento\Framework\Code\Validator
     */
    private $validator;

    /**
     * @param \Magento\Tools\Di\Code\Reader\ClassesScanner $classesScanner
     * @param \Magento\Framework\Code\Reader\ClassReader $classReader
     * @param \Magento\Framework\Code\Validator $validator
     * @param \Magento\Framework\Code\Validator\ConstructorIntegrity $constructorIntegrityValidator
     * @param \Magento\Framework\Code\Validator\ContextAggregation $contextAggregationValidator
     * @param Log $log
     */
    public function __construct(
        \Magento\Tools\Di\Code\Reader\ClassesScanner $classesScanner,
        \Magento\Framework\Code\Reader\ClassReader $classReader,
        \Magento\Framework\Code\Validator $validator,
        \Magento\Framework\Code\Validator\ConstructorIntegrity $constructorIntegrityValidator,
        \Magento\Framework\Code\Validator\ContextAggregation $contextAggregationValidator,
        Log $log
    ) {
        $this->classReader = $classReader;
        $this->classesScanner = $classesScanner;
        $this->validator = $validator;
        $this->log = $log;

        $this->validator->add($constructorIntegrityValidator);
        $this->validator->add($contextAggregationValidator);
    }

    /**
     * Retrieves list of classes for given path
     *
     * @param string $path path to dir with files
     *
     * @return array
     */
    public function getList($path)
    {
        $nameList = [];
        foreach ($this->classesScanner->getList($path) as $className) {
            try {
                if (!strpos($path, 'generation')) { // validate all classes except classes in var/generation dir
                    $this->validator->validate($className);
                }
                $nameList[] = $className;
            } catch (\Magento\Framework\Code\ValidationException $exception) {
                $this->log->add(Log::COMPILATION_ERROR, $className, $exception->getMessage());
            } catch (\ReflectionException $e) {
                $this->log->add(Log::COMPILATION_ERROR, $className, $e->getMessage());
            }
        }

        $this->log->report();

        return $nameList;
    }
}
