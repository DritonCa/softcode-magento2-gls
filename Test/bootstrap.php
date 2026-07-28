<?php

/**
 * Standalone unit-test bootstrap.
 *
 * The unit tests only depend on a couple of Magento contracts, which they mock.
 * This bootstrap autoloads the module's own classes and defines minimal stand-ins
 * for those contracts *only when the real framework is absent*, so the tests run in
 * plain CI (no Magento install). Inside a real Magento install the genuine classes
 * exist and win.
 */

namespace {
    spl_autoload_register(static function ($class) {
        $prefix = 'Softcode\\Gls\\';
        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            return;
        }
        $path = __DIR__ . '/../' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($path)) {
            require $path;
        }
    });
}

namespace Magento\Framework\App\Config {
    if (!interface_exists(ScopeConfigInterface::class)) {
        interface ScopeConfigInterface
        {
            public function getValue($path, $scopeType = 'default', $scopeCode = null);

            public function isSetFlag($path, $scopeType = 'default', $scopeCode = null);
        }
    }
}

namespace Magento\Store\Model {
    if (!interface_exists(ScopeInterface::class)) {
        interface ScopeInterface
        {
            public const SCOPE_WEBSITE = 'website';
            public const SCOPE_WEBSITES = 'websites';
            public const SCOPE_STORE = 'store';
            public const SCOPE_STORES = 'stores';
        }
    }
}
