<?php

/**
 * Standalone unit-test bootstrap.
 *
 * The unit tests only touch a few Magento contracts, which they mock. This bootstrap
 * autoloads the module's own classes and defines minimal stand-ins for those
 * contracts *only when the real framework is absent*, so the tests run in plain CI
 * (no Magento install). Inside a real Magento install the genuine classes win.
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

    if (!function_exists('__')) {
        function __($text, ...$args)
        {
            foreach ($args as $i => $arg) {
                $text = str_replace('%' . ($i + 1), (string)$arg, $text);
            }
            return $text;
        }
    }
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

namespace Magento\Framework\Encryption {
    if (!interface_exists(EncryptorInterface::class)) {
        interface EncryptorInterface
        {
            public function decrypt($data);
        }
    }
}

namespace Magento\Framework\Exception {
    if (!class_exists(LocalizedException::class)) {
        class LocalizedException extends \Exception
        {
            public function __construct($phrase, ?\Exception $cause = null, $code = 0)
            {
                parent::__construct((string)$phrase, (int)$code, $cause);
            }
        }
    }
}
