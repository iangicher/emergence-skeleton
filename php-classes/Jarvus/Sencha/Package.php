<?php

namespace Jarvus\Sencha;

abstract class Package implements IPackage
{
    public static $sources = [
        WorkspacePackage::class,
        FrameworkPackage::class,
        \Chaki\Package::class // TODO: have chaki package add itself via php-config layer
    ];


    protected $name;
    protected $config;

    protected $antConfig;
    protected $packageAntConfig;
    protected $classPaths;

    // factories
    final public static function get($name, Framework $framework)
    {
        foreach (static::$sources AS $source) {
            if (!is_a($source, IPackage::Class, true)) {
                throw new \Exception('Source is not a package subclass');
            }

            if ($package = $source::load($name, $framework)) {
                return $package;
            }
        }

        return null;
    }


    // magic methods and property getters
    public function __construct($name, $config)
    {
        $this->name = $name;
        $this->config = $config;
    }

    public function __toString()
    {
        return $this->name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getConfig($key = null)
    {
        return $key ? $this->config[$key] : $this->config;
    }


    // member methods

    /**
     * Gets aggregate ant config of workspace/sencha.cfg + app/sencha.cfg + app.json
     */
    public function getAntConfig($key = null)
    {
        if (!$this->antConfig) {
            // TODO: maybe execute an ant task to get these? cache until any .cfg or .properties files change in app or workspace?

            // start with package ant config (already in dotted-key tree format)
            $this->antConfig = $this->getPackageAntConfig();

            // append nested array data from package.json config on top of dotted-key tree
            \Emergence\Util\Data::collapseTreeToDottedKeys($this->config, $this->antConfig, 'package');

            // TODO: cache this with an event handler to clear?
        }

        return $key ? $this->antConfig[$key] : $this->antConfig;
    }

    /**
     * Gets dotted-key values from app/sencha.cfg
     */
    public function getPackageAntConfig($key = null)
    {
        if (!$this->packageAntConfig) {
            $antConfigPointer = $this->getFilePointer('.sencha/package/sencha.cfg');

            if (!$antConfigPointer) {
                throw new \Exception("Could not get pointer to .sencha/package/sencha.cfg for package $this");
            }

            $this->packageAntConfig = Util::loadAntProperties($antConfigPointer);
        }

        return $key ? $this->packageAntConfig[$key] : $this->packageAntConfig;
    }

    public function getRequiredPackageNames()
    {
        $packages = $this->getConfig('requires') ?: [];

        if (($extendPackage = $this->getConfig('extend')) && !in_array($extendPackage, $packages)) {
            $packages[] = $extendPackage;
        }

        return $packages;
    }

    public function getClassPaths()
    {
        if (!$this->classPaths) {
            $this->classPaths = array_filter(explode(',', $this->getAntConfig('package.classpath')));
        }

        return $this->classPaths;
    }


    // static utility methods
    public static function loadPackageConfig($packagePath)
    {
        if ($packagePath instanceof \SiteFile) {
            $packagePath = $packagePath->RealPath;
        }

        $packageConfig = json_decode(Util::cleanJson(file_get_contents($packagePath)), true);

        if (!$packageConfig || empty($packageConfig['name'])) {
            throw new \Exception("Could not parse package.json for $packagePath");
        }

        return $packageConfig;
    }

    public static function aggregatePackageDependencies(array $inputPackages, Framework $framework, array &$outputPackages = [])
    {
        foreach ($inputPackages AS $packageName) {
            if (isset($outputPackages[$packageName])) {
                $package = $outputPackages[$packageName];
            } else {
                $package = $outputPackages[$packageName] = static::get($packageName, $framework);
            }

            if (!$package) {
                throw new \Exception("Could not find source for package $packageName");
            }

            static::aggregatePackageDependencies($package->getRequiredPackageNames(), $framework, $outputPackages);
        }

        return $outputPackages;
    }
}