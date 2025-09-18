<?php

if (!defined('_PS_ROOT_DIR_')) {
    define('_PS_ROOT_DIR_', sys_get_temp_dir());
}

if (!class_exists('Module')) {
    class Module
    {
        public $context;

        public function __construct()
        {
            $this->context = new stdClass();
            $this->context->link = new Link();
            $this->context->language = (object) ['id' => 1];
        }

        public function l($string)
        {
            return $string;
        }
    }
}

if (!class_exists('PaymentModule')) {
    class PaymentModule extends Module
    {
        public $name;
        public $tab;
        public $version;
        public $author;
        public $currencies;
        public $currencies_mode;
        public $ps_versions_compliancy;
        public $controllers = [];

        public function registerHook($hook)
        {
            return true;
        }
    }
}

if (!class_exists('ModuleFrontController')) {
    class ModuleFrontController
    {
        public $module;
        public $context;

        public function __construct()
        {
            $this->context = new stdClass();
            $this->context->link = new Link();
            $this->context->language = (object) ['id' => 1];
        }
    }
}

if (!class_exists('Configuration')) {
    class Configuration
    {
        private static $values = [];

        public static function get($key)
        {
            return static::$values[$key] ?? '';
        }

        public static function updateValue($key, $value)
        {
            static::$values[$key] = $value;

            return true;
        }

        public static function updateGlobalValue($key, $value)
        {
            return static::updateValue($key, $value);
        }

        public static function deleteByName($key)
        {
            unset(static::$values[$key]);

            return true;
        }

        public static function reset()
        {
            static::$values = [];
        }
    }
}

if (!class_exists('Language')) {
    class Language
    {
        public static $languages = [
            ['id_lang' => 1, 'iso_code' => 'ru'],
            ['id_lang' => 2, 'iso_code' => 'uk'],
        ];

        public $iso_code = 'en';

        public function __construct($idLang)
        {
            foreach (static::$languages as $language) {
                if ((int) $language['id_lang'] === (int) $idLang) {
                    $this->iso_code = $language['iso_code'];

                    return;
                }
            }
        }

        public static function getLanguages($active = true)
        {
            return static::$languages;
        }

        public static function getIDs($active = true)
        {
            return array_map(function ($language) {
                return (int) $language['id_lang'];
            }, static::$languages);
        }
    }
}

if (!class_exists('Currency')) {
    class Currency
    {
        public $id;

        public function __construct($id)
        {
            $this->id = (int) $id;
        }
    }
}

if (!class_exists('Link')) {
    class Link
    {
        public function getPageLink($controller, $ssl = true, $idLang = null, array $params = [])
        {
            return sprintf('%s://example.com/%s?%s', $ssl ? 'https' : 'http', $controller, http_build_query($params));
        }
    }
}

if (!class_exists('Tools')) {
    class Tools
    {
        public static $values = [];
        public static $redirectedTo;

        public static function redirectAdmin($url)
        {
            static::$redirectedTo = $url;
        }

        public static function redirect($url)
        {
            static::$redirectedTo = $url;
        }

        public static function passwdGen($length)
        {
            return substr(str_shuffle(str_repeat('abcdef0123456789', 4)), 0, $length);
        }

        public static function getValue($key)
        {
            return static::$values[$key] ?? '';
        }

        public static function displayDate($date, $idLang)
        {
            return $date;
        }

        public static function displayPrice($price, Currency $currency)
        {
            return number_format((float) $price, 2) . ' ' . $currency->id;
        }

        public static function strtolower($string)
        {
            return strtolower($string);
        }

        public static function strlen($string)
        {
            return strlen($string);
        }

        public static function substr($string, $offset, $length = null)
        {
            return null === $length ? substr($string, $offset) : substr($string, $offset, $length);
        }
    }
}

if (!class_exists('Validate')) {
    class Validate
    {
        public static function isLoadedObject($object)
        {
            return $object instanceof stdClass ? false : !empty($object->loaded);
        }
    }
}

if (!class_exists('OrderState')) {
    class OrderState
    {
        public static $states = [];

        public $id;
        public $module_name;
        public $send_email;
        public $color;
        public $hidden;
        public $logable;
        public $invoice;
        public $delivery;
        public $shipped;
        public $paid;
        public $pdf_invoice;
        public $pdf_delivery;
        public $deleted;
        public $unremovable;
        public $name = [];
        public $template = [];
        public $loaded = false;

        public function __construct($id = null)
        {
            if (null !== $id && isset(static::$states[$id])) {
                $this->id = (int) $id;
                $this->name = static::$states[$id]['name'];
                $this->template = static::$states[$id]['template'];
                $this->loaded = true;
            }
        }

        public static function getOrderStates($idLang)
        {
            $result = [];
            foreach (static::$states as $id => $state) {
                $result[] = [
                    'id_order_state' => (int) $id,
                    'name' => $state['name'][$idLang] ?? '',
                ];
            }

            return $result;
        }

        public function add()
        {
            if (null === $this->id) {
                $this->id = count(static::$states) + 1;
            }

            static::$states[$this->id] = [
                'name' => $this->name,
                'template' => $this->template,
            ];
            $this->loaded = true;

            return true;
        }

        public function delete()
        {
            unset(static::$states[$this->id]);

            return true;
        }
    }
}

if (!class_exists('Tab')) {
    class Tab
    {
        public $id;
        public $class_name;
        public $module;
        public $id_parent;
        public $name;
        public $icon;

        public static $tabs = [];

        public function __construct($id = null)
        {
            if (null !== $id && isset(static::$tabs[$id])) {
                foreach (static::$tabs[$id] as $key => $value) {
                    $this->{$key} = $value;
                }
            }
        }

        public static function getIdFromClassName($className)
        {
            foreach (static::$tabs as $id => $tab) {
                if ($tab['class_name'] === $className) {
                    return $id;
                }
            }

            return 0;
        }

        public function add()
        {
            $this->id = count(static::$tabs) + 1;
            static::$tabs[$this->id] = [
                'class_name' => $this->class_name,
                'module' => $this->module,
                'id_parent' => $this->id_parent,
                'name' => $this->name,
                'icon' => $this->icon,
            ];

            return true;
        }

        public function delete()
        {
            unset(static::$tabs[$this->id]);

            return true;
        }
    }
}

if (!class_exists('DbQuery')) {
    class DbQuery
    {
        public function select($fields)
        {
            return $this;
        }

        public function from($table, $alias = null)
        {
            return $this;
        }

        public function where($condition)
        {
            return $this;
        }
    }
}

if (!class_exists('Db')) {
    class Db
    {
        private static $instance;

        public static function getInstance()
        {
            return static::$instance;
        }

        public static function setInstance($instance)
        {
            static::$instance = $instance;
        }
    }
}

if (!class_exists('DummyDb')) {
    class DummyDb
    {
        private $responses;

        public function __construct(array $responses)
        {
            $this->responses = $responses;
        }

        public function executeS($query)
        {
            return $this->responses;
        }
    }
}

if (!class_exists('OrderHistory')) {
    class OrderHistory
    {
        public static $changes = [];
        public $id_order;

        public function changeIdOrderState($newState, $idOrder)
        {
            static::$changes[] = ['order' => $idOrder, 'state' => $newState];
        }

        public function add()
        {
            return true;
        }
    }
}

if (!class_exists('PrestaShopLogger')) {
    class PrestaShopLogger
    {
        public static $logs = [];

        public static function addLog($message)
        {
            static::$logs[] = $message;
        }
    }
}

if (!class_exists('Mail')) {
    class Mail
    {
        public static function Send($idLang, $template, $subject, array $vars, $to, $toName, $from = null, $fromName = null, $fileAttachment = null, $modeSMTP = null, $templatePath = null, $die = false, $idShop = null)
        {
            return true;
        }
    }
}

if (!class_exists('Customer')) {
    class Customer
    {
        public $id;
        public $email = 'customer@example.com';
        public $firstname = 'John';
        public $lastname = 'Doe';
        public $secure_key = 'secure';
        public $loaded = true;

        public function __construct($id)
        {
            $this->id = (int) $id;
        }
    }
}

if (!class_exists('Order')) {
    class Order
    {
        public static $orders = [];

        public $id;
        public $id_customer;
        public $id_lang;
        public $id_currency;
        public $current_state;
        public $module;
        public $reference = 'REF';
        public $date_add = '2023-01-01 00:00:00';
        public $total_paid = 100.0;
        public $loaded = true;

        public function __construct($id)
        {
            if (isset(static::$orders[$id])) {
                foreach (static::$orders[$id] as $key => $value) {
                    $this->{$key} = $value;
                }
                $this->id = (int) $id;
            } else {
                $this->loaded = false;
            }
        }

        public function hasBeenPaid()
        {
            return false;
        }

        public function isInPreparation()
        {
            return false;
        }
    }
}

if (!class_exists('PaymentOption')) {
    class PaymentOption
    {
        public function setModuleName($name)
        {
            return $this;
        }

        public function setCallToActionText($text)
        {
            return $this;
        }

        public function setAction($action)
        {
            return $this;
        }

        public function setAdditionalInformation($info)
        {
            return $this;
        }
    }
}

require_once __DIR__ . '/../westernbid_starter_stripe.php';
require_once __DIR__ . '/../controllers/front/cancel.php';
