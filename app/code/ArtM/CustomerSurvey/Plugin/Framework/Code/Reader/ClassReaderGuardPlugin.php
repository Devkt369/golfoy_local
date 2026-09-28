<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Plugin\Framework\Code\Reader;

use Magento\Framework\Code\Reader\ClassReader;

class ClassReaderGuardPlugin
{
    /**
     * Compatibility shim for staging nodes that still reference this plugin
     * in generated DI metadata from a previous deployment.
     *
     * @param mixed $className
     * @return array<int, string>
     */
    public function aroundGetParents(ClassReader $subject, callable $proceed, $className): array
    {
        if (!is_string($className) || $className === '') {
            return [];
        }

        if (!class_exists($className) && !interface_exists($className)) {
            return [];
        }

        try {
            return $proceed($className);
        } catch (\TypeError $exception) {
            return [];
        }
    }
}
