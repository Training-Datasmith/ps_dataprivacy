<?php

/**
 * In-memory PrestaShop stand-ins for ps_dataprivacy unit tests.
 */

class PsDataprivacyHarness
{
    const SENTINEL = '[[SENTINEL_SHOULD_NOT_EMIT]]';
    const CONFIRMATION_MARKER = '[[CONFIRMATION]]';
    const SEEDED_ACTIVE_LANG_MARKER = '[[SEEDED_ACTIVE_LANG]]';

    public static function reset()
    {
        ConfigurationHarness::$values = array();
        ConfigurationHarness::$htmlKeys = array();
        LanguageHarness::$languages = array(
            array('id_lang' => 1, 'locale' => 'en-US', 'active' => 1),
            array('id_lang' => 2, 'locale' => 'fr-FR', 'active' => 1),
            array('id_lang' => 3, 'locale' => 'de-DE', 'active' => 0),
        );
        ModuleHarness::$installed = array();
        ModuleHarness::$hooks = array();
        ModuleHarness::$instances = array();
        ModuleHarness::$failInstall = false;
        ModuleHarness::$failRegisterHook = false;
        ModuleHarness::$failUnregisterHook = false;
        $_POST = array();
        $_GET = array();
        ContextHarness::reset();
    }

    public static function phpBinaryInContainer()
    {
        return 'php';
    }

    public static function runPhpScriptInContainer($scriptBody)
    {
        $workspace = realpath(dirname(dirname(__DIR__)));
        $tmpDir = $workspace . '/tests/tmp';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }
        $scriptPath = $tmpDir . '/subprocess-' . mt_rand() . '.php';
        file_put_contents($scriptPath, $scriptBody);
        $image = 'php@sha256:6ce95208609dc66df163ab936c970b3b34cd901b85c747102c5999f08ade9143';
        $rel = 'tests/tmp/' . basename($scriptPath);
        if (file_exists('/.dockerenv')) {
            $cmd = 'php ' . escapeshellarg('/workspace/' . $rel);
        } else {
            $docker = 'docker';
            $probe = array();
            exec('docker info >/dev/null 2>&1', $probe, $dockerOk);
            if ($dockerOk !== 0) {
                $docker = 'sudo docker';
            }
            $cmd = sprintf(
                '%s run --rm -v %s:/workspace -v /tmp:/tmp -w /workspace %s php %s',
                $docker,
                escapeshellarg($workspace),
                escapeshellarg($image),
                escapeshellarg('/workspace/' . $rel)
            );
        }
        $output = array();
        $exitCode = 0;
        exec($cmd . ' 2>&1', $output, $exitCode);
        @unlink($scriptPath);

        return array('exit' => $exitCode, 'output' => implode("\n", $output));
    }
}

class ConfigurationHarness
{
    public static $values = array();
    public static $htmlKeys = array();

    public static function deleteByName($key)
    {
        unset(self::$values[$key]);
        unset(self::$htmlKeys[$key]);
    }

    public static function get($key, $idLang = null)
    {
        if (!isset(self::$values[$key])) {
            return false;
        }
        if ($idLang === null || $idLang === false) {
            $idLang = 0;
        }
        $idLang = (int) $idLang;
        if (!isset(self::$values[$key][$idLang])) {
            return false;
        }

        return self::$values[$key][$idLang];
    }

    public static function updateValue($key, $values, $html = false)
    {
        if (!is_array($values)) {
            $values = array($values);
        }
        if ($html) {
            self::$htmlKeys[$key] = true;
        }
        if (!isset(self::$values[$key])) {
            self::$values[$key] = array();
        }
        foreach ($values as $lang => $value) {
            if (!$html && is_string($value)) {
                $value = strip_tags($value);
            }
            self::$values[$key][(int) $lang] = $value;
        }

        return true;
    }
}

class LanguageHarness
{
    public static $languages = array();

    public static function getLanguages($active = true)
    {
        $out = array();
        foreach (self::$languages as $lang) {
            if ($active && empty($lang['active'])) {
                continue;
            }
            $out[] = $lang;
        }

        return $out;
    }
}

class ContextHarness
{
    /** @var ContextHarness */
    private static $instance;

    public $language;
    public $link;
    public $controller;

    public static function reset()
    {
        self::$instance = new self();
        self::$instance->language = new stdClass();
        self::$instance->language->id = 1;
        self::$instance->link = new LinkHarness();
        self::$instance->controller = new AdminControllerHarness();
    }

    public static function getContext()
    {
        if (!self::$instance) {
            self::reset();
        }

        return self::$instance;
    }
}

class LinkHarness
{
    public function getAdminLink($controller, $withToken = true)
    {
        $url = 'index.php?controller=' . $controller;
        if ($withToken) {
            $url .= '&token=' . ToolsHarness::getAdminTokenLite($controller);
        }

        return $url;
    }
}

class AdminControllerHarness
{
    public function getLanguages()
    {
        return LanguageHarness::getLanguages(false);
    }
}

class ToolsHarness
{
    public static function getValue($key, $default_value = false)
    {
        if (!isset($key) || $key === '' || !is_string($key)) {
            return false;
        }
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }

        return $default_value;
    }

    public static function isSubmit($submit)
    {
        return isset($_POST[$submit]) || isset($_POST[$submit . '_x']) || isset($_POST[$submit . '_y'])
            || isset($_GET[$submit]) || isset($_GET[$submit . '_x']) || isset($_GET[$submit . '_y']);
    }

    public static function getAdminTokenLite($tab)
    {
        return 'admintoken-' . $tab;
    }
}

class ModuleHarness
{
    public static $installed = array();
    public static $hooks = array();
    public static $instances = array();
    public static $failInstall = false;
    public static $failRegisterHook = false;
    public static $failUnregisterHook = false;

    protected $table = 'module';
    protected $identifier = 'id_module';
    public $name;
    public $context;
    public $tab;
    public $author;
    public $version;
    public $need_instance;
    public $bootstrap;
    public $displayName;
    public $description;
    public $ps_versions_compliancy = array();

    public function __construct()
    {
        $this->context = ContextHarness::getContext();
    }

    public function install()
    {
        if (self::$failInstall) {
            return false;
        }
        self::$installed[$this->name] = true;

        return true;
    }

    public function uninstall()
    {
        unset(self::$installed[$this->name]);

        return true;
    }

    public static function isInstalled($name)
    {
        return !empty(self::$installed[$name]);
    }

    public function registerHook($hook)
    {
        if (self::$failRegisterHook) {
            return false;
        }
        if (!isset(self::$hooks[$this->name])) {
            self::$hooks[$this->name] = array();
        }
        self::$hooks[$this->name][] = $hook;

        return true;
    }

    public function unregisterHook($hook)
    {
        if (self::$failUnregisterHook) {
            return false;
        }
        if (!isset(self::$hooks[$this->name])) {
            return true;
        }
        $next = array();
        foreach (self::$hooks[$this->name] as $registered) {
            if ($registered !== $hook) {
                $next[] = $registered;
            }
        }
        self::$hooks[$this->name] = $next;

        return true;
    }

    public static function getInstanceByName($name)
    {
        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }

        return false;
    }

    public function getTranslator()
    {
        return null;
    }

    protected function trans($id, array $parameters = array(), $domain = null, $locale = null)
    {
        unset($parameters['legacy'], $parameters['_raw']);

        if ($domain === 'Admin.Notifications.Success') {
            return PsDataprivacyHarness::CONFIRMATION_MARKER;
        }

        if ($domain === 'Modules.Dataprivacy.Shop' && strpos($id, '%message%') !== false) {
            $message = isset($parameters['%message%']) ? $parameters['%message%'] : '';
            if ($message === false) {
                $message = '';
            }

            return '[[HOOK_LABEL:' . $message . ']]';
        }

        if ($domain === 'Modules.Dataprivacy.Admin' && $locale !== null) {
            return PsDataprivacyHarness::SEEDED_ACTIVE_LANG_MARKER;
        }

        return '[[TRANS:' . (string) $domain . ']]';
    }

    public function displayConfirmation($string)
    {
        return $string;
    }
}

class Blockcustomerprivacy extends ModuleHarness
{
    public function __construct()
    {
        $this->name = 'blockcustomerprivacy';
        parent::__construct();
    }

    public function uninstall()
    {
        ConfigurationHarness::deleteByName('CUSTPRIV_MSG_AUTH');

        return parent::uninstall();
    }
}

class FormFieldHarness
{
    private $name = '';
    private $type = 'text';
    private $required = false;
    private $label = '';
    private $value = null;

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setRequired($required)
    {
        $this->required = (bool) $required;

        return $this;
    }

    public function isRequired()
    {
        return $this->required;
    }

    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }
}

class HelperFormHarness
{
    public $show_toolbar = true;
    public $table;
    public $default_form_language;
    public $allow_employee_form_lang;
    public $identifier;
    public $submit_action;
    public $currentIndex;
    public $token;
    public $tpl_vars = array();

    public function generateForm($forms)
    {
        $payload = array(
            'show_toolbar' => $this->show_toolbar,
            'table' => $this->table,
            'default_form_language' => $this->default_form_language,
            'allow_employee_form_lang' => $this->allow_employee_form_lang,
            'identifier' => $this->identifier,
            'submit_action' => $this->submit_action,
            'currentIndex' => $this->currentIndex,
            'token' => $this->token,
            'tpl_vars' => $this->tpl_vars,
            'forms' => $forms,
        );

        return 'FORMJSON:' . json_encode($payload);
    }
}

// Global aliases expected by production code.
class Configuration extends ConfigurationHarness
{
}

class Language extends LanguageHarness
{
}

class Context extends ContextHarness
{
}

class Tools extends ToolsHarness
{
}

class Module extends ModuleHarness
{
}

class FormField extends FormFieldHarness
{
}

class HelperForm extends HelperFormHarness
{
}
